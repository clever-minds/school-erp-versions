<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\FeesInstallment\FeesInstallmentInterface;
use App\Repositories\Medium\MediumInterface;
use App\Repositories\FeesType\FeesTypeInterface;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\FeesClassType\FeesClassTypeInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Models\FeesAdvance;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Str;

class StaffFeesApiController extends Controller
{
    private FeesInterface $fees;
    private SessionYearInterface $sessionYear;
    private FeesInstallmentInterface $feesInstallment;
    private MediumInterface $medium;
    private FeesTypeInterface $feesType;
    private ClassSchoolInterface $class;
    private FeesClassTypeInterface $feesClassType;
    private StudentInterface $student;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private FeesPaidInterface $feesPaid;
    private CompulsoryFeeInterface $compulsoryFee;
    private OptionalFeeInterface $optionalFee;
    private PaymentTransactionInterface $paymentTransaction;

    public function __construct(
        FeesInterface $fees,
        SessionYearInterface $sessionYear,
        FeesInstallmentInterface $feesInstallment,
        MediumInterface $medium,
        FeesTypeInterface $feesType,
        ClassSchoolInterface $classSchool,
        FeesClassTypeInterface $feesClassType,
        StudentInterface $student,
        CachingService $cache,
        SessionYearsTrackingsService $sessionYearsTrackingsService,
        FeesPaidInterface $feesPaid,
        CompulsoryFeeInterface $compulsoryFee,
        OptionalFeeInterface $optionalFee,
        PaymentTransactionInterface $paymentTransaction
    ) {
        $this->fees = $fees;
        $this->sessionYear = $sessionYear;
        $this->feesInstallment = $feesInstallment;
        $this->medium = $medium;
        $this->feesType = $feesType;
        $this->class = $classSchool;
        $this->feesClassType = $feesClassType;
        $this->student = $student;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->feesPaid = $feesPaid;
        $this->compulsoryFee = $compulsoryFee;
        $this->optionalFee = $optionalFee;
        $this->paymentTransaction = $paymentTransaction;
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('fees-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');
        $session_year_id = request('session_year_id');
        $medium_id = request('medium_id');

        try {
            $sql = $this->fees->builder()->with('installments', 'class:id,name,stream_id,medium_id', 'class.medium:id,name', 'class.stream:id,name', 'fees_class_type.fees_type:id,name')
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
                })->when($session_year_id, function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                })->when($medium_id, function ($query) use ($medium_id) {
                    $query->whereHas('class', function ($q) use ($medium_id) {
                        $q->where('medium_id', $medium_id);
                    });
                });

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['compulsory_fees'] = number_format($row->fees_class_type->filter(function ($data) {
                    return $data->optional == 0;
                })->sum('amount'), 2);
                $tempRow['total_fees'] = number_format($row->fees_class_type->sum('amount'), 2);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Fees fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffFeesApiController -> show");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('fees-create');
        
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
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
            'optional_fees_type' => 'nullable|array',
            'optional_fees_type.*.fees_type_id' => 'required_with:optional_fees_type|numeric',
            'optional_fees_type.*.amount' => 'required_with:optional_fees_type|numeric',
            'fees_installments' => 'required_if:include_fee_installments,1|array',
            'fees_installments.*.name' => 'required',
            'fees_installments.*.due_date' => 'required|date',
            'fees_installments.*.due_charges' => 'required|numeric',
            'fees_installments.*.due_charges_type' => 'required|in:fixed,percentage',
            'fees_installments.*.amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            if ($request->include_fee_installments) {
                $totalInstallments = collect($request->fees_installments)->sum('amount');
                $totalCompulsoryFees = collect($request->compulsory_fees_type)->sum('amount');

                if ((float) $totalInstallments !== (float) $totalCompulsoryFees) {
                    return ResponseService::errorResponse('Total amount of Fees Installments is not equal to the total amount of Compulsory Fees');
                }
            }

            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $classes = $this->class->builder()->whereIn("id", $request->class_id)->with('stream', 'medium')->get();

            foreach ($request->class_id as $class_id) {
                $class = $classes->first(function ($data) use ($class_id) {
                    return $data->id == $class_id;
                });
                $name = (!empty($request->name)) ? $request->name . " - " : "";
                
                $fees = $this->fees->create([
                    'name' => $name . $class->full_name,
                    'due_date' => date('Y-m-d', strtotime($request->due_date)),
                    'due_charges' => $request->due_charges_percentage,
                    'due_charges_amount' => $request->due_charges_amount,
                    'class_id' => $class_id,
                    'session_year_id' => $sessionYear->id,
                ]);

                $semester = $this->cache->getDefaultSemesterData();
                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Fees', $fees->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);

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

                if ($request->include_fee_installments && count($request->fees_installments) > 0) {
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

            DB::commit();
            return ResponseService::successResponse('Fees created successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffFeesApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('fees-edit');

        $feesDataCheck = $this->fees->builder()->withCount('fees_paid')->findOrFail($id);

        $rules = [
            'name' => 'required|string',
            'include_fee_installments' => 'required|boolean',
            'due_date' => 'required|date',
            'due_charges_percentage' => 'required|numeric',
            'due_charges_amount' => 'required|numeric',
        ];

        if ($feesDataCheck->fees_paid_count == 0) {
            $rules = array_merge($rules, [
                'compulsory_fees_type' => 'required|array',
                'compulsory_fees_type.*' => 'required|array',
                'compulsory_fees_type.*.id' => 'nullable|numeric',
                'compulsory_fees_type.*.fees_type_id' => 'required|numeric',
                'compulsory_fees_type.*.amount' => 'required|numeric',
                'optional_fees_type' => 'nullable|array',
                'optional_fees_type.*.id' => 'nullable|numeric',
                'optional_fees_type.*.fees_type_id' => 'required_with:optional_fees_type|numeric',
                'optional_fees_type.*.amount' => 'required_with:optional_fees_type|numeric',
                'fees_installments' => 'nullable|array',
                'fees_installments.*.id' => 'nullable|numeric',
                'fees_installments.*.name' => 'required',
                'fees_installments.*.due_date' => 'required|date',
                'fees_installments.*.due_charges' => 'required|numeric',
                'fees_installments.*.due_charges_type' => 'required|in:fixed,percentage',
                'fees_installments.*.amount' => 'required|numeric',
            ]);
        } else {
            $rules = array_merge($rules, [
                'fees_installments' => 'nullable|array',
                'fees_installments.*.due_date' => 'required|date'
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        if ($request->include_fee_installments && $feesDataCheck->fees_paid_count == 0) {
            $totalInstallments = collect($request->fees_installments)->sum('amount');
            $totalCompulsoryFees = collect($request->compulsory_fees_type)->sum('amount');

            if ((float) $totalInstallments !== (float) $totalCompulsoryFees) {
                return ResponseService::errorResponse('Total amount of Fees Installments is not equal to the total amount of Compulsory Fees');
            }
        }

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();

            $feesData = array(
                'name' => $request->name,
                'due_date' => date('Y-m-d', strtotime($request->due_date)),
                'due_charges' => $request->due_charges_percentage,
                'due_charges_amount' => $request->due_charges_amount
            );
            $fees = $this->fees->update($id, $feesData);

            if (!empty($request->fees_installments)) {
                $installmentData = array();
                foreach ($request->fees_installments as $data) {
                    $data = (object) $data;
                    $installmentData[] = array(
                        'id' => $data->id ?? null,
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

            DB::commit();
            return ResponseService::successResponse('Fees updated successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffFeesApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('fees-delete');
        try {
            DB::beginTransaction();
            $this->fees->deleteById($id);
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Fees', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            DB::commit();
            return ResponseService::successResponse("Fees Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffFeesApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }

    public function compulsoryFeesPaidStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('fees-paid');
        $validator = Validator::make($request->all(), [
            'fees_id' => 'required|numeric',
            'student_id' => 'required|numeric',
            'installment_mode' => 'required|boolean',
            'installment_fees' => 'array',
            'installment_fees' => 'required_if:installment_mode,1',
            'mode' => 'required|numeric'
        ], [
            'installment_fees.required_if' => 'Please select at least one installment'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $fees = $this->fees->findById($request->fees_id, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);

            $feesPaid = $this->feesPaid->builder()->where([
                'fees_id' => $request->fees_id,
                'student_id' => $request->student_id
            ])->first();

            if (!empty($feesPaid) && $feesPaid->is_fully_paid) {
                return ResponseService::errorResponse("Compulsory Fees already Paid");
            }

            $amount = 0;
            if ($request->installment_mode) {
                if (!empty($request->installment_fees)) {
                    $amount = array_sum(array_column($request->installment_fees, 'amount'));
                }
                $amount += $request->advance ?? 0;
            } else {
                if ($request->enter_amount) {
                    $amount = $request->enter_amount;
                } else {
                    $amount = $request->total_amount;
                }
            }

            if (empty($feesPaid)) {
                $feesPaidResult = $this->feesPaid->create([
                    'date' => date('Y-m-d', strtotime($request->date)),
                    'is_fully_paid' => $amount >= $fees->total_compulsory_fees,
                    'is_used_installment' => $request->installment_mode,
                    'fees_id' => $request->fees_id,
                    'student_id' => $request->student_id,
                    'amount' => $amount,
                ]);
            } else {
                $feesPaidResult = $this->feesPaid->update($feesPaid->id, [
                    'amount' => $amount + $feesPaid->amount,
                    'is_fully_paid' => ($amount + $feesPaid->amount) >= $fees->total_compulsory_fees
                ]);
            }

            if ($request->installment_mode == 1) {
                if (!empty($request->installment_fees)) {
                    foreach ($request->installment_fees as $installment_fee) {
                        $compulsoryFeeData = array(
                            'student_id' => $request->student_id,
                            'type' => 'Installment Payment',
                            'installment_id' => $installment_fee['id'],
                            'mode' => $request->mode,
                            'remark' => $request->remark,
                            'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                            'transaction_id' => $request->mode == 3 ? $request->transaction_id : null,
                            'bank_name' => $request->mode == 3 ? $request->bank_name : null,
                            'amount' => $installment_fee['amount'],
                            'due_charges' => $installment_fee['due_charges'] ?? null,
                            'fees_paid_id' => $feesPaidResult->id,
                            'date' => date('Y-m-d', strtotime($request->date))
                        );
                        $this->compulsoryFee->create($compulsoryFeeData);

                        $sessionYear = $this->cache->getDefaultSessionYear();
                        $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\CompulsoryFee', $feesPaidResult->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
                    }
                }
            } else {
                $compulsoryFeeData = array(
                    'type' => 'Full Payment',
                    'student_id' => $request->student_id,
                    'mode' => $request->mode,
                    'remark' => $request->remark,
                    'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                    'transaction_id' => $request->mode == 3 ? $request->transaction_id : null,
                    'bank_name' => $request->mode == 3 ? $request->bank_name : null,
                    'amount' => $amount,
                    'due_charges' => $request->due_charges_amount ?? null,
                    'fees_paid_id' => $feesPaidResult->id,
                    'date' => date('Y-m-d', strtotime($request->date))
                );
                $this->compulsoryFee->create($compulsoryFeeData);

                $sessionYear = $this->cache->getDefaultSessionYear();
                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\CompulsoryFee', $feesPaidResult->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            }

            if ($request->advance > 0) {
                $updateCompulsoryFees = $this->compulsoryFee->builder()->where('student_id', $request->student_id)->with('fees_paid')->whereHas('fees_paid', function ($q) use ($request) {
                    $q->where('fees_id', $request->fees_id);
                })->orderBy('id', 'DESC')->first();

                if ($updateCompulsoryFees) {
                    $updateCompulsoryFees->amount += $request->advance;
                    $updateCompulsoryFees->save();

                    FeesAdvance::create([
                        'compulsory_fee_id' => $updateCompulsoryFees->id,
                        'student_id' => $request->student_id,
                        'parent_id' => $request->parent_id ?? null,
                        'amount' => $request->advance
                    ]);
                }
            }

            DB::commit();
            return ResponseService::successResponse("Fees Payment Recorded Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'StaffFeesApiController -> compulsoryFeesPaidStore method ');
            return ResponseService::errorResponse();
        }
    }

    public function optionalFeesPaidStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('fees-paid');
        $validator = Validator::make($request->all(), [
            'fees_id' => 'required|numeric',
            'student_id' => 'required|numeric',
            'fees_class_id' => 'required|array',
            'amount' => 'required|array',
            'mode' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $feesPaid = $this->feesPaid->builder()->where(['fees_id' => $request->fees_id, 'student_id' => $request->student_id])->first();
            if (empty($feesPaid)) {
                $feesPaidResult = $this->feesPaid->create([
                    'date' => date('Y-m-d', strtotime($request->date)),
                    'is_fully_paid' => 0,
                    'is_used_installment' => 0,
                    'fees_id' => $request->fees_id,
                    'student_id' => $request->student_id,
                    'amount' => 0,
                ]);
                $fees_paid_id = $feesPaidResult->id;
            } else {
                $fees_paid_id = $feesPaid->id;
            }

            foreach ($request->fees_class_id as $key => $fees_class_id) {
                if ($request->amount[$key] > 0) {
                    $optionalFeeData = array(
                        'student_id' => $request->student_id,
                        'class_id' => $request->class_id,
                        'mode' => $request->mode,
                        'cheque_no' => $request->mode == 2 ? $request->cheque_no : null,
                        'transaction_id' => $request->mode == 3 ? $request->transaction_id : null,
                        'bank_name' => $request->mode == 3 ? $request->bank_name : null,
                        'amount' => $request->amount[$key],
                        'fees_paid_id' => $fees_paid_id,
                        'fees_class_type_id' => $fees_class_id,
                        'date' => date('Y-m-d', strtotime($request->date)),
                        'status' => "Success"
                    );
                    $optionalFee = $this->optionalFee->create($optionalFeeData);

                    $sessionYear = $this->cache->getDefaultSessionYear();
                    $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\OptionalFee', $optionalFee->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
                }
            }
            DB::commit();
            return ResponseService::successResponse("Optional Fees Recorded Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'StaffFeesApiController -> optionalFeesPaidStore');
            return ResponseService::errorResponse();
        }
    }
}
