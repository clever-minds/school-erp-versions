<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\FeesClass\FeesClassInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\FeesType\FeesTypeInterface;
use App\Repositories\InstallmentFees\InstallmentFeesInterface;
use App\Repositories\Medium\MediumInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class FeesController extends Controller {
    private FeesInterface $fees;
    private SessionYearInterface $sessionYear;
    private InstallmentFeesInterface $installmentFees;
    private SchoolSettingInterface $schoolSettings;
    private MediumInterface $medium;
    private FeesTypeInterface $feesType;
    private ClassSchoolInterface $classes;
    private FeesClassInterface $feesClass;
    private UserInterface $user;
    private FeesPaidInterface $feesPaid;
    private CompulsoryFeeInterface $compulsoryFee;
    private OptionalFeeInterface $optionalFee;
    private CachingService $cache;
    private PaymentConfigurationInterface $paymentConfigurations;

    public function __construct(FeesInterface $fees, SessionYearInterface $sessionYear, InstallmentFeesInterface $installmentFees, SchoolSettingInterface $schoolSettings, MediumInterface $medium, FeesTypeInterface $feesType, ClassSchoolInterface $classes, FeesClassInterface $feesClass, UserInterface $user, FeesPaidInterface $feesPaid, CompulsoryFeeInterface $compulsoryFee, OptionalFeeInterface $optionalFee, CachingService $cache, PaymentConfigurationInterface $paymentConfigurations) {
        $this->fees = $fees;
        $this->sessionYear = $sessionYear;
        $this->installmentFees = $installmentFees;
        $this->schoolSettings = $schoolSettings;
        $this->medium = $medium;
        $this->feesType = $feesType;
        $this->classes = $classes;
        $this->feesClass = $feesClass;
        $this->user = $user;
        $this->feesPaid = $feesPaid;
        $this->compulsoryFee = $compulsoryFee;
        $this->optionalFee = $optionalFee;
        $this->cache = $cache;
        $this->paymentConfigurations = $paymentConfigurations;
    }


    public function index() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        return view('fees.index');
    }


    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-create');
        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year

            // Fees Data Store
            $feesData = array(
                'name'                     => $request->name,
                'due_date'                 => date('Y-m-d', strtotime($request->due_date)),
                'due_charges'              => $request->due_charges,
                'include_fee_installments' => $request->fees_installment,
                'session_year_id'          => $sessionYear->id,
            );
            $fees = $this->fees->create($feesData);

            // Installment Data Store
            if ($request->fees_installment == 1 && isset($request->installment_data) && !empty($request->installment_data)) {
                $installmentData = array();
                foreach ($request->installment_data as $data) {
                    $data = (object)$data; // Convert Array to Object
                    $installmentData[] = array(
                        'name'            => $data->name,
                        'due_date'        => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges'     => $data->due_charges,
                        'fees_id'         => $fees->id,
                        'session_year_id' => $sessionYear->id,
                        'created_at'      => now(),
                        'updated_at'      => now()
                    );
                }
                $this->installmentFees->createBulk($installmentData);
            }

            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function show() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');

        $sql = $this->fees->builder()->with('installments')
            ->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('due_date', 'LIKE', "%$search%")
                    ->orwhere('due_charges', 'LIKE', "%$search%");
            })
            ->when(!empty($showDeleted), function ($query) {
                $query->onlyTrashed();
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            if ($showDeleted) {
                //Show Restore and Hard Delete Buttons
                $operate .= BootstrapTableService::restoreButton(route('fees.restore', $row->id));
                $operate .= BootstrapTableService::trashButton(route('fees.trash', $row->id));
            } else {
                //Show Fees Class, Edit and Soft Delete Buttons
                $operate .= BootstrapTableService::button('fa fa-list-ol', route('fees.class-index', $row->id), ['btn-gradient-info'], ['title' => trans('class')]);
                $operate .= BootstrapTableService::editButton(route('fees.update', $row->id));
                $operate .= BootstrapTableService::deleteButton(route('fees.destroy', $row->id));
            }
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['due_date'] = date('Y-m-d', strtotime($row->due_date));
            $tempRow['due_charges_view'] = $row->due_charges . '%';
            $tempRow['include_fee_installments'] = (int)$row->include_fee_installments;
            $tempRow['installment_data'] = $row->installments;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }


    public function update(Request $request, $id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-create');
        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year

            // Fees Data Store
            $feesData = array(
                'name'        => $request->edit_name,
                'due_date'    => date('Y-m-d', strtotime($request->edit_due_date)),
                'due_charges' => $request->edit_due_charges,
            );
            $fees = $this->fees->update($id, $feesData);

            // Installment Data Store
            if (!empty($request->edit_installment_data)) {
                $installmentData = array();
                foreach ($request->edit_installment_data as $data) {
                    $data = (object)$data; // Convert Array to Object
                    $installmentData[] = array(
                        'id'              => $data->id,
                        'name'            => $data->name,
                        'due_date'        => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges'     => $data->due_charges,
                        'fees_id'         => $fees->id,
                        'session_year_id' => $sessionYear->id
                    );
                }
                $this->installmentFees->upsert($installmentData, ['id'], ['name', 'due_date', 'due_charges', 'fees_id', 'session_year_id']);
            }

            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenSendJson('fees-delete');
        try {
            DB::beginTransaction();
            $this->fees->deleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id) {
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

    public function trash($id) {
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

    public function removeInstallmentData($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            DB::beginTransaction();
            $this->installmentFees->DeleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feesClassListIndex($feesId) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-class-list');
        $fees = $this->fees->findById($feesId);
        $classes = $this->classes->all(['*'], ['medium']);
        $mediums = $this->medium->all();
        $feesTypeData = $this->feesType->all();
        return response(view('fees.fees_class', compact('fees', 'classes', 'feesTypeData', 'mediums')));
    }

    public function feesClassList() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-class-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $medium_id = request('medium_id');
        $fees_id = request('fees_id');


        $sql = $this->classes->builder()->orderByRaw('CONVERT(name, SIGNED) asc')
            ->with(['fees_class' => function ($query) use ($fees_id) {
                $query->where('fees_id', $fees_id);
            }], 'medium')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('name', 'LIKE', "%$search%");
                });
            })
            ->when($medium_id, function ($query) use ($medium_id) {
                $query->where('medium_id', $medium_id);
            });
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {
            $row = (object)$row;
            $operate = BootstrapTableService::editButton(route('fees.class-update'));

            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->id;
            $tempRow['class_name'] = $row->name . ' ' . $row->medium->name;
            if (count($row->fees_class)) {
                $total_amount = 0;
                $base_amount = 0;
                $fees_type_table = array();
                foreach ($row->fees_class as $fees_details) {
                    $fees_type_table[] = array(
                        'id'           => $fees_details->id,
                        'fees_name'    => $fees_details->fees_type->name,
                        'amount'       => $fees_details->amount,
                        'fees_type_id' => $fees_details->fees_type->id,
                    );
                    if ($fees_details->choiceable == 0) {
                        $base_amount += $fees_details->amount;
                    }
                    $total_amount += $fees_details->amount;
                }
                $tempRow['fees_type'] = $fees_type_table;
                $tempRow['base_amount'] = $base_amount;
                $tempRow['total_amount'] = $total_amount;
            } else {
                $tempRow['fees_type'] = [];
                $tempRow['base_amount'] = "-";
                $tempRow['total_amount'] = "-";
            }
            $tempRow['fees_class'] = $row->fees_class;
            $tempRow['created_at'] = $row->created_at;
            $tempRow['updated_at'] = $row->updated_at;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function updateFeesClass(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noAnyPermissionThenSendJson(['fees-class-create', 'fees-class-update']);
        try {
            DB::beginTransaction();

            foreach ($request->fees_data as $data) {
                $classFeesData[] = array(
                    "fees_id"      => $request->fees_id,
                    "class_id"     => $request->class_id,
                    "fees_type_id" => $data['fees_type_id'],
                    "amount"       => $data['amount'],
                    "choiceable"   => $data['choiceable'],
                    "deleted_at"   => null
                );
            }
            if (!empty($classFeesData)) {
                $this->feesClass->upsert($classFeesData, ['class_id', 'fees_type_id'], ['amount', 'choiceable', 'deleted_at']);
            }
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> updateFeesClass method");
            ResponseService::errorResponse();
        }
    }

    public function removeFeesClass($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-class-delete');
        try {
            DB::beginTransaction();
            $this->feesClass->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> removeFeesClass method");
            ResponseService::errorResponse();
        }
    }

    public function feesPaidListIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        // Fees Data With Few Selected Data
        $fees = $this->fees->builder()->select(['id', 'name'])->with(['fees_class' => function ($q) {
            $q->select('id', 'class_id', 'fees_id')->groupby('class_id')->with('class:id,name,medium_id');
        }])->get();

        $classes = $this->classes->all(['*'], ['medium', 'sections']); // Classes Data
        $session_year_all = $this->sessionYear->all(['id', 'name', 'default']); // All Session Years Data
        return response(view('fees.fees_paid', compact('fees', 'classes', 'session_year_all')));
    }

    public function feesPaidList() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $feesId = (int)request('fees_id');
        $requestSessionYearId = (int)request('session_year_id');

        $currentSessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year
        $currentSessionYearId = $currentSessionYear->id;

        $sessionYearId = $requestSessionYearId ? $requestSessionYearId : $currentSessionYearId;

        $sql = $this->user->builder()->role('Student')->with([
            'student'       => function ($query) {
                $query->with([
                    'class_section' => function ($query) {
                        $query->with('class', 'section', 'medium');
                    }
                ]);
            },
            'fees_paid' => function ($query) use ($sessionYearId) {
                $query->where('session_year_id', $sessionYearId)
                      ->with('class', 'session_year');
            },
            'optional_fees' => function ($query) {
                $query->with('fees_class');
            },
            'compulsory_fees'
        ]);


        if (!empty($_GET['session_year_id'])) {
            $sql->whereHas('fees_paid', function ($q) {
                $q->where('session_year_id', $_GET['session_year_id']);
            });
        }

        if (!empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $class_section_id = ClassSection::where('class_id', $class_id)->pluck('id');
            $sql->whereHas('student', function ($query) use ($class_section_id) {
                $query->whereIn('class_section_id', $class_section_id);
            });
        } else {
            $sql->has('fees_paid');
        }

        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();


        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        $currentDate = date('Y-m-d');

        // Fees Data Query
        $feesDataQuery = $this->fees->findById($feesId, ['*'], ['fees_class.optional_fees', 'fees_class.fees_type:id,name', 'installments.compulsory_fees', 'fees_paid']);

        foreach ($res as $row) {
            $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu">';
            $operate .= '<a href="#" class="compulsory-data dropdown-item" data-id=' . $row->id . ' title="' . trans('Compulsory Fees') . '" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('compulsory') . ' ' . trans('fees') . '</a><div class="dropdown-divider"></div>';
            $operate .= '<a href="#" class="optional-data dropdown-item" data-id=' . $row->id . ' title="' . trans('Optional Fees') . '" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>' . trans('optional') . ' ' . trans('fees') . '</a>';
            $operate .= '</div></div>&nbsp;&nbsp;';

            if ($row->fees_paid && !$row->fees_paid->isEmpty()) {
                foreach ($row->fees_paid as $fees_paid) {
                    $operate = ($fees_paid->session_year_id == $sessionYearId) ? $operate : "";
                    $operate .= BootstrapTableService::button('fa fa-remove', route('fees.paid.clear.data', $fees_paid->id), ['btn', 'btn-xs', 'btn-gradient-danger', 'btn-rounded', 'btn-icon', 'delete-form'], ['title' => trans('clear'), 'data-id' => $fees_paid->id]);
                    $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', $fees_paid->id), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $fees_paid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                }
            }


            $classId = $row->student->class_section->class_id;

            $optionalData = [];
            $feesDataQuery->optional_fees->where('class_id', $classId)->each(function ($optional) use ($row, &$optionalData) {
                $transactions = $optional->optional_fees()->where('student_id', $row->id)->get();

                if (!$transactions->isEmpty()) {
                    $transactions->each(function ($transaction) use ($optional, &$optionalData) {
                        $optionalData[] = [
                            'id'           => $optional->id,
                            'name'         => $optional->fees_type->name,
                            'class_id'     => $optional->class_id,
                            'fees_type_id' => $optional->fees_type_id,
                            'choiceable'   => 1,
                            'amount'       => $optional->amount,
                            'is_paid'      => 1,
                            'paid_id'      => $transaction->id,
                            'date'         => date('d-m-Y', strtotime($transaction->date))
                        ];
                    });
                } else {
                    $optionalData[] = [
                        'id'           => $optional->id,
                        'name'         => $optional->fees_type->name,
                        'class_id'     => $optional->class_id,
                        'fees_type_id' => $optional->fees_type_id,
                        'choiceable'   => 1,
                        'amount'       => $optional->amount,
                        'is_paid'      => null,
                        'paid_id'      => null,
                        'date'         => null
                    ];
                }
            });

            $compulsoryData = [];
            $feesDataQuery->compulsory_fees->where('class_id', $classId)->each(function ($compulsory) use ($row, &$compulsoryData) {
                $compulsoryData[] = [
                    'id'           => $compulsory->id,
                    'name'         => $compulsory->fees_type->name,
                    'class_id'     => $compulsory->class_id,
                    'fees_type_id' => $compulsory->fees_type_id,
                    'choiceable'   => 0,
                    'amount'       => $compulsory->amount,
                    'is_paid'      => ($row->fees_paid->isNotEmpty()) ? 1 : 0,
                    'paid_id'      => ($row->fees_paid->isNotEmpty()) ? $row->fees_paid->first()->id : null,
                    'date' => ($row->fees_paid->isNotEmpty()) ? date('d-m-Y', strtotime($row->fees_paid->first()->date)) : null,
                ];
            });

            $totalCompulsoryAmount = $feesDataQuery->compulsory_fees->where('class_id', $classId)->sum('amount');
            $isInstallmentPaid = 0;

            $installmentData = null;
            if ($feesDataQuery->include_fee_installments == 1) {

                // If there are installments, divide the total compulsory fees equally among them
                $installmentAmount = round($totalCompulsoryAmount / count($feesDataQuery->installments), 2);

                foreach ($feesDataQuery->installments as $installment) {
                    $installmentDueDate = date("Y-m-d", strtotime($installment->due_date)); // Due Date Parsing
                    $installmentDueChargesAmount = $totalInstallmentAmount = 0;
                    // Comparing the Installment Due Date with Current Date
                    if ($installmentDueDate < $currentDate) {
                        $installmentDueChargesApplicable = 1;
                        $installmentDueChargesAmount = round(($installment->due_charges / 100) * $installmentAmount, 2); // Get Due Charges Amount from the Installment Amount with two decimals round off
                        $totalInstallmentAmount = round($installmentAmount + $installmentDueChargesAmount, 2); // Sum Installment Amount with Due Charges with two decimals round off
                    } else {
                        $installmentDueChargesApplicable = 0;
                    }

                    // Fetch the fees_transaction for the current installment and student
                    $transaction = $installment->compulsory_fees()->where('student_id', $row->id)->first();

                    // Initialize the additional fields
                    $isPaid = null;
                    $paidId = null;
                    $paidOnDate = null;

                    // If a transaction exists, update the additional fields
                    if ($transaction) {
                        $isPaid = 1;
                        $paidId = $transaction->id;
                        $paidOnDate = date('d-m-Y', strtotime($transaction->date));
                        $isInstallmentPaid = 1;
                    }

                    $installmentData[] = array(
                        'id'                     => $installment->id,
                        'name'                   => $installment->name,
                        'installment_amount'     => $installmentAmount,
                        'due_date'               => date('d-m-Y', strtotime($installmentDueDate)),
                        'due_charges_percentage' => $installment->due_charges,
                        'due_charges_applicable' => $installmentDueChargesApplicable,
                        'due_charges_amount'     => $installmentDueChargesApplicable == 1 ? $installmentDueChargesAmount : null,
                        'total_amount'           => $installmentDueChargesApplicable == 1 ? $totalInstallmentAmount : $installmentAmount,
                        'is_paid'                => $isPaid,
                        'paid_id'                => $paidId,
                        'paid_on'                => $paidOnDate,
                    );
                }
            }

            // Check Due Date Without Installment
            $dueDate = date('Y-m-d', strtotime($feesDataQuery->due_date));
            $dueChargesAmount = $totalCompulsoryAmountWithDueCharges = 0;
            if ($dueDate < $currentDate) {
                $dueChargesApplicable = 1;
                $dueChargesAmount = round(($feesDataQuery->due_charges / 100) * $totalCompulsoryAmount, 2); // Get Due Charges Amount from the Total Compulsory Amount with two decimals round off
                $totalCompulsoryAmountWithDueCharges = round($totalCompulsoryAmount + $dueChargesAmount, 2); // Sum Total Compulsory Amount with Due Charges with two decimals round off
            } else {
                $dueChargesApplicable = 0;
            }

            // Fees Data
            $feesData = array(
                'id'                          => $feesDataQuery->id,
                'name'                        => $feesDataQuery->name,
                'include_fee_installments'    => (int)$feesDataQuery->include_fee_installments,
                'installment_data'            => $installmentData,
                'compulsory_fees'             => $compulsoryData ?? null,
                'optional_fees'               => $optionalData ?? null,
                'due_date'                    => date('d-m-Y', strtotime($dueDate)),
                'due_charges_percentage'      => $feesDataQuery->due_charges,
                'due_charges_applicable'      => $dueChargesApplicable == 1 ? 1 : 0,
                'total_compulsory_amount'     => $totalCompulsoryAmount,
                'due_charges_amount'          => $dueChargesApplicable == 1 ? $dueChargesAmount : null,
                'compulsory_with_due_charges' => $dueChargesApplicable == 1 ? $totalCompulsoryAmountWithDueCharges : $totalCompulsoryAmount,
                'is_installment_paid'         => $isInstallmentPaid,
            );

            $tempRow = array(
                'id'                   => $row->fees_paid->toArray() ? $row->fees_paid->first()->id : null,
                'student_id'           => $row->id,
                'student_user_id'      => $row->user_id,
                'no'                   => $no++,
                'student_name'         => $row->full_name,
                'class_id'             => $row->student->class_section->class_id,
                'class_name'           => $row->student->class_section->full_name,
                'fees_data'            => $feesData,
                'fees_status'          => $row->fees_paid->toArray() ? $row->fees_paid->first()->is_fully_paid : null,
                'total_fees'           => $row->fees_paid->toArray() ? $row->fees_paid->first()->amount : null,
                'current_date'         => date('d-m-Y', strtotime($currentDate)),
                'fees_paid_on'         => $row->fees_paid->toArray() ? date('d-m-Y', strtotime($row->fees_paid->first()->date)) : null,
                'fees_paid_updated_on' => $row->fees_paid->toArray() ? $row->fees_paid->first()->updated_on : null,
                'session_year_name'    => $row->fees_paid->toArray() ? $row->fees_paid->first()->session_year->name : null,
                'operate'              => $operate,
            );

            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function optionalFeesPaidStore(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Current Session Year from Cache
            $cache = app(CachingService::class);
            $currentSessionYear = $cache->getDefaultSessionYear();

            $feesPaidQuery = $this->feesPaid->builder()->where(['fees_id' => $request->fees_id, 'student_id' => $request->student_id, 'class_id' => $request->class_id]); // Get Query Of Fees Paid
            $feesPaidDB = $feesPaidQuery->first(); // Get Fees Paid Data

            // Check if Fees Paid Exists Then Add The optional Fees Amount with Fess Paid Amount
            $total_amount = $feesPaidQuery->count() ? $feesPaidDB->amount + $request->total_amount : $request->total_amount;

            // Fees Paid Array
            $feesPaidData = array(
                'amount' => $total_amount,
                'date'   => date('Y-m-d', strtotime($request->date)),
            );

            // If Fees Paid Doesn't Exists
            if (!$feesPaidQuery->count()) {

                // Then Merge the Fees Paid Data with the Data
                $feesPaidData = array_merge($feesPaidData, [
                    'fees_id'       => $request->fees_id,
                    'student_id'    => $request->student_id,
                    'class_id'      => $request->class_id,
                    'is_fully_paid' => 0,
                    'session_year_id' => $currentSessionYear->id,
                ]);

                // Store Fees Paid New Entry
                $feesPaidResult = $this->feesPaid->create($feesPaidData);
            } else {
                // If Data Exists then Update only Date and Amount
                $feesPaidResult = $this->feesPaid->update($feesPaidDB->id, $feesPaidData);
            }


            $optionalFeeData = array();
            foreach ($request->optional_data as $optionalFees) {
                if (isset($optionalFees['id'])) {
                    $optionalFeeData[] = array(
                        'student_id'    => $request->student_id,
                        'class_id'      => $request->class_id,
                        'fees_class_id' => $optionalFees['fees_class_id'],
                        'mode'          => $request->mode,
                        'cheque_no'     => $request->mode == 2 ? $request->cheque_no : null,
                        'amount'        => $optionalFees['amount'],
                        'fees_paid_id'  => $feesPaidResult->id,
                        'date' => date('Y-m-d', strtotime($request->date)),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    );
                }
            }

            // Store Fees Transaction Bulk Data
            $this->optionalFee->createBulk($optionalFeeData);

            DB::commit();
            ResponseService::SuccessResponse("Data Updated SuccessFully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> optionalFeesPaidStore method");
            ResponseService::errorResponse();
        }
    }
    // public function removeFeesTransaction($id) {
    //     ResponseService::noPermissionThenRedirect('fees-paid');
    //     try {
    //         DB::beginTransaction();

    //         // Get Fees Paid ID and Amount of Fees Transaction Table
    //         $feesTransactionData = $this->feesTransaction->findById($id);
    //         $feesPaidId = $feesTransactionData->fees_paid_id;
    //         $feesTransactionAmount = $feesTransactionData->amount;

    //         $this->feesTransaction->permanentlyDeleteById($id); // Permanently Delete Fees Transaction Data

    //         // Check Fees Transactions Entry
    //         $isFeesTransactionsExists = $this->feesTransaction->builder()->where('fees_paid_id',$feesPaidId)->count();
    //         if($isFeesTransactionsExists){
    //             $feesPaidData = $this->feesPaid->findById($feesPaidId); // Get Fees Paid Data
    //             $feesPaidAmount = $feesPaidData->amount; // Get Fees Paid Amount
    //             $finalAmount = $feesPaidAmount - $feesTransactionAmount; // Calculate Final Amount
    //             $this->feesPaid->update($feesPaidId,['amount' => $finalAmount]); // Update Fees Paid Data with Final Amount
    //         }else{
    //             $this->feesPaid->permanentlyDeleteById($feesPaidId);
    //         }

    //         DB::commit();
    //         ResponseService::successResponse('Data Deleted Successfully');
    //     } catch (Throwable $e) {
    //         DB::rollback();
    //         ResponseService::logErrorResponse($e);
    //         ResponseService::errorResponse();
    //     }
    // }
    public function removeOptionalFees($id) {
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
                $feesPaidData = $feesPaidDataQuery->first(); // Get Fees Paid Data
                $feesPaidAmount = $feesPaidData->amount; // Get Fees Paid Amount
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

    public function compulsoryFeesPaidStore(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Current Session Year from Cache
            $cache = app(CachingService::class);
            $currentSessionYear = $cache->getDefaultSessionYear();

            $feesPaidQuery = $this->feesPaid->builder()->where(['fees_id' => $request->fees_id, 'student_id' => $request->student_id, 'class_id' => $request->class_id, 'session_year_id' => $currentSessionYear->id]); // Get Query Of Fees Paid
            $feesPaidDB = $feesPaidQuery->first(); // Get Fees Paid Data

            // Check if Fees Paid Exists Then Add The optional Fees Amount with Fess Paid Amount
            $totalAmount = $feesPaidQuery->count() ? $feesPaidDB->amount + $request->total_amount : $request->total_amount;

            // Fees Paid Array
            $feesPaidData = array(
                'amount'        => $totalAmount,
                'date' => date('Y-m-d', strtotime($request->date)),
                'is_fully_paid' => $request->is_fully_paid
            );

            // If Fees Paid Doesn't Exists
            if (!$feesPaidQuery->count()) {

                // Then Merge the Fees Paid Data with the Data
                $feesPaidData = array_merge($feesPaidData, [
                    'fees_id'         => $request->fees_id,
                    'student_id'      => $request->student_id,
                    'class_id'        => $request->class_id,
                    'session_year_id' => $currentSessionYear->id,
                ]);

                // Store Fees Paid New Entry
                $feesPaidResult = $this->feesPaid->create($feesPaidData);
            } else {
                // If Data Exists then Update only Date and Amount
                $feesPaidResult = $this->feesPaid->update($feesPaidDB->id, $feesPaidData);
            }

            if ($request->installment_mode == 1) {
                $compulsoryFeeData = array();
                foreach ($request->installment_fees as $installmentFees) {
                    if (isset($installmentFees['id'])) {
                        $compulsoryFeeData[] = array(
                            'student_id'     => $request->student_id,
                            'class_id'       => $request->class_id,
                            'type'           => 2,
                            'installment_id' => $installmentFees['id'],
                            'mode'           => $request->mode,
                            'cheque_no'      => $request->mode == 2 ? $request->cheque_no : null,
                            'amount'         => $installmentFees['amount'],
                            'due_charges'    => $installmentFees['due_charges'] ?? null,
                            'fees_paid_id'   => $feesPaidResult->id,
                            'date'           => date('Y-m-d', strtotime($request->date)),
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        );
                    }
                }
                // Store Fees Transaction Bulk Data
                $this->compulsoryFee->createBulk($compulsoryFeeData);
            } else {
                $compulsoryFeeData = array(
                    'type'         => 1,
                    'student_id'   => $request->student_id,
                    'class_id'     => $request->class_id,
                    'mode'         => $request->mode,
                    'cheque_no'    => $request->mode == 2 ? $request->cheque_no : null,
                    'amount'       => $totalAmount,
                    'due_charges'  => $request->due_charges_amount ?? null,
                    'fees_paid_id' => $feesPaidResult->id,
                    'date'         => date('Y-m-d', strtotime($request->date))
                );
                $this->compulsoryFee->create($compulsoryFeeData);
            }
            DB::commit();
            ResponseService::successResponse("Data Updated SuccessFully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> compulsoryFeesPaidStore method ');
            ResponseService::errorResponse();
        }
    }

    public function removeInstallmentFees($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Fees Paid ID and Amount of Fees Transaction Table
            $installmentFeeTransaction = $this->compulsoryFee->findById($id);
            $feesPaidId = $installmentFeeTransaction->fees_paid_id;
            $feesTransactionAmount = $installmentFeeTransaction->amount;

            $this->compulsoryFee->permanentlyDeleteById($id); // Permanently Delete Fees Transaction Data

            // Check Fees Transactions Entry
            $feesPaidDataQuery = $this->feesPaid->builder()->where('id', $feesPaidId);
            if ($feesPaidDataQuery->count()) {
                $feesPaidData = $feesPaidDataQuery->first(); // Get Fees Paid Data
                $feesPaidAmount = $feesPaidData->amount; // Get Fees Paid Amount
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

    public function clearFeesPaidData($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            $this->feesPaid->permanentlyDeleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Fees Controller -> clearFeesPaid");
            ResponseService::errorResponse();
        }
    }

    public function feesConfigIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');

        // List of the names to be fetched
        $names = array(
            'currency_code',
            'currency_symbol',
        );

        $settings = $this->schoolSettings->getBulkData($names); // Passing the array of names and gets the array of data
        $domain = request()->getSchemeAndHttpHost(); // Get Current Web Domain

        $paymentConfigurations = $this->paymentConfigurations->all();
        $stripeData = $paymentConfigurations->where('payment_method','stripe')->first();
        return view('fees.fees_config', compact('settings', 'domain', 'stripeData'));
    }

    public function feesConfigUpdate(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');
        $request->validate([
            'stripe_status'          => 'required',
            'stripe_publishable_key' => 'required_if:stripe_status,1|nullable',
            'stripe_secret_key'      => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_secret'  => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_url'     => 'required_if:stripe_status,1|nullable',
            'currency_code'          => 'required|max:10',
            'currency_symbol'        => 'required|max:5',
        ]);
        try {
            $this->paymentConfigurations->updateOrCreate(
                ['payment_method'     => strtolower('stripe')],
                [
                    'api_key'            => $request->stripe_publishable_key,
                    'secret_key'         => $request->stripe_secret_key,
                    'webhook_secret_key' => $request->stripe_webhook_secret,
                    'status'      => $request->stripe_status
                ]
            );



            // Store Currency Code and Currency Symbol in School Settings
            $settings = array(
                'currency_code','currency_symbol'
            );

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

    public function feesTransactionsLogsIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $session_year_all = $this->sessionYear->all(['id', 'name', 'default']);
        $classes = $this->classes->builder()->orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'stream', 'sections')->get();
        $mediums = $this->medium->builder()->orderBy('id', 'ASC')->get();
        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'session_year_all')));
    }

    public function feesTransactionsLogsList() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');

        //Fetching Students Data on Basis of Class Section ID with Relation fees paid
        $sql = $this->feesTransaction->builder()->with('student', 'fees_paid.session_year');

        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('order_id', 'LIKE', "%$search%")
                ->orwhere('payment_id', 'LIKE', "%$search%")
                ->orWhereHas('student.user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
        }
        if (!empty($_GET['session_year_id'])) {
            $sessionYearId = $_GET['session_year_id'];
            $sql = $sql->whereHas('fees_paid', function ($query) use ($sessionYearId) {
                $query->where('session_year_id', $sessionYearId);
            });
        }
        if (!empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $sql = $sql->where('class_id', $class_id);
        }
        if (!empty($_GET['payment_status']) && in_array($_GET['payment_status'], [0, 1, 2])) {
            $sql->where('payment_status', $_GET['payment_status']);
        }
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['student_name'] = $row->student->full_name;
            $tempRow['payment_gateway'] = null; // Optimise when api is done
            $tempRow['payment_status'] = null; // Optimise when api is done
            $tempRow['order_id'] = null; // Optimise when api is done
            $tempRow['payment_id'] = null; // Optimise when api is done
            $tempRow['session_year_name'] = $row->fees_paid->session_year->name;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function feesPaidReceiptPDF($feesPaidId) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            $verticalLogo = $this->cache->getSystemSettings()['vertical_logo'] ?? NULL; // Get Vertical Logo
            $logo = $verticalLogo ?? url('assets/vertical-logo2.svg');
            $schoolName = env('APP_NAME');
            // TODO : Fetch School Data from Caching
            $schoolAddress = getSchoolSettings('school_address');
            $currencySymbol = getSchoolSettings('currency_symbol');

            $feesPaid = $this->feesPaid->builder()->where('id', $feesPaidId)
                ->with([
                    'student:id,first_name,last_name',
                    'class',
                    'session_year',
                    'fees.fees_class',
                    'compulsory_fee.installment_fee:id,name',
                    'optional_fee' => function ($q) {
                        $q->with(['fees_class' => function ($q) {
                            $q->select('id', 'fees_type_id')->with('fees_type:id,name');
                        }]);
                    },
                ])->first();

            $verticalLogo = $this->cache->getSystemSettings()['vertical_logo'] ?? NULL; // Get Vertical Logo
            $logo = $verticalLogo ?? url('assets/vertical-logo2.svg');
            $schoolName = $this->cache->getSchoolSettings('school_name');
            $schoolAddress = $this->cache->getSchoolSettings('school_address');
            $currencySymbol = $this->cache->getSchoolSettings('currency_symbol') ?? NULL;

            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'schoolName', 'schoolAddress', 'currencySymbol', 'feesPaid'));
            return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}

