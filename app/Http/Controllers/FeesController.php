<?php

namespace App\Http\Controllers;

use App\Models\FeesAdvance;
use App\Models\FeesClassType;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\FeesClassType\FeesClassTypeInterface;
use App\Repositories\FeesInstallment\FeesInstallmentInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\FeesType\FeesTypeInterface;
use App\Repositories\Medium\MediumInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;
use App\Services\UserService;

class FeesController extends Controller
{
    private FeesInterface $fees;
    private SessionYearInterface $sessionYear;
    private FeesInstallmentInterface $feesInstallment;
    private SchoolSettingInterface $schoolSettings;
    private MediumInterface $medium;
    private FeesTypeInterface $feesType;
    private ClassSchoolInterface $classes;
    private FeesClassTypeInterface $feesClassType;
    private UserInterface $user;
    private FeesPaidInterface $feesPaid;
    private CompulsoryFeeInterface $compulsoryFee;
    private OptionalFeeInterface $optionalFee;
    private CachingService $cache;
    private PaymentConfigurationInterface $paymentConfigurations;
    private ClassSchoolInterface $class;
    private StudentInterface $student;
    private PaymentTransactionInterface $paymentTransaction;
    private SystemSettingInterface $systemSetting;
    private ClassSectionInterface $classSection;
    private UserService $userService;

    public function __construct(FeesInterface $fees, SessionYearInterface $sessionYear, FeesInstallmentInterface $feesInstallment, SchoolSettingInterface $schoolSettings, MediumInterface $medium, FeesTypeInterface $feesType, ClassSchoolInterface $classes, FeesClassTypeInterface $feesClassType, UserInterface $user, FeesPaidInterface $feesPaid, CompulsoryFeeInterface $compulsoryFee, OptionalFeeInterface $optionalFee, CachingService $cache, PaymentConfigurationInterface $paymentConfigurations, ClassSchoolInterface $classSchool, StudentInterface $student, PaymentTransactionInterface $paymentTransaction, SystemSettingInterface $systemSetting, ClassSectionInterface $classSection, UserService $userService)
    {
        $this->fees = $fees;
        $this->sessionYear = $sessionYear;
        $this->feesInstallment = $feesInstallment;
        $this->schoolSettings = $schoolSettings;
        $this->medium = $medium;
        $this->feesType = $feesType;
        $this->classes = $classes;
        $this->feesClassType = $feesClassType;
        $this->user = $user;
        $this->feesPaid = $feesPaid;
        $this->compulsoryFee = $compulsoryFee;
        $this->optionalFee = $optionalFee;
        $this->cache = $cache;
        $this->paymentConfigurations = $paymentConfigurations;
        $this->class = $classSchool;
        $this->student = $student;
        $this->paymentTransaction = $paymentTransaction;
        $this->systemSetting = $systemSetting;
        $this->classSection = $classSection;
        $this->userService = $userService;
    }

    /* START : Fees Module */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        $classes = $this->class->all(['*'], ['stream', 'medium', 'shift']);
        $feesTypeData = $this->feesType->all();
        $mediums = $this->medium->builder()->pluck('name', 'id');
        return view('fees.index', compact('classes', 'feesTypeData', 'mediums'));
    }

    public function store(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Fees Management');
        ResponseService::noPermissionThenSendJson('fees-create');
        $request->validate([
            'include_fee_installments' => 'required|boolean',
            'due_date' => 'required|date',
            'due_charges_percentage' => 'required|numeric',
            'due_charges_amount' => 'required|numeric',
            'class_id' => 'required|array',
            'class_id.*' => 'required|numeric',
            'compulsory_fees_type' => 'required|array',
            'compulsory_fees_type.*' => 'required|array',
            'compulsory_fees_type.*.fees_type_id' => 'required|numeric',
            'compulsory_fees_type.*.amount' => 'required|numeric',
            'optional_fees_type.*' => 'required|array',
            'optional_fees_type.*.fees_type_id' => 'required|numeric',
            'optional_fees_type.*.amount' => 'required|numeric',
            'fees_installments' => 'required_if:include_fee_installments,1|array',
            'fees_installments.*.name' => 'required',
            'fees_installments.*.due_date' => 'required|date',
            'fees_installments.*.due_charges' => 'required|numeric'
        ]);
        try {

            if ($request->include_fee_installments) {
                $totalInstallments = collect($request->fees_installments)->sum('amount');
                $totalCompulsoryFees = collect($request->compulsory_fees_type)->sum('amount');

                if ((float) $totalInstallments !== (float) $totalCompulsoryFees) {
                    return ResponseService::errorResponse('Total amount of Fees Installments is not equal to the total amount of Compulsory Fees');
                }
            }

            DB::beginTransaction();
            $sessionYear = $this->cache->getSessionYear();
            $classes = $this->class->builder()->whereIn("id", $request->class_id)->with('stream', 'medium')->get();

            $notifyUser = $this->student->builder()->where('session_year_id', $sessionYear->id)->whereHas('class_section', function ($q) use ($request) {
                $q->whereIn('class_id', $request->class_id);
            })->pluck('guardian_id');

            $title = 'Fees';
            $body = $request->name;
            $type = 'Fees';
            // send_notification($notifyUser, $title, $body, $type); // Send Notification

            foreach ($request->class_id as $class_id) {
                $class = $classes->first(function ($data) use ($class_id) {
                    return $data->id == $class_id;
                });
                $name = (!empty($request->name)) ? $request->name . " - " : "";
                $fees = $this->fees->create([
                    'name' => $name . $class->full_name,
                    'due_date' => $request->due_date,
                    'due_charges' => $request->due_charges_percentage,
                    'due_charges_amount' => $request->due_charges_amount,
                    'class_id' => $class_id,
                    'session_year_id' => $sessionYear->id,
                ]);

                $feeClassType = [];
                foreach ($request->compulsory_fees_type as $data) {
                    $feeClassType[] = array(
                        "fees_id" => $fees->id,
                        "class_id" => $class_id,
                        "fees_type_id" => $data['fees_type_id'],
                        "amount" => $data['amount'],
                        "optional" => 0,
                    );
                }

                if (!empty($request->optional_fees_type)) {
                    foreach ($request->optional_fees_type as $data) {
                        $feeClassType[] = array(
                            "fees_id" => $fees->id,
                            "class_id" => $class_id,
                            "fees_type_id" => $data['fees_type_id'],
                            "amount" => $data['amount'],
                            "optional" => 1,
                        );
                    }
                }

                if (count($feeClassType) > 0) {
                    $this->feesClassType->upsert($feeClassType, ['class_id', 'fees_type_id'], ['amount', 'optional']);
                }

                if ($request->include_fee_installments && count($request->fees_installments)) {
                    $installmentData = array();
                    foreach ($request->fees_installments as $data) {
                        $data = (object) $data;
                        $installmentData[] = array(
                            'name' => $data->name,
                            'due_date' => date('Y-m-d', strtotime($data->due_date)),
                            'due_charges_type' => $data->due_charges_type,
                            'due_charges' => $data->due_charges,
                            'fees_id' => $fees->id,
                            'session_year_id' => $sessionYear->id,
                            'installment_amount' => $data->amount
                        );
                    }
                    $this->feesInstallment->createBulk($installmentData);
                }
            }
            $students = $this->student->builder()
                ->whereHas('class_section', function ($q) use ($request) {
                    $q->whereIn('class_id', (array) $request->class_id);
                })
                ->where('session_year_id', $sessionYear->id)
                ->where(function ($q) {
                    $q->where('application_type', 'offline')
                        ->orWhere(function ($q) {
                            $q->where('application_type', 'online')
                                ->where('application_status', 1);
                        });
                })
                ->whereHas('user', function ($q) {
                    $q->where('status', 1);
                })
                ->get();
            $allPayloads = [];
            $title = 'Fees Added';
            $type = "fees";
            foreach ($students as $student) {

                // Guardian recipient (array because send_notification expects array of user IDs)
                $recipient = [$student->guardian_id];

                // Child info
                $childId = $student->id;
                $childName = trim(($student->user->full_name ?? ''));

                if ($childName === '') {
                    $childName = "Student #$childId";
                }

                // Notification text
                $body = "Dear {$childName}. New fees is added please pay the fees before due date.";

                // Custom payload
                $customData = [
                    'child_id' => $childId,
                ];

                // Send notification
                $payloads = buildPayloads($recipient, $title, $body, $type, $customData);
                $allPayloads = array_merge($allPayloads, $payloads);
            }

            DB::commit();
            sendBulk($allPayloads);
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            if (
                Str::contains($e->getMessage(), [
                    'does not exist',
                    'file_get_contents'
                ])
            ) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::rollback();
                ResponseService::logErrorResponse($e, "FeesController -> Store Method");
                ResponseService::errorResponse();
            }
        }
    }

    public function show()
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');
        $session_year_id = $this->cache->getSessionYear()->id;
        $medium_id = request('medium_id');

        $sql = $this->fees->builder()->with('installments', 'class:id,name,stream_id,medium_id,shift_id', 'class.medium:id,name', 'class.shift:id,name', 'class.stream:id,name', 'fees_class_type.fees_type:id,name')
            ->where('session_year_id', $session_year_id)
            ->where(function ($q) use ($search) {
                $q->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('name', 'LIKE', "%$search%")
                        ->orwhere('due_date', 'LIKE', "%$search%")
                        ->orwhere('due_charges', 'LIKE', "%$search%");
                });
            })
            ->when(!empty($showDeleted), function ($query) {
                $query->onlyTrashed();
            })->when($medium_id, function ($query) use ($medium_id) {
                $query->whereHas('class', function ($q) use ($medium_id) {
                    $q->where('medium_id', $medium_id);
                });
            });

        $total = $sql->count();
        if ($offset >= $total && $total > 0) {
            $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
            $offset = $lastPage;
        }
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = $offset + 1;
        foreach ($res as $row) {
            $operate = '';
            if ($showDeleted) {
                $operate .= BootstrapTableService::restoreButton(route('fees.restore', $row->id));
                $operate .= BootstrapTableService::trashButton(route('fees.trash', $row->id));
            } else {
                $operate .= BootstrapTableService::editButton(route('fees.edit', $row->id), false);
                $operate .= BootstrapTableService::deleteButton(route('fees.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['compulsory_fees'] = number_format($row->fees_class_type->filter(function ($data) {
                return $data->optional == 0;
            })->sum('amount'), 2);
            $tempRow['total_fees'] = number_format($row->fees_class_type->sum('amount'), 2);
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-edit');
        $classes = $this->class->all(['*'], ['stream', 'medium', 'stream']);
        $feesTypeData = $this->feesType->all();

        $fees = $this->fees->builder()->with(['fees_class_type', 'installments', 'class.medium'])->withCount('fees_paid')->findOrFail($id);

        return view('fees.edit', compact('classes', 'feesTypeData', 'fees'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenSendJson('Fees Management');
        ResponseService::noPermissionThenSendJson('fees-edit');

        $request->validate([
            'include_fee_installments' => 'required|boolean',
            'due_date' => 'required|date',
            'due_charges_percentage' => 'required|numeric',
            'due_charges_amount' => 'required|numeric',
            'compulsory_fees_type' => 'required|array',
            'compulsory_fees_type.*' => 'required|array',
            'compulsory_fees_type.*.fees_type_id' => 'required|numeric',
            'compulsory_fees_type.*.amount' => 'required|numeric',
            'optional_fees_type.*' => 'required|array',
            'optional_fees_type.*.fees_type_id' => 'required|numeric',
            'optional_fees_type.*.amount' => 'required|numeric',
            'fees_installments' => 'nullable|array',
            'fees_installments.*.name' => 'required',
            'fees_installments.*.due_date' => 'required|date',
            'fees_installments.*.due_charges' => 'required|numeric'
        ]);

        if ($request->include_fee_installments) {
            $totalInstallments = collect($request->fees_installments)->sum('amount');
            $totalCompulsoryFees = collect($request->compulsory_fees_type)->sum('amount');

            if ((float) $totalInstallments !== (float) $totalCompulsoryFees) {
                return ResponseService::errorRedirectResponse(route('fees.edit', $id), 'Total amount of Fees Installments is not equal to the total amount of Compulsory Fees');
            }
        }

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getSessionYear();

            // Fees Data Store
            $feesData = array(
                'name' => $request->name,
                'due_date' => $request->due_date,
                'due_charges' => $request->due_charges_percentage,
                'due_charges_amount' => $request->due_charges_amount
            );
            $fees = $this->fees->update($id, $feesData);

            foreach ($request->compulsory_fees_type as $data) {
                $feeClassType[] = array(
                    "id" => $data['id'],
                    "fees_id" => $fees->id,
                    "class_id" => $fees->class_id,
                    "fees_type_id" => $data['fees_type_id'],
                    "amount" => $data['amount'],
                    "optional" => 0,
                );
            }

            if (!empty($request->optional_fees_type)) {
                foreach ($request->optional_fees_type as $data) {
                    $feeClassType[] = array(
                        "id" => $data['id'],
                        "fees_id" => $fees->id,
                        "class_id" => $fees->class_id,
                        "fees_type_id" => $data['fees_type_id'],
                        "amount" => $data['amount'],
                        "optional" => 1,
                    );
                }
            }

            if (isset($feeClassType)) {
                $this->feesClassType->upsert($feeClassType, ['id'], ['fees_type_id', 'amount', 'optional']);
            }

            if (!empty($request->fees_installments)) {
                $installmentData = array();
                foreach ($request->fees_installments as $data) {
                    $data = (object) $data;
                    $installmentData[] = array(
                        'id' => $data->id,
                        'name' => $data->name,
                        'due_date' => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges_type' => $data->due_charges_type,
                        'due_charges' => $data->due_charges,
                        'fees_id' => $fees->id,
                        'session_year_id' => $sessionYear->id,
                        'installment_amount' => $data->amount
                    );
                }

                $this->feesInstallment->upsert($installmentData, ['id'], ['name', 'due_date', 'due_charges', 'due_charges_type', 'fees_id', 'session_year_id', 'installment_amount']);
            }

            $students = $this->student->builder()
                ->whereHas('class_section', function ($q) use ($fees) {
                    $q->whereIn('class_id', (array) $fees->class_id);
                })
                ->where('session_year_id', $sessionYear->id)
                ->where(function ($q) {
                    $q->where('application_type', 'offline')
                        ->orWhere(function ($q) {
                            $q->where('application_type', 'online')
                                ->where('application_status', 1);
                        });
                })
                ->whereHas('user', function ($q) {
                    $q->where('status', 1);
                })
                ->get();
            $allPayloads = [];
            $title = 'Fees Updated';
            $type = "fees";
            foreach ($students as $student) {

                // Guardian recipient (array because send_notification expects array of user IDs)
                $recipient = [$student->guardian_id];

                // Child info
                $childId = $student->id;
                $childName = trim(($student->user->full_name ?? ''));

                if ($childName === '') {
                    $childName = "Student #$childId";
                }

                // Notification text
                $body = "Dear {$childName}. Fees is upated please pay the fees before due date.";

                // Custom payload
                $customData = [
                    'child_id' => $childId,
                ];

                // Send notification
                $payloads = buildPayloads($recipient, $title, $body, $type, $customData);
                $allPayloads = array_merge($allPayloads, $payloads);
            }

            DB::commit();
            sendBulk($allPayloads);
            ResponseService::successRedirectResponse(route('fees.index'), 'Data Update Successfully');
        } catch (Throwable) {
            DB::rollback();
            ResponseService::errorRedirectResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenSendJson('fees-delete');
        try {
            DB::beginTransaction();
            $this->fees->deleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (\Exception $e) {
            DB::rollback();
            // Return validation error instead of generic error
            return ResponseService::errorResponse($e->getMessage());
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-delete');
        try {
            $this->fees->findOnlyTrashedById($id)->restore();
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function search(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            $data = $this->fees->builder()->where('session_year_id', $request->session_year_id)->get();
            ResponseService::successResponse("Data Restored Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-delete');
        try {
            $this->fees->findOnlyTrashedById($id)->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /* END : Fees Module */

    public function deleteInstallment($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            DB::beginTransaction();
            $this->feesInstallment->DeleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function deleteClassType($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            DB::beginTransaction();
            $this->feesClassType->DeleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function removeOptionalFees($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Fees Paid ID and Amount of Fees Transaction Table
            $optionalFeeData = $this->optionalFee->findById($id);
            $feesPaidId = $optionalFeeData->fees_paid_id;
            $optionalFeeAmount = $optionalFeeData->amount;

            $this->optionalFee->permanentlyDeleteById($id); // Permanently Delete Optional Fees Data

            // Check Fees Transactions Entry
            $feesPaidDataQuery = $this->feesPaid->builder()->where('id', $feesPaidId);
            if ($feesPaidDataQuery->count()) {
                // Get Fees Paid Data
                $feesPaidAmount = $feesPaidDataQuery->first()->amount; // Get Fees Paid Amount
                $finalAmount = $feesPaidAmount - $optionalFeeAmount; // Calculate Final Amount
                if ($finalAmount > 0) {
                    $this->feesPaid->update($feesPaidId, ['amount' => $finalAmount]); // Update Fees Paid Data with Final Amount
                } else {
                    $this->feesPaid->permanentlyDeleteById($feesPaidId);
                }
            } else {
                $this->feesPaid->permanentlyDeleteById($feesPaidId);
            }

            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function removeInstallmentFees($compulsoryFeesPaidID)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Fees Paid ID and Amount of Fees Transaction Table
            $installmentFeeTransaction = $this->compulsoryFee->findById($compulsoryFeesPaidID);
            $feesPaidId = $installmentFeeTransaction->fees_paid_id;
            $feesTransactionAmount = $installmentFeeTransaction->amount;

            $this->compulsoryFee->permanentlyDeleteById($compulsoryFeesPaidID); // Permanently Delete Fees Transaction Data

            // Check Fees Transactions Entry
            $feesPaidDataQuery = $this->feesPaid->builder()->where('id', $feesPaidId);
            if ($feesPaidDataQuery->count()) {
                // Get Fees Paid Data
                $feesPaidAmount = $feesPaidDataQuery->first()->amount; // Get Fees Paid Amount
                $finalAmount = $feesPaidAmount - $feesTransactionAmount; // Calculate Final Amount
                if ($finalAmount > 0) {
                    $this->feesPaid->update($feesPaidId, ['amount' => $finalAmount, 'is_fully_paid' => 0]); // Update Fees Paid Data with Final Amount
                } else {
                    $this->feesPaid->permanentlyDeleteById($feesPaidId);
                }
            } else {
                $this->feesPaid->permanentlyDeleteById($feesPaidId);
            }

            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feesConfigIndex()
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');

        // List of the names to be fetched
        $names = array('currency_code', 'currency_symbol',);

        $settings = $this->schoolSettings->getBulkData($names); // Passing the array of names and gets the array of data
        $domain = request()->getSchemeAndHttpHost(); // Get Current Web Domain

        $stripeData = $this->paymentConfigurations->all()->where('payment_method', 'stripe')->first();
        return view('fees.fees_config', compact('settings', 'domain', 'stripeData'));
    }

    public function feesConfigUpdate(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');
        $request->validate(['stripe_status' => 'required', 'stripe_publishable_key' => 'required_if:stripe_status,1|nullable', 'stripe_secret_key' => 'required_if:stripe_status,1|nullable', 'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable', 'stripe_webhook_url' => 'required_if:stripe_status,1|nullable', 'currency_code' => 'required|max:10', 'currency_symbol' => 'required|max:5',]);
        try {
            $this->paymentConfigurations->updateOrCreate(['payment_method' => strtolower('stripe')], ['api_key' => $request->stripe_publishable_key, 'secret_key' => $request->stripe_secret_key, 'webhook_secret_key' => $request->stripe_webhook_secret, 'status' => $request->stripe_status]);


            // Store Currency Code and Currency Symbol in School Settings
            $settings = array('currency_code', 'currency_symbol');

            $data = array();
            foreach ($settings as $row) {
                $data[] = [
                    "name" => $row,
                    "data" => $row == 'school_name' ? str_replace('"', '', $request->$row) : $request->$row,
                    "type" => "string"
                ];
            }

            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            Cache::flush();

            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feesTransactionsLogsIndex()
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $classes = $this->classes->builder()->orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'stream', 'sections')->get();
        $mediums = $this->medium->builder()->orderBy('id', 'ASC')->get();

        $months = sessionYearWiseMonthYear();

        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'months')));
    }

    public function feesTransactionsLogsList(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $sessionYearId = $this->cache->getSessionYear()->id;

        //Fetching Students Data on Basis of Class Section ID with Relation fees paid
        $sql = $this->paymentTransaction->builder()->with('user:id,first_name,last_name,image,email')->where(function ($query) use ($sessionYearId) {
            $query->whereHas('compulsory_fees.fees_paid.fees', function ($q) use ($sessionYearId) {
                $q->where('session_year_id', $sessionYearId);
            })->orWhereHas('optional_fees.fees_paid.fees', function ($q) use ($sessionYearId) {
                $q->where('session_year_id', $sessionYearId);
            })->orWhereHas('transportation_payment', function ($q) use ($sessionYearId) {
                $q->where('session_year_id', $sessionYearId);
            });
        });

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orwhere('order_id', 'LIKE', "%$search%")->orwhere('payment_id', 'LIKE', "%$search%")
                    ->orwhere('payment_gateway', 'LIKE', "%$search%")->orwhere('amount', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")->orwhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        if ($request->payment_status) {
            $sql = $sql->where('payment_status', $request->payment_status);
        }

        if ($request->month) {
            $month_year = explode('_', $request->month);
            $sql = $sql->whereRaw('MONTH(created_at) = ?', [$month_year[0]])->whereRaw('YEAR(created_at) = ?', [$month_year[1]]);
        }

        $total = $sql->count();
        if ($offset >= $total && $total > 0) {
            $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
            $offset = $lastPage;
        }
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /* START : Fees Paid Module */
    public function feesPaidListIndex()
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        // Fees Data With Few Selected Data
        $sessionYearId = $this->cache->getSessionYear()->id;
        $fees = $this->fees->builder()->select(['id', 'name', 'class_id'])->where('session_year_id', $sessionYearId)->get();
        $classes = $this->classes->all(['*'], ['medium', 'sections', 'shift']);
        //        $session_year_all = $this->sessionYear->builder()->where('default', 1)->get();
        $class_section = $this->classSection->builder()->with('class', 'class.stream', 'class.shift', 'section', 'medium')->get();
        $months = sessionYearWiseMonthYear();
        return response(view('fees.fees_paid', compact('fees', 'classes', 'months', 'class_section')));
    }

    public function feesPaidList(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $feesId = (int) request('fees_id');
        $sessionYearId = $this->cache->getSessionYear()->id;
        $class_section_id = request('class_section_id');
        $class_id = request('class_id');
        $settings = $this->cache->getSchoolSettings();


        $fees = null;
        if ($feesId) {
            $fees = $this->fees->findById($feesId, ['*'], [
                'fees_class_type.fees_type:id,name',
                'installments:id,name,due_date,due_charges,fees_id',
                'fees_paid' => function ($q) {
                    $q->withSum([
                        'compulsory_fee' => function ($q) {
                            $q->whereNull('deleted_at');
                        }
                    ], 'amount')
                        ->withSum([
                            'compulsory_fee' => function ($q) {
                                $q->whereNull('deleted_at');
                            }
                        ], 'due_charges')
                        ->withSum([
                            'optional_fee' => function ($q) {
                                $q->whereNull('deleted_at');
                            }
                        ], 'amount');
                }
            ]);

            $sql = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name', 'email', 'image');

            $requestedSessionYearId = $fees->session_year_id;
            $defaultSessionYear = $this->cache->getDefaultSessionYear();
            $isCurrentSession = ($defaultSessionYear->id == $sessionYearId);

            if ($isCurrentSession) {
                $sql->with([
                    'student' => function ($query) use ($sessionYearId) {
                        $query->select('id', 'class_section_id', 'user_id', 'session_year_id')->with([
                            'class_section' => function ($query) {
                                $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id,stream_id', 'section:id,name', 'medium:id,name', 'class.shift:id,name', 'class.stream:id,name');
                            }
                        ])->where('session_year_id', $sessionYearId);
                    }
                ])->whereHas('student', function ($q) use ($sessionYearId, $fees, $class_section_id, $class_id) {
                    $q->where('session_year_id', $sessionYearId)
                        ->whereHas('class_section', function ($q) use ($fees, $class_section_id, $class_id) {
                            $q->where('class_id', $fees->class_id);

                            if ($class_id) {
                                $q->where('class_id', $class_id); // optional if same as above
                            }
                            if ($class_section_id) {
                                $q->where('id', $class_section_id);
                            }
                        });
                });
            } else {
                $sql->with([
                    'student' => function ($query) {
                        $query->withoutGlobalScopes();
                    },
                    'promote_student' => function ($query) use ($requestedSessionYearId) {
                        $query->where(function ($q) use ($requestedSessionYearId) {
                            $q->where('session_year_id', $requestedSessionYearId)
                                ->orWhere('session_year_id', $requestedSessionYearId);
                        })->with([
                            'class_section' => function ($query) {
                                $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id,stream_id', 'section:id,name', 'medium:id,name', 'class.shift:id,name', 'class.stream:id,name');
                            },
                            'class_section' => function ($query) {
                                $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id,stream_id', 'section:id,name', 'medium:id,name', 'class.shift:id,name', 'class.stream:id,name');
                            }
                        ]);
                    }
                ])->whereHas('promote_student', function ($q) use ($requestedSessionYearId, $fees, $class_section_id, $class_id) {
                    $q->where(function ($query) use ($requestedSessionYearId, $fees, $class_section_id, $class_id) {
                        $query->where('session_year_id', $requestedSessionYearId)
                            ->whereHas('class_section', function ($q) use ($fees, $class_section_id, $class_id) {
                                $q->where('class_id', $fees->class_id);
                                if ($class_id) $q->where('class_id', $class_id);
                                if ($class_section_id) $q->where('id', $class_section_id);
                            });
                    })->orWhere(function ($query) use ($requestedSessionYearId, $fees, $class_section_id, $class_id) {
                        $query->where('session_year_id', $requestedSessionYearId)
                            ->whereHas('class_section', function ($q) use ($fees, $class_section_id, $class_id) {
                                $q->where('class_id', $fees->class_id);
                                if ($class_id) $q->where('class_id', $class_id);
                                if ($class_section_id) $q->where('id', $class_section_id);
                            });
                    });
                });
            }

            $sql->with([
                'optional_fees' => function ($query) {
                    $query->with('fees_class_type');
                },
                'fees_paid' => function ($q) use ($fees) {
                    $q->where('fees_id', $fees->id)->where('amount', '>', 0);
                },
                'compulsory_fees' => function ($q) use ($fees) {
                    $q->whereNull('deleted_at');
                }
            ])
                ->withSum([
                    'compulsory_fees' => function ($q) use ($fees) {
                        $q->whereNull('deleted_at')
                            ->whereHas('fees_paid', function ($q) use ($fees) {
                                $q->where('fees_id', $fees->id)
                                    ->whereNull('deleted_at');
                            });
                    }
                ], 'amount')
                ->withSum([
                    'compulsory_fees' => function ($q) use ($fees) {
                        $q->whereNull('deleted_at')
                            ->whereHas('fees_paid', function ($q) use ($fees) {
                                $q->where('fees_id', $fees->id)
                                    ->whereNull('deleted_at');
                            });
                    }
                ], 'due_charges');


            if (!empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where(function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")->orWhere('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
            }

            $currencySymbol = $settings['currency_symbol'] ?? '';

            $total_compulsory_fees = ($fees->total_compulsory_fees * $sql->count());
            $total_optional_fees = ($fees->total_optional_fees * $sql->count());
            $total_fees = $total_compulsory_fees + $total_optional_fees;
            $fees_data = [
                'total_fees' => $total_fees,
                'total_compulsory_fees' => $total_compulsory_fees,
                'total_optional_fees' => $total_optional_fees,
            ];
            $fees_data['currency_symbol'] = $currencySymbol;

            // Total Collected Fees
            if (count($fees->fees_paid)) {
                $total_compulsory_fees_collected = $fees->fees_paid->sum('compulsory_fee_sum_amount');
                $total_optional_fees_collected = $fees->fees_paid->sum('optional_fee_sum_amount');
                $total_fees_collected = $total_compulsory_fees_collected + $total_optional_fees_collected;
                $fees_data['total_fees_collected'] = $total_fees_collected;
                $fees_data['total_compulsory_fees_collected'] = $total_compulsory_fees_collected;
                $fees_data['total_optional_fees_collected'] = $total_optional_fees_collected;
                // compulsory_fees_sum_due_charges
                $fees_data['compulsory_fees_sum_due_charges'] = $fees->fees_paid->sum('compulsory_fee_sum_due_charges');
            }



            if ($request->paid_status == 0) {
                $sql->where(function ($query) use ($fees) {
                    $query->whereDoesntHave('fees_paid', function ($q) use ($fees) {
                        $q->where('fees_id', $fees->id);
                    })->orWhereHas('fees_paid', function ($q) use ($fees) {
                        $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 0, 'amount' => 0]);
                    });
                });
            } else {

                if ($request->paid_status == 1) {
                    $sql->whereHas('fees_paid', function ($q) use ($fees) {
                        $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 1]);
                    });
                } else {
                    $sql->whereHas('fees_paid', function ($q) use ($fees) {
                        $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 0]);
                    });
                }

                if ($request->month) {
                    $sql->whereHas('fees_paid', function ($q) use ($request, $fees) {
                        $q->whereMonth('date', $request->month)
                            ->where('fees_id', $fees->id);
                    });
                }

                if ($request->payment_gateway == 'cash_cheque') {
                    $sql->whereHas('fees_paid.compulsory_fee', function ($q) use ($request) {
                        $q->whereIn('mode', ['Cash', 'Cheque']);
                    });
                }

                if ($request->payment_gateway == 'stripe_razorpay') {
                    $sql->whereHas('fees_paid.compulsory_fee.payment_transaction', function ($q) use ($request) {
                        $q->whereIn('payment_gateway', ['Stripe', 'Razorpay', 'Flutterwave', 'Paystack']);
                    });
                }

                if ($request->online_offline_payment) {
                    $sql->whereHas('fees_paid.compulsory_fee', function ($q) use ($request) {
                        if ($request->online_offline_payment == 2) {
                            // Offline
                            $q->whereIn('mode', ['Cash', 'Cheque']);
                        } else if ($request->online_offline_payment == 1) {
                            // Online
                            $q->whereIn('mode', ['Stripe', 'Razorpay', 'Flutterwave', 'Paystack']);
                        }
                    });
                }
            }




            $total = $sql->count();
            if ($offset >= $total && $total > 0) {
                $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
                $offset = $lastPage;
            }
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $no = 1;

            foreach ($res as $row) {
                if (!$isCurrentSession) {
                    if ($row->promote_student && $row->promote_student->count() > 0) {
                        $ps = $row->promote_student->first();
                        $class_section = null;

                        if ($ps->session_year_id == $requestedSessionYearId) {
                            $class_section = $ps->class_section;
                        } else {
                            $class_section = $ps->current_class_section;
                        }

                        if ($class_section) {
                            $studentModel = new \App\Models\Students([
                                'id' => $row->student->id ?? null,
                                'class_section_id' => $class_section->id,
                                'user_id' => $row->id,
                                'session_year_id' => $requestedSessionYearId
                            ]);
                            $studentModel->setRelation('class_section', $class_section);
                            $row->setRelation('student', $studentModel);
                        }
                    } else {
                        $row->setRelation('student', null);
                    }
                    $row->unsetRelation('promote_student');
                }

                $tempRow = $row->toArray();
                $fees_data['no'] = $no++;
                $tempRow['no'] = $fees_data;


                // Calculate Minimum amount for installment
                if (count($fees->installments) > 0) {
                    collect($fees->installments)->map(function ($data) use ($fees) {
                        $data['minimum_amount'] = $fees->total_compulsory_fees / count($fees->installments);
                        $data['total_amount'] = $data['minimum_amount'] + 0; //Due charges
                        return $data;
                    });
                }
                $tempRow['fees'] = $fees->toArray();
                $tempRow['fees_status'] = null;
                $due_date = Carbon::parse($fees->due_date);
                $today_date = Carbon::now()->format('Y-m-d');

                if ($due_date->gt($today_date)) {
                    $tempRow['fees_status'] = null;
                } else {
                    $tempRow['fees_status'] = 2;
                }

                $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-right">';
                $operate .= '<a href="' . route('fees.compulsory.index', [$fees->id, $row->id]) . '" class="compulsory-data dropdown-item" title="' . trans('Compulsory Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Compulsory Fees') . '</a>';

                if (count($fees->optional_fees) > 0) {
                    $operate .= '<div class="dropdown-divider"></div><a href="' . route('fees.optional.index', [$fees->id, $row->id]) . '" class="optional-data dropdown-item" title="' . trans('Optional Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('Optional Fees') . '</a>';
                }
                $operate .= '</div></div>';

                if (!empty($row->fees_paid) && $row->fees_paid->amount) {
                    // $operate .= ($fees->session_year_id == $sessionYearId) ? $operate : "";
                    $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', [$row->fees_paid->fees_id, $row->id]), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $row->fees_paid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                    $tempRow['fees_status'] = $row->fees_paid->is_fully_paid;
                }

                // if (!empty($row->fees_paid->is_fully_paid)) {
                //     $operate .= ($fees->session_year_id == $sessionYearId) ? $operate : "";
                //     $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', $row->fees_paid->id), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $row->fees_paid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                //     $tempRow['fees_status'] = $row->fees_paid->is_fully_paid;
                // }

                if ($row->fees_paid && $row->fees_paid->amount) {
                    // $tempRow['paid_amount'] = $row->compulsory_fees_sum_amount + $row->compulsory_fees_sum_due_charges;
                    $tempRow['paid_amount'] = number_format($row->compulsory_fees_sum_amount, 2);
                    // compulsory_fees_sum_due_charges
                    $tempRow['due_charges'] = number_format($row->compulsory_fees_sum_due_charges, 2);
                } else {
                    $tempRow['paid_amount'] = 0;
                }
                if ($row->fees_paid && $row->fees_paid->amount && isset($row->fees_paid->compulsory_fee[0]->mode)) {
                    $tempRow['payment_method'] = $row->fees_paid->compulsory_fee[0]->mode;
                }

                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }


        $bulkData['total'] = 0;
        $bulkData['rows'] = $tempRow = [];
        return response()->json($bulkData);
    }

    public function feesPaidReceiptPDF($feesId, $studentId)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {

            return $this->userService->generateSchoolFeesReceiptPDF($feesId, $studentId, 'panel');

            // $sessionYear = $this->cache->getSessionYear();
            // $feesPaid = $this->feesPaid->builder()->where('id', $feesPaidId)
            //     ->with([
            //         'fees.fees_class_type.fees_type',
            //         'fees.session_year',
            //         'compulsory_fee.installment_fee:id,name',
            //         'compulsory_fee.fees_paid.fees',
            //         'optional_fee' => function ($q) {
            //             $q->with([
            //                 'fees_class_type' => function ($q) {
            //                     $q->select('id', 'fees_type_id')->with('fees_type:id,name');
            //                 }
            //             ]);
            //         },
            //         'compulsory_fee' => function ($q) {
            //             $q->whereNull('deleted_at')
            //                 ->with([
            //                     'installment_fee:id,name',
            //                     'fees_paid.fees'
            //                 ]);
            //         },
            //     ])->firstOrFail();

            // $student = $this->student->builder()->where('session_year_id', $sessionYear->id)->with('user:id,first_name,last_name', 'class_section.class.stream', 'class_section.class.shift', 'class_section.section', 'class_section.medium')->whereHas('user', function ($q) use ($feesPaid) {
            //     $q->where('id', $feesPaid->student_id);
            // })->firstOrFail();

            // $school = $this->cache->getSchoolSettings();

            // $data = explode("storage/", $school['horizontal_logo'] ?? '');
            // $school['horizontal_logo'] = end($data);

            // if ($school['horizontal_logo'] == null) {
            //     $systemSettings = $this->cache->getSystemSettings();
            //     $data = explode("storage/", $systemSettings['horizontal_logo'] ?? '');
            //     $school['horizontal_logo'] = end($data);
            // }

            // $pdf = Pdf::loadView('fees.fees_receipt', compact('school', 'feesPaid', 'student'));
            // return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable $e) {
            ResponseService::errorRedirectResponse();
            return false;
        }
    }

    private function getPaymentMetadata($fees, $studentID)
    {
        $min_payable_amount = 0;
        $total_payable_amount = 0;
        $overdue_installment_ids = [];

        // Fetch current advance amount
        $last_transaction = $this->compulsoryFee->builder()
            ->where('student_id', $studentID)
            ->whereHas('fees_paid', function ($q) use ($fees) {
                $q->where('fees_id', $fees->id);
            })
            ->orderBy('id', 'DESC')
            ->first();
        $student_advance = $last_transaction ? $last_transaction->advance_amount : 0;

        $due_date_fees = Carbon::parse($fees->getRawOriginal('due_date'));
        $is_fees_overdue = $due_date_fees->isPast() && !$due_date_fees->isToday();


        if (count($fees->installments) > 0) {
            $sorted_installments = $fees->installments->sortBy('due_date');
            $overdue_amount = 0;
            $next_due_amount = 0;
            $found_next_due = false;
            $first_unpaid_future_id = null;

            foreach ($sorted_installments as $installment) {
                // Determine Overdue Status based on Real Time
                $is_overdue = false;
                $due_date_obj = Carbon::parse($installment->due_date);
                if ($due_date_obj->isPast() && !$due_date_obj->isToday()) {
                    $is_overdue = true;
                }

                // Calculate Paid Amount for this specific installment
                $paid_installment_amount = $this->compulsoryFee->builder()->whereNull('deleted_at')->where('installment_id', $installment->id)->where('student_id', $studentID)->sum('amount');
                $paid_due_charges = $this->compulsoryFee->builder()->whereNull('deleted_at')->where('installment_id', $installment->id)->where('student_id', $studentID)->sum('due_charges');

                // Base Amount
                $inst_amount = $installment->installment_amount > 0 ? $installment->installment_amount : ($fees->total_compulsory_fees / $fees->installments->count());

                // Due Charges Logic
                $inst_due_charges_amount = 0;

                // Check if due date passed
                if ($due_date_obj->isPast() && !$due_date_obj->isToday()) {
                    if ($installment->due_charges_type == "percentage") {
                        $inst_due_charges_amount = ($inst_amount * $installment->due_charges) / 100;
                    } else if ($installment->due_charges_type == "fixed") {
                        $inst_due_charges_amount = $installment->due_charges;
                    }
                }

                // Total Expected
                $total_expected = $inst_amount + $inst_due_charges_amount;

                // Total Paid
                $total_paid_already = $paid_installment_amount + $paid_due_charges;

                $balance_due = max(0, $total_expected - $total_paid_already);

                if ($balance_due > 0.01) { // Unpaid
                    $total_payable_amount += $balance_due;

                    if ($is_overdue) {
                        $overdue_amount += $balance_due;
                        $overdue_installment_ids[] = $installment->id;
                    } elseif (!$found_next_due) {
                        $next_due_amount = $balance_due;
                        $first_unpaid_future_id = $installment->id;
                        $found_next_due = true;
                    }
                }
            }

            if ($overdue_amount > 0) {
                $min_payable_amount = $overdue_amount;

                // Requirement: If Overdue, Pay Overdue + Next Installment
                if ($next_due_amount > 0) {
                    $min_payable_amount += $next_due_amount;
                    if ($first_unpaid_future_id) {
                        $overdue_installment_ids[] = $first_unpaid_future_id;
                    }
                }
            } else {
                $min_payable_amount = $next_due_amount;
                if ($first_unpaid_future_id) {
                    $overdue_installment_ids[] = $first_unpaid_future_id;
                }
            }
        } else {
            // Non-Installment Mode
            $total_payable_amount = $fees->total_compulsory_fees;

            // Due Charges
            $total_c = 0;
            if ($is_fees_overdue) {
                $total_c = $fees->due_charges_amount; // Use stored amount logic or calculation? Index uses due_charges_amount.
                // $total_c = ($fees->total_compulsory_fees * $fees->due_charges) / 100; 
                if ($total_c == 0 && $fees->due_charges > 0) {
                    // Fallback if due_charges_amount is 0 but percentage exists
                    $total_c = ($fees->total_compulsory_fees * $fees->due_charges) / 100;
                }

                $total_payable_amount += $total_c;
            }

            // Paid
            $paid_total = 0;
            // Calculate total paid for this fees (globally)
            $paid_total = $this->compulsoryFee->builder()->where('student_id', $studentID)
                ->whereHas('fees_paid', function ($q) use ($fees) {
                    $q->where('fees_id', $fees->id);
                })->sum(DB::raw('amount + due_charges'));

            $total_payable_amount = max(0, $total_payable_amount - $paid_total);

            if ($is_fees_overdue) {
                $min_payable_amount = $total_payable_amount;
            } else {
                $min_payable_amount = 0;
            }
        }

        return [
            'min_payable_amount' => $min_payable_amount,
            'total_payable_amount' => $total_payable_amount,
            'overdue_installment_ids' => $overdue_installment_ids,
            'student_advance' => $student_advance
        ];
    }

    public function payCompulsoryFeesIndex($feesID, $studentID)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        //        ResponseService::noPermissionThenRedirect('fees-edit');
        $fees = $this->fees->findById($feesID, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,due_charges_type,fees_id,installment_amount']);
        $oneInstallmentPaid = false;

        $student = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'class_section_id', 'user_id', 'guardian_id')->with([
                        'class_section' => function ($query) {
                            $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id,stream_id', 'class.shift:id,name', 'class.stream:id,name', 'section:id,name', 'medium:id,name');
                        }
                    ]);
                },
                // 'fees_paid' => function ($q) use ($feesID) {
                //     $q->where('fees_id', $feesID)->withSum('compulsory_fee', 'amount')->with('compulsory_fee');
                // },
                'fees_paid' => function ($q) use ($feesID) {
                    $q->where('fees_id', $feesID)
                        ->with([
                            'compulsory_fee' => function ($q) {
                                $q->whereNull('deleted_at');
                            }
                        ])
                        ->withSum([
                            'compulsory_fee as compulsory_fee_sum' => function ($q) {
                                $q->whereNull('deleted_at');
                            }
                        ], 'amount');
                },
                'compulsory_fees.advance_fees'
            ])->findOrFail($studentID);



        $isFullyPaid = false;
        $feesPaidId = null;
        if (!empty($student->fees_paid)) {
            // ResponseService::successRedirectResponse(route('fees.paid.index'), 'Compulsory Fees Already Paid');

            $feesPaidId = $student->fees_paid->id;
            if ($student->fees_paid->is_fully_paid) {
                $isFullyPaid = true;
            }
        }
        $installment_status = 0;
        if (count($fees->installments) > 0) {
            $installment_status = 1;
            $totalFeesAmount = $fees->total_compulsory_fees;
            $totalInstallments = count($fees->installments);

            collect($fees->installments)->map(function ($installment) use ($student, &$totalFeesAmount, &$totalInstallments, $fees, &$oneInstallmentPaid, $isFullyPaid) {
                // Calculate total paid for this installment
                $installmentPaidAmount = $student->compulsory_fees->whereNull('deleted_at')->where('installment_id', $installment->id)->sum('amount');

                $dueCharges = $student->compulsory_fees->whereNull('deleted_at')->where('installment_id', $installment->id)->sum('due_charges');

                $installmentPaidAmount = $installmentPaidAmount + $dueCharges;
                $installment['paid_amount'] = $installmentPaidAmount;

                // Determine Status
                $installment['status'] = 0; // 0: Unpaid, 1: Paid, 2: Partial
                if ($installmentPaidAmount >= $installment->total_amount) { // Assuming total_amount is available/calculated? 
                    // Wait, total_amount for installment checks Minimum + Due Charges? 
                    // The loop below calculates 'total_amount'. We need to be careful with ordering.
                    // Let's use minimum_amount logic first? 
                    // Actually, let's use the logic below to determine standard amounts first.
                }


                // RE-ORDERING LOGIC: Calculate Amounts FIRST, then check Payment Status

                if ($isFullyPaid || $installmentPaidAmount >= $installment['installment_amount']) {
                    $installment['due_charges_amount'] = 0;
                } else if (new DateTime(date('Y-m-d')) > new DateTime($installment['due_date'])) {
                    if ($installment->due_charges_type == "percentage") {
                        $installment['due_charges_amount'] = ($installment['installment_amount'] * $installment->due_charges) / 100;
                    } else if ($installment->due_charges_type == "fixed") {
                        $installment['due_charges_amount'] = $installment->due_charges;
                    }
                } else {
                    $installment['due_charges_amount'] = 0;
                }

                if ($installment['due_charges_amount'] == 0) {
                    $installment['due_charges_amount'] = $dueCharges;
                }

                // Note: The original code calculated 'minimum_amount' dynamically based on remaining/total.
                // But if we want consistent pricing, we should rely on 'installment_amount'.
                // Original Code: $installment['minimum_amount'] = $totalFeesAmount / $totalInstallments; (This logic handles dynamic splitting if some are paid)
                // But this dynamic logic is tricky if "Partial".
                // Let's stick to: Minimum = Stored Installment Amount.
                // If the user wants "Waterfall" dynamic adjustment, it creates complexity in labeling "Original Amount".
                // User Requirement: "Original Installment Amount | Paid | Balance". This implies fixed amounts.
                $installment['minimum_amount'] = $installment['installment_amount'];
                $installment['total_expected_amount'] = $installment['installment_amount'] + $installment['due_charges_amount']; // minimum + charges

                // Status Logic
                if ($installmentPaidAmount >= $installment['total_expected_amount']) {
                    $installment['status'] = 1; // Fully Paid
                    $installment['is_paid'] = $student->compulsory_fees->whereNull('deleted_at')->where('installment_id', $installment->id)->first();
                    $oneInstallmentPaid = true;
                    $totalFeesAmount -= $installmentPaidAmount; // Deduced actual paid
                    --$totalInstallments;
                } elseif ($installmentPaidAmount > 0) {
                    $installment['status'] = 2; // Partial
                    $oneInstallmentPaid = true;
                } else {
                    $installment['status'] = 0; // Unpaid
                }

                // Override if Global Full Payment
                if ($isFullyPaid) { // Accessing key from outside? No, need to pass it or check student->fees_paid
                    $installment['status'] = 1;
                    $installment['paid_amount'] = $installment['total_expected_amount'];
                    $installment['balance_due'] = 0;
                } else {
                    if ($installment->id == 50) {
                        // dd($installment['total_expected_amount'], $installmentPaidAmount, $dueCharges);
                    }
                    $installment['balance_due'] = max(0, ($installment['total_expected_amount']) - $installmentPaidAmount);
                }

                // Inject Transactions for History View
                $installment['transactions'] = $student->compulsory_fees->whereNull('deleted_at')->where('installment_id', $installment->id)->values();

                $installment['total_paid_amount'] = $student->compulsory_fees->whereNull('deleted_at')->where('installment_id', $installment->id)->sum('amount');

                $installment['total_amount'] = $installment['total_expected_amount'];
                $fees->remaining_amount = $totalFeesAmount; // Note: This might need adjustment if Partial payments exist.
                // Ideally remaining_amount should vary.

                return $installment;
            });
        }

        $due_charges = 0;
        $due_date = Carbon::createFromFormat('Y-m-d', $fees->getRawOriginal('due_date'));
        if ($due_date->isPast() && !$due_date->isToday() && $isFullyPaid != 1 && count($fees->installments) == 0) {
            $due_charges = $fees->due_charges_amount;
        }


        // Calculate Minimum Payable Amount AND Total Payable Amount
        $metaData = $this->getPaymentMetadata($fees, $studentID);
        $min_payable_amount = $metaData['min_payable_amount'];
        $total_payable_amount = $metaData['total_payable_amount'];
        $overdue_installment_ids = $metaData['overdue_installment_ids'];
        $student_advance = $metaData['student_advance'];

        $fees->complusory_details = $student->compulsory_fees->whereNull('deleted_at')->where('fees_paid_id', $feesPaidId);


        // Use the robustly calculated total_payable_amount which includes all installment-specific due charges
        $payFullFeesAmountDueCharges = $total_payable_amount;

        if ($fees->include_fee_installments) {
            if ($oneInstallmentPaid == 1) {
                $instalmentFeesFullPaid = $fees->installments->sum('balance_due');
            } else {
                $instalmentFeesFullPaid = $fees->remaining_amount;
            }
        } else {
            $instalmentFeesFullPaid = $fees->total_compulsory_fees;
        }

        if ($fees->due_date && Carbon::parse($fees->getRawOriginal('due_date'))->lt(Carbon::today())) {
            $instalmentFeesFullPaid += $fees->due_charges_amount;
        }

        $currencySymbol = $this->cache->getSchoolSettings('currency_symbol');
        return view('fees.pay-compulsory', compact('fees', 'student', 'oneInstallmentPaid', 'currencySymbol', 'isFullyPaid', 'due_charges', 'installment_status', 'student_advance', 'min_payable_amount', 'total_payable_amount', 'overdue_installment_ids', 'payFullFeesAmountDueCharges', 'instalmentFeesFullPaid'));
    }

    public function deleteTransaction($id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();
            $transaction = $this->compulsoryFee->findById($id);
            $feesPaidId = $transaction->fees_paid_id;

            // Delete the transaction
            $transactionType = $transaction->type;
            $transaction->delete();

            // Recalculate FeesPaid Summary
            if ($feesPaidId) {
                $feesPaid = $this->feesPaid->findById($feesPaidId);

                // If the deleted transaction was a Full Payment (Type 1), force generic reset
                if ($transactionType == 1) {
                    $feesPaid->update([
                        'amount' => 0,
                        'is_fully_paid' => 0,
                        'is_used_installment' => 1 // Revert to Installment Mode
                    ]);
                } else {
                    // Standard recalculation for Installment Payments
                    $total_paid = $this->compulsoryFee->builder()->whereNull('deleted_at')->where('fees_paid_id', $feesPaidId)->sum('amount');

                    // Recalculate fully paid status
                    $fees = $this->fees->findById($feesPaid->fees_id, ['*'], ['fees_class_type']);

                    // Calc total due charges paid
                    $total_due_charges_paid = $this->compulsoryFee->builder()->whereNull('deleted_at')->where('fees_paid_id', $feesPaidId)->sum('due_charges');

                    $is_fully_paid = $total_paid >= ($fees->total_compulsory_fees + $total_due_charges_paid);

                    $feesPaid->update([
                        'amount' => $total_paid,
                        'is_fully_paid' => $is_fully_paid
                    ]);
                }
            }

            DB::commit();
            ResponseService::successResponse("Transaction Deleted Successfully");
        } catch (\Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> deleteTransaction method');
            ResponseService::errorResponse();
        }
    }

    public function payCompulsoryFeesStore(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        $request->validate([
            'fees_id' => 'required|numeric',
            'student_id' => 'required|numeric',
            'installment_mode' => 'required|boolean', // 0=Full, 1=Installment
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
        ]);


        try {
            DB::beginTransaction();

            // 1. Fetch Fees & Installments
            $fees = $this->fees->findById($request->fees_id, ['*'], ['installments', 'fees_class_type']);
            $student_id = $request->student_id;
            $input_amount = $request->amount;


            // Fetch the last paid compulsory fee to get the advance amount
            $last_transaction = $this->compulsoryFee->builder()
                ->where('student_id', $student_id)
                ->whereHas('fees_paid', function ($q) use ($request) {
                    $q->where('fees_id', $request->fees_id);
                })
                ->orderBy('id', 'DESC')
                ->first();

            $existing_advance = $last_transaction ? $last_transaction->advance_amount : 0;
            $total_available = $input_amount + $existing_advance;

            // 3. Get or Create FeesPaid Record (Summary Table)
            $feesPaid = $this->feesPaid->builder()->where(['fees_id' => $fees->id, 'student_id' => $student_id])->first();
            if (empty($feesPaid)) {
                $feesPaid = $this->feesPaid->create([
                    'fees_id' => $fees->id,
                    'student_id' => $student_id,
                    // 'class_id' => $fees->class_id,
                    'is_fully_paid' => 0,
                    'is_used_installment' => $request->installment_mode,
                    'amount' => 0,
                    'date' => date('Y-m-d'),
                    // 'session_year_id' => $fees->session_year_id,
                    'school_id' => $fees->school_id,
                ]);
            }

            // Check if already fully paid
            if ($feesPaid->is_fully_paid) {
                ResponseService::errorResponse("Compulsory Fees already fully Paid");
            }

            $used_advance = 0; // Track how much advance represents in this payment
            $original_total_available = $total_available;

            // --- Server-Side Validation for Minimum & Maximum Payment ---
            $metaData = $this->getPaymentMetadata($fees, $student_id);
            $min_payable_server = $metaData['min_payable_amount'];
            $max_payable_server = $metaData['total_payable_amount'];
            $existing_advance = $metaData['student_advance']; // Use metadata advance to be consistent
            $total_available = $input_amount + $existing_advance;


            // Validate Input
            // Logic: Amount + Advance >= Minimum
            if ((($total_available + 0.01) < $min_payable_server) && $request->installment_mode == 1) { // Added epsilon
                throw new \Exception("Insufficient payment. You must pay at least " . number_format($min_payable_server, 2) . " (including advance).");
            }

            if (($total_available > ($max_payable_server + 1.00)) && $request->installment_mode == 1) { // Tolerance of 1.00 for rounding issues?
                throw new \Exception("Overpayment not allowed. You are paying more than total outstanding dues (" . number_format($max_payable_server, 2) . ").");
            }

            // 4. Payment Logic
            // Fetch all installments sorted by due date
            $installments = $fees->installments->sortBy('due_date');

            if ($installments->count() > 0 && $request->installment_mode == 1) {
                // --- INSTALLMENT LOGIC (Waterfall) ---
                $last_installment_id = $installments->last()->id;

                foreach ($installments as $installment) {
                    if ($total_available <= 0) {
                        break; // No more money
                    }

                    // 1. Calculate Total Liability (Base + Due Charges)
                    $installment_amount = $installment->installment_amount > 0 ? $installment->installment_amount : ($fees->total_compulsory_fees / $fees->installments->count());

                    // Calculate Due Charges (Penalty)
                    $total_due_charges = 0;
                    if (date('Y-m-d') > $installment->getRawOriginal('due_date')) {
                        if ($installment->due_charges_type == "percentage") {
                            $total_due_charges = ($installment_amount * $installment->due_charges) / 100;
                        } else if ($installment->due_charges_type == "fixed") {
                            $total_due_charges = $installment->due_charges;
                        }
                    }

                    // 2. Fetch Already Paid Amounts (Separated)
                    $paid_base = $this->compulsoryFee->builder()
                        ->where('fees_paid_id', $feesPaid->id)
                        ->where('installment_id', $installment->id)
                        ->whereNull('deleted_at')
                        ->sum('amount');

                    $paid_due_charges = $this->compulsoryFee->builder()
                        ->where('fees_paid_id', $feesPaid->id)
                        ->where('installment_id', $installment->id)
                        ->whereNull('deleted_at')
                        ->sum('due_charges');

                    // 3. Calculate Pending Amounts
                    $pending_base = max(0, $installment_amount - $paid_base);
                    $pending_due_charges = max(0, $total_due_charges - $paid_due_charges);

                    // If fully paid, skip
                    if ($pending_base <= 0.01 && $pending_due_charges <= 0.01) {
                        continue;
                    }

                    // 4. Allocate Funds (Priority: Due Charges -> Base Amount)
                    $allocate_due_charges = 0;
                    $allocate_base = 0;

                    // Pay off Due Charges first
                    if ($total_available >= $pending_due_charges) {
                        $allocate_due_charges = $pending_due_charges;
                        $total_available -= $pending_due_charges;
                    } else {
                        $allocate_due_charges = $total_available;
                        $total_available = 0;
                    }

                    // Pay off Base Amount next (if money left)
                    if ($total_available > 0) {
                        if ($total_available >= $pending_base) {
                            $allocate_base = $pending_base;
                            $total_available -= $pending_base;
                        } else {
                            $allocate_base = $total_available;
                            $total_available = 0;
                        }
                    }

                    // 5. Check if we need to record a transaction
                    if ($allocate_base > 0 || $allocate_due_charges > 0) {
                        // Create Payment Record
                        $this->compulsoryFee->create([
                            'student_id' => $student_id,
                            'fees_paid_id' => $feesPaid->id,
                            'type' => 2, // Installment Payment
                            'installment_id' => $installment->id,
                            'mode' => $request->mode,
                            'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                            'amount' => $allocate_base, // Stores ONLY Base Amount
                            'due_charges' => $allocate_due_charges, // Stores ONLY Penalty
                            'date' => date('Y-m-d', strtotime($request->date)),
                            'status' => 1,
                            'school_id' => $fees->school_id
                        ]);
                    }

                    // Last Installment Overpayment Check
                    if ($installment->id == $last_installment_id && $total_available > 0.01) {
                        throw new \Exception("Overpayment is not allowed for the final installment. Surplus: " . $total_available);
                    }
                }
            } else {
                // --- NON-INSTALLMENT LOGIC (Full Year / Partial) ---

                // 1. Calculate Total Liability
                $total_fee = $fees->total_compulsory_fees;
                $total_due_charges = 0;

                // Check Main Due Date
                if (date('Y-m-d') > $fees->getRawOriginal('due_date')) {
                    // Calculating Penalty (Assuming Percentage as per Schema)
                    $total_due_charges = ($total_fee * $fees->due_charges) / 100;
                }

                // 2. Fetch Already Paid Sums
                $paid_base = $this->compulsoryFee->builder()->where('fees_paid_id', $feesPaid->id)->sum('amount');
                $paid_due_charges = $this->compulsoryFee->builder()->where('fees_paid_id', $feesPaid->id)->sum('due_charges');

                // 3. Calculate Pending Amounts
                $pending_base = max(0, $total_fee - $paid_base);
                $pending_due_charges = max(0, $total_due_charges - $paid_due_charges);

                // Overpayment Validation
                // Allowed to pay = All Pending Base + All Pending Charges
                $max_payable = $pending_base + $pending_due_charges;

                if ($total_available > ($max_payable + 0.01)) { // Strict check with minimal float tolerance
                    throw new \Exception("Overpayment not allowed. Max Payable: " . number_format($max_payable, 2));
                }

                // 4. Allocate Funds
                $allocate_due_charges = 0;
                $allocate_base = 0;

                // Pay Due Charges First
                if ($total_available >= $pending_due_charges) {
                    $allocate_due_charges = $pending_due_charges;
                    $total_available -= $pending_due_charges;
                } else {
                    $allocate_due_charges = $total_available;
                    $total_available = 0;
                }

                // Pay Base Amount
                if ($total_available > 0) {
                    $allocate_base = min($total_available, $pending_base);
                    $total_available -= $allocate_base;
                }

                // Create Transaction (Type 1)
                $this->compulsoryFee->create([
                    'student_id' => $student_id,
                    'fees_paid_id' => $feesPaid->id,
                    'type' => 1, // Full/Partial Payment (Non-Installment)
                    'mode' => $request->mode,
                    'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                    'amount' => $allocate_base,
                    'due_charges' => $allocate_due_charges,
                    'date' => date('Y-m-d', strtotime($request->date)),
                    'status' => 1,
                    'school_id' => $fees->school_id
                ]);

                // Update Advance? 
                // Non-installment usually doesn't have "Next Installment" logic, so surplus shouldn't exist due to validation.
                // But if $total_available > 0 (e.g. fractional), it's lost or stored as advance?
                // Validation prevents big surplus.
            }

            // 5. Update Advance Amount on the LAST transaction created in this session
            // We need to fetch the record we just created.
            $latest_transaction = $this->compulsoryFee->builder()->where('fees_paid_id', $feesPaid->id)->orderBy('id', 'DESC')->first();
            if ($latest_transaction) {
                // $latest_transaction->advance_amount = $total_available; // Surplus
                $latest_transaction->save();
            } else {
                // Should not happen if amount > 0/
            }


            // 6. Update FeesPaid Summary
            $total_paid = $this->compulsoryFee->builder()->whereNull('deleted_at')->where('fees_paid_id', $feesPaid->id)->sum('amount');
            // Check if fully paid
            // Note: Simplistic check. Ideally should verify if all installments are cleared or total amount met.
            $is_fully_paid = $total_paid >= $fees->total_compulsory_fees;

            $feesPaid->update([
                'amount' => $total_paid,
                'is_fully_paid' => $is_fully_paid
            ]);


            // Send notification to guardian
            $student = $this->student->builder()->where('user_id', $student_id)->first();
            $currencySymbol = $this->cache->getSchoolSettings('currency_symbol');
            $user[] = $student->guardian_id;
            if ($user) {
                $paymentType = 'Compulsory Fees Payment';
                $title = 'Fees Payment Successful';
                $body = "Your payment of " . $currencySymbol . number_format($input_amount, 2) . " for " . $paymentType . " was successful.";
                $type = "payment";

                send_notification($user, $title, $body, $type);
            }

            DB::commit();
            ResponseService::successResponse("Data Updated SuccessFully");
        } catch (\Exception $e) {
            DB::rollback();

            dd($e);
            ResponseService::errorResponse($e->getMessage());
        } catch (Throwable $e) {
            DB::rollback();
            dd($e);
            ResponseService::logErrorResponse($e, 'FeesController -> payCompulsoryFeesStore method ');
            ResponseService::errorResponse();
        }
    }

    public function payOptionalFeesIndex($feesID, $studentID)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        //        ResponseService::noPermissionThenRedirect('fees-edit');
        // $fees = $this->fees->findById($feesID, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);

        $fees = $this->fees->findById($feesID, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);

        $student = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'class_section_id', 'user_id', 'session_year_id')->with([
                        'class_section' => function ($query) {
                            $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id,stream_id', 'class.shift:id,name', 'section:id,name', 'medium:id,name', 'class.stream');
                        }
                    ]);
                },
                'fees_paid' => function ($q) use ($feesID) {
                    $q->where('fees_id', $feesID)->first();
                }
            ])->findOrFail($studentID);


        $optionalFeesData = $this->feesClassType->builder()
            ->where('fees_id', $feesID)
            ->where(['class_id' => $student->student->class_section->class_id, 'optional' => 1])
            ->with([
                'fees_type',
                'optional_fees_paid' => function ($query) use ($student) {
                    $query->where('student_id', $student->id)->whereHas('fees_paid', function ($subQuery1) use ($student) {
                        $subQuery1->whereHas('fees', function ($subQuery2) use ($student) {
                            $subQuery2->where('session_year_id', $student->student->session_year_id);
                        });
                    });
                }
            ])
            ->get();

        $currencySymbol = $this->cache->getSchoolSettings('currency_symbol');

        return view('fees.pay-optional', compact('fees', 'student', 'optionalFeesData', 'currencySymbol'));
    }

    public function payOptionalFeesStore(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $request->validate([
            'fees_id' => 'required|numeric',
            'student_id' => 'required|numeric',
        ]);
        try {
            DB::beginTransaction();

            // First Store in Fees Paid table to get Fees Paid ID
            $feesPaid = $this->feesPaid->builder()->where([
                'fees_id' => $request->fees_id,
                'student_id' => $request->student_id
            ])->first();

            // If Fees Paid Doesn't Exists
            if (empty($feesPaid)) {
                $feesPaidResult = $this->feesPaid->create([
                    'date' => date('Y-m-d', strtotime($request->date)),
                    'is_fully_paid' => 0,
                    'is_used_installment' => 0,
                    'fees_id' => $request->fees_id,
                    'student_id' => $request->student_id,
                    'amount' => $request->total_amount,
                ]);
            } else {
                $feesPaidResult = $this->feesPaid->update($feesPaid->id, [
                    'amount' => $request->total_amount + $feesPaid->amount
                ]);
            }


            $optionalFeesPaymentData = array();

            // dd($feesPaidResult->id);
            // Loop to the Optional Fees
            if (!empty($request->fees_class_type)) {
                foreach ($request->fees_class_type as $key => $feesClassType) {
                    if (isset($feesClassType['id'])) {
                        $optionalFeesPaymentData[] = array(
                            'student_id' => $request->student_id,
                            'class_id' => $request->class_id,
                            'fees_class_id' => $feesClassType['id'],
                            'mode' => $request->mode,
                            'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                            'amount' => $feesClassType['amount'],
                            'fees_paid_id' => $feesPaidResult->id,
                            'date' => date('Y-m-d', strtotime($request->date)),
                            'status' => "Success",
                            'created_at' => now(),
                            'updated_at' => now()
                        );
                    }
                }
            }

            $this->optionalFee->createBulk($optionalFeesPaymentData);

            DB::commit();

            $student = $this->student->builder()->where('user_id', $request->student_id)->first();
            $currencySymbol = $this->cache->getSchoolSettings('currency_symbol');
            $user[] = $student->guardian_id;
            if ($user) {
                // Get fees name safely
                $paymentType = 'Optional Fees Payment';
                $title = 'Fees Payment Successful';
                $body = "Your payment of " . $currencySymbol . number_format($request->total_amount, 2) . " for " . $paymentType . " was successful.";
                $type = "payment";

                send_notification($user, $title, $body, $type);
            }


            ResponseService::successResponse("Data Updated SuccessFully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> compulsoryFeesPaidStore method ');
            ResponseService::errorResponse();
        }
    }
    /* END : Fees Paid Module */

    public function optionalFees(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        $sessionYear = $this->cache->getSessionYear();
        $class_section = $this->classSection->builder()->with('class', 'class.stream', 'class.shift', 'section', 'medium')->get();
        $feesClassTypes = FeesClassType::where('optional', '=', 1, 'and')
            ->whereHas('fees', function ($query) use ($sessionYear) {
                $query->where('session_year_id', $sessionYear->id);
            })
            ->with([
                'fees_type' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->get();

        return view('fees.optional-fees', compact('class_section', 'feesClassTypes'));
    }

    public function optionalFeesList(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $filter_optional_fees = (int) request('filter_optional_fees');
        $class_section_id = request('class_section_id');
        $sessionYearId = $this->cache->getSessionYear()->id;

        if ($filter_optional_fees) {
            $sql = $this->user->builder()
                ->role('Student')
                ->select('id', 'first_name', 'last_name', 'email', 'image')
                ->with([
                    'optional_fees' => function ($query) use ($filter_optional_fees) {
                        $query->where('fees_class_id', $filter_optional_fees)
                            ->with([
                                'fees_class_type' => function ($query) {
                                    $query->where(['optional' => 1]);
                                }
                            ]);
                    },
                    'fees_paid' => function ($q) use ($request, $sessionYearId) {
                        $q->withSum('optional_fee', 'amount')->whereHas('fees', function ($q) use ($sessionYearId) {
                            $q->where('session_year_id', $sessionYearId);
                        });
                    }
                ])
                ->whereHas('optional_fees', function ($query) use ($filter_optional_fees) {
                    $query->where('fees_class_id', $filter_optional_fees)
                        ->with([
                            'fees_class_type' => function ($query) {
                                $query->where('optional', 1);
                            }
                        ]);
                })
                ->whereHas('fees_paid.fees', function ($q) use ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                })
                ->when($class_section_id, function ($query) use ($class_section_id) {
                    $query->whereHas('student.class_section', function ($q) use ($class_section_id) {
                        $q->where('id', $class_section_id);
                    });
                });

            if (!empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql->where(function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")->orWhere('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
            }

            $total = $sql->count();
            if ($offset >= $total && $total > 0) {
                $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
                $offset = $lastPage;
            }
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $no = 1;

            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $fees_data['no'] = $no++;
                $tempRow['no'] = $fees_data;

                if (!empty($row->fees_paid)) {
                    $tempRow['fees_status'] = $row->fees_paid->is_fully_paid;
                }

                if ($row->optional_fees) {
                    $tempRow['optional_fees_amount'] = $row->optional_fees[0]->amount ?? 0;
                } else {
                    $tempRow['optional_fees_amount'] = 0;
                }

                if ($row->fees_paid && !empty($row->optional_fees[0]->mode)) {
                    $tempRow['payment_method'] = $row->optional_fees[0]->mode;
                }

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        $bulkData['total'] = 0;
        $bulkData['rows'] = $tempRow = [];
        return response()->json($bulkData);
    }

    public function feesOverDue($class_section_id)
    {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        try {
            // $sessionYear = $this->cache->getDefaultSessionYear();
            $class_id = $this->classSection->builder()->where('id', $class_section_id)->pluck('class_id')->toArray();

            // Ensure $class_id is a single value rather than an array if you expect a single class_id
            $class_id = reset($class_id);

            $today = Carbon::now()->format('Y-m-d');
            $student_ids = [];


            $sessionYearId = $this->cache->getSessionYear()->id;
            $fees = $this->fees->builder()->where('session_year_id', $sessionYearId)->whereDate('due_date', '<', $today)->with('installments:id,name,due_date,due_charges,fees_id')->where('class_id', $class_id)->get();

            foreach ($fees as $fee) {
                $sql = $this->user->builder()
                    ->role('Student')
                    ->select('id', 'first_name', 'last_name')->where('status', 1)
                    ->with([
                        'fees_paids' => function ($query) use ($fee) {
                            $query->where('fees_id', $fee->id);
                        },
                    ])->whereDoesntHave('fees_paids', function ($q) use ($fee) {
                        $q->where('fees_id', $fee->id);
                    })->orwhereHas('fees_paids', function ($query) use ($fee, $today) {
                        $query->where('fees_id', $fee->id)->where('is_fully_paid', 0)
                            ->where(function ($q) use ($fee, $today) {
                                $q->where('is_used_installment', true)
                                    ->whereHas('fees', function ($q) use ($today) {
                                        $q->whereHas('installments', function ($q) use ($today) {
                                            $q->whereDate('due_date', '<', $today);
                                        });
                                    });
                            });
                    });
                $student_ids = array_merge($student_ids, $sql->whereHas('student', function ($q) use ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                })->get()->pluck('id')->toArray());
            }
            $student_ids = array_unique($student_ids);

            $students = $this->student->builder()->with('guardian')->whereIn('user_id', $student_ids)->where('class_section_id', $class_section_id)
                ->whereHas('user', function ($query) {
                    $query->where('status', 1);
                })->with([
                    'user',
                    'user.fees_paids',
                    'class_section' => function ($query) {
                        $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name,shift_id', 'class.shift:id,name', 'section:id,name', 'medium:id,name');
                    }
                ])->get();

            // $guardian_ids = $students->pluck('guardian_id')->toArray();

            // // send notification to guardians
            // $title = "Overdue Fees";
            // $body = "Dear Guardian, the fees for your ward are overdue. Please make the necessary payment at the earliest.";
            // $type = 'Notification';

            // // Send the notification to the guardians
            // send_notification($guardian_ids, $title, $body, $type);

            ResponseService::successResponse("Data Fetched SuccessFully", $students);
        } catch (Throwable $e) {
            if (
                Str::contains($e->getMessage(), [
                    'does not exist',
                    'file_get_contents'
                ])
            ) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::rollback();
                ResponseService::logErrorResponse($e, 'FeesController -> feesOverDue method ');
                ResponseService::errorResponse();
            }
        }
    }

    public function studentAccountDeactivate(Request $request)
    {
        try {
            // Retrieve the IDs from the request
            $checkedIds = explode(',', $request->checked_ids);
            $users = $this->user->builder()->whereIn('id', $checkedIds)->get();
            // dd($users);
            foreach ($users as $user) {
                $user->status = 0;
                $user->update();
            }

            ResponseService::successResponse("Students Deactived Account Successfully.");
        } catch (\Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> studentAccountDeactivate method ');
            ResponseService::errorResponse();
        }
    }
}
