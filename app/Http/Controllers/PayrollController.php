<?php

namespace App\Http\Controllers;

use App\Models\PayrollSetting;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\LeaveMaster\LeaveMasterInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\StaffPayroll\StaffPayrollInterface;
use App\Repositories\StaffSalary\StaffSalaryInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use App\Models\TransportationPayment;
use App\Models\Expense;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;
use Throwable;

class PayrollController extends Controller
{
    private SessionYearInterface $sessionYear;
    private StaffInterface $staff;
    private ExpenseInterface $expense;
    private LeaveMasterInterface $leaveMaster;
    private CachingService $cache;
    private SchoolSettingInterface $schoolSetting;
    private LeaveInterface $leave;
    private SessionYearInterface $sessionYearInterface;
    private StaffSalaryInterface $staffSalary;
    private StaffPayrollInterface $staffPayroll;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(SessionYearInterface $sessionYear, StaffInterface $staff, ExpenseInterface $expense, LeaveMasterInterface $leaveMaster, CachingService $cache, SchoolSettingInterface $schoolSetting, LeaveInterface $leave, SessionYearInterface $sessionYearInterface, StaffSalaryInterface $staffSalary, StaffPayrollInterface $staffPayroll, SessionYearsTrackingsService $sessionYearsTrackingsService)
    {
        $this->sessionYear = $sessionYear;
        $this->staff = $staff;
        $this->expense = $expense;
        $this->leaveMaster = $leaveMaster;
        $this->cache = $cache;
        $this->schoolSetting = $schoolSetting;
        $this->leave = $leave;
        $this->sessionYearInterface = $sessionYearInterface;
        $this->staffSalary = $staffSalary;
        $this->staffPayroll = $staffPayroll;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sessionYear = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->first();
        $sessionYear = date('Y', strtotime($sessionYear->start_date));
        // Get months starting from session year
        $months = sessionYearWiseMonth();


        return view('payroll.index', compact('sessionYear', 'months'));
    }

    public function create()
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');
    }

    public function store(Request $request)
    {

        ResponseService::noFeatureThenSendJson('Expense Management');
        ResponseService::noPermissionThenSendJson('payroll-create');

        $request->validate([
            'net_salary' => 'required',
            'date' => 'required',
            'user_id' => 'required'
        ], [
            'net_salary.required' => trans('no_records_found'),
            'user_id.required' => trans('Please select at least one record')
        ]);

        try {
            DB::beginTransaction();
            $user_ids = explode(",", $request->user_id);

            $selectedMonth = $request->month;
            $selectedYear = $request->year;
            // Define the start and end dates
            $startDate = Carbon::createFromFormat('Y-m', "$selectedYear-$selectedMonth")->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $sessionYearInterface = $this->sessionYearInterface->builder()->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
            })->first();

            if (!$sessionYearInterface) {
                ResponseService::errorResponse('Session year not found');
            }

            $data = array();
            $staff_payroll_data = array();
            foreach ($user_ids as $key => $user_id) {
                $data = [
                    'title' => Carbon::create()->month($request->month)->format('F') . ' - ' . $request->year,
                    'description' => 'Salary',
                    'month' => $request->month,
                    'year' => $request->year,
                    'staff_id' => $user_id,
                    'basic_salary' => $request->basic_salary[$user_id],
                    'paid_leaves' => $request->paid_leave[$user_id],
                    'amount' => $request->net_salary[$user_id],
                    'session_year_id' => $sessionYearInterface->id,
                    'date' => date('Y-m-d', strtotime($request->date)),
                ];

                $expense = $this->expense->updateOrCreate(['staff_id' => $data['staff_id'], 'month' => $data['month'], 'year' => $data['year']], ['amount' => $data['amount'], 'session_year_id' => $data['session_year_id'], 'basic_salary' => $data['basic_salary'], 'date' => $data['date'], 'title' => $data['title'], 'paid_leaves' => $data['paid_leaves'], 'description' => $data['description']]);

                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Expense', $expense->id, Auth::user()->id, $sessionYearInterface->id, Auth::user()->school_id, null);

                $staffSalary = $this->staffSalary->builder()->where('staff_id', $user_id)->get();
                if (count($staffSalary)) {
                    foreach ($staffSalary as $key => $payroll) {
                        $staff_payroll_data[] = [
                            'expense_id' => $expense->id,
                            'payroll_setting_id' => $payroll->payroll_setting_id,
                            'amount' => $payroll->amount,
                            'percentage' => $payroll->percentage,
                        ];
                    }
                }
            }

            $this->staffPayroll->upsert($staff_payroll_data, ['staff_id', 'payroll_setting_id'], ['amount', 'percentage']);
            $user = $this->staff->builder()->whereIn('id', $user_ids)->pluck('user_id');

            $title = 'Payroll Update !!!';
            $body = "Your Payroll has been Updated.";
            $type = "payroll";

            DB::commit();
            send_notification($user, $title, $body, $type);

            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not sent.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, 'Payroll Controller -> Store method');
                ResponseService::errorResponse();
            }
        }
    }

    public function show()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $month = request('month');
        $year = request('year');

        $schoolSetting = $this->cache->getSchoolSettings();
        $payrollSetting = PayrollSetting::where('name', 'Transportation Deduction')->first();

        /* =====================================================
           SAFE QUERY REDUCTION #1
           StaffSalary expiry cleanup (NO logic change)
           ===================================================== */

        if ($payrollSetting) {
            $this->staffSalary->builder()
                ->where('payroll_setting_id', $payrollSetting->id)
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ])
                ->delete();
        }

        /* =====================================================
           SAFE QUERY REDUCTION #2
           Preload expenses (same conditions, reused)
           ===================================================== */

        $expenses = Expense::with('staff_payroll.payroll_setting')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('staff_id');

        /* =====================================================
           SAFE QUERY REDUCTION #3
           Preload transportation payments (same filters)
           ===================================================== */

        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $transportationPayments = TransportationPayment::whereDate('created_at', '<=', $monthEnd)
            ->whereDate('expiry_date', '>=', $monthStart)
            ->get()
            ->groupBy('user_id');

        /* =====================================================
           LEAVE MASTER (UNCHANGED)
           ===================================================== */

        $leaveMaster = $this->leaveMaster->builder()
            ->whereHas('session_year', function ($q) use ($month, $year) {
                $q->where(function ($q) use ($month, $year) {
                    $q->whereMonth('start_date', '<=', $month)
                        ->whereYear('start_date', $year);
                })->orWhere(function ($q) use ($month, $year) {
                    $q->whereMonth('start_date', '>=', $month)
                        ->whereYear('end_date', '<=', $year);
                });
            })->first();

        /* =====================================================
           STAFF QUERY (UNCHANGED)
           ===================================================== */

        $sql = $this->staff->builder()->with([
            'user',
            'staffSalary.payrollSetting',
            'leave' => function ($q) use ($month, $year) {
                $q->where('status', 1)->withCount([
                    'leave_detail as full_leave' => function ($q) use ($month, $year) {
                        $q->whereMonth('date', $month)
                            ->whereYear('date', $year)
                            ->where('type', 'Full');
                    }
                ])->withCount([
                            'leave_detail as half_leave' => function ($q) use ($month, $year) {
                                $q->whereMonth('date', $month)
                                    ->whereYear('date', $year)
                                    ->whereNot('type', 'Full');
                            }
                        ]);
            }
        ])->whereHas('user', function ($q) {
            $q->whereNull('deleted_at')->Owner();
        })->when($search, function ($query) use ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%");
            });
        });

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->get();

        /* =====================================================
           PAYROLL CALCULATION LOOP (100% ORIGINAL)
           ===================================================== */

        $rows = [];
        $no = 1;

        foreach ($res as $row) {

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;

            $salary = $row->salary;
            $salary_deduction = 0;

            $full_leave = isset($row->leave) ? $row->leave->sum('full_leave') : 0;
            $half_leave = isset($row->leave) ? ($row->leave->sum('half_leave') / 2) : 0;
            $total_leave = $full_leave + $half_leave;

            $tempRow['total_leaves'] = $total_leave;

            /* ===== Allowances & Deductions (UNCHANGED) ===== */

            $allowanceAmount = [];
            $deductionAmount = [];

            foreach ($row->staffSalary as $salaryItem) {
                $payrollSettingItem = $salaryItem->payrollSetting;
                if (!$payrollSettingItem)
                    continue;

                if ($payrollSettingItem->type === 'allowance') {
                    if (isset($salaryItem->percentage)) {
                        $allowanceAmount[] = ($salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $allowanceAmount[] = $salaryItem->amount;
                    }
                } elseif ($payrollSettingItem->type === 'deduction') {

                    if ($payrollSettingItem->name == 'Transportation Deduction') {
                        $requestedDate = Carbon::create(null, $month, 1)->startOfMonth();
                        $startDate = Carbon::createFromFormat($schoolSetting['date_format'] . ' ' . $schoolSetting['time_format'], $salaryItem->updated_at)->startOfMonth();
                        $endDate = Carbon::parse($salaryItem->expiry_date)->endOfMonth();

                        if (!$requestedDate->between($startDate, $endDate)) {
                            continue;
                        }
                    }

                    if (isset($salaryItem->percentage)) {
                        $deductionAmount[] = ($salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $deductionAmount[] = $salaryItem->amount;
                    }
                }
            }

            $totalAllowanceAmount = array_sum($allowanceAmount);
            $totalDeductionAmount = array_sum($deductionAmount);

            /* ===== Expense (same logic, reused data) ===== */

            $expense = $expenses->get($row->id);

            if ($expense) {
                $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/' . $expense->id), ['btn-gradient-info'], ['title' => trans("slip"), 'target' => '_blank']);

                // delete expense
                $operate .= BootstrapTableService::trashButton(route('payroll.destroy', $expense->id));

                $salary = $expense->getRawOriginal('basic_salary');
                $tempRow['salary'] = $expense->basic_salary;
                $tempRow['status'] = 1;
                $tempRow['paid_leaves'] = $expense->paid_leaves;

                if ($expense->paid_leaves < $total_leave && $expense->paid_leaves !== null) {
                    $unpaid_leave = $total_leave - $expense->paid_leaves;
                    $salary_deduction = ($salary / 30) * $unpaid_leave;
                }
                $tempRow['operate'] = $operate;
                $tempRow['salary_deduction'] = number_format($salary_deduction, 2);
                $tempRow['net_salary'] = $expense->amount;

            } elseif ($leaveMaster) {

                if ($leaveMaster->leaves < $total_leave && $leaveMaster->leaves !== null) {
                    $unpaid_leave = $total_leave - $leaveMaster->leaves;
                    $salary_deduction = ($salary / 30) * $unpaid_leave;
                }

                $tempRow['salary_deduction'] = number_format($salary_deduction, 2);
                $tempRow['net_salary'] = $salary - $salary_deduction + $totalAllowanceAmount - $totalDeductionAmount;

            } else {
                $tempRow['net_salary'] = $salary + $totalAllowanceAmount - $totalDeductionAmount;
            }

            /* ===== Transportation deduction (ORIGINAL day logic) ===== */

            $transportationdeduction = 0;
            $staffTransportPayments = $transportationPayments->get($row->user_id, collect());

            foreach ($staffTransportPayments as $transportationPayment) {

                $startcustomdate = Carbon::create(
                    $year,
                    $month,
                    (int) date('d', strtotime($transportationPayment->created_at))
                );

                $endcustomdate = Carbon::create(
                    $year,
                    $month,
                    (int) date('d', strtotime($transportationPayment->expiry_date))
                );

                if (
                    $transportationPayment->created_at >= $startcustomdate ||
                    $transportationPayment->expiry_date >= $endcustomdate
                ) {
                    $transportationdeduction += $transportationPayment->included_amount;
                }
            }
            if (!$expense) {
                $tempRow['net_salary'] -= $transportationdeduction;
            }
            $tempRow['deductions'] = number_format(
                $totalDeductionAmount + $transportationdeduction,
                2
            );
            $tempRow['allowances'] = number_format($totalAllowanceAmount, 2);

            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function slip_index()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        try {
            $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
            $currentSessionYear = $this->cache->getDefaultSessionYear();

            $sessionYears = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->get();

            return view('payroll.list', compact('sessionYear', 'currentSessionYear', 'sessionYears'));
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'Payroll Controller -> Slip Index method');
            ResponseService::errorResponse();
        }
    }

    public function slip_list()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $sessionYearId = request('session_year_id');

        $sql = $this->expense->builder()->where('staff_id', Auth::user()->staff->id)
            ->where(function ($q) use ($search) {
                $q->when($search, function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")
                        ->orWhere('basic_salary', 'LIKE', "%$search%")
                        ->orWhere('amount', 'LIKE', "%$search%")
                        ->where('staff_id', Auth::user()->staff->id);
                });
            })

            ->when($sessionYearId, function ($q) use ($sessionYearId) {
                $q->where('session_year_id', $sessionYearId);
            })
            ->where('staff_id', Auth::user()->staff->id);

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
            $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/' . $row->id), ['btn-gradient-info'], ['title' => trans("slip"), 'target' => '_blank']);
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function slip($id = null)
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        try {
            $schoolSetting = $this->cache->getSchoolSettings();
            $data = explode("storage/", $schoolSetting['horizontal_logo'] ?? '');
            $schoolSetting['horizontal_logo'] = end($data);

            if ($schoolSetting['horizontal_logo'] == null) {
                $systemSettings = $this->cache->getSystemSettings();
                $data = explode("storage/", $systemSettings['horizontal_logo'] ?? '');
                $schoolSetting['horizontal_logo'] = end($data);
            }

            // Salary
            $salary = $this->expense->builder()->with('staff.user:id,first_name,last_name', 'staff_payroll.payroll_setting')->where('id', $id)->first();
            if (!$salary) {
                return redirect()->back()->with('error', trans('no_data_found'));
            }
            // transportation deduction
            $transportationPayments = TransportationPayment::where('user_id', $salary->staff->user_id)
                ->whereDate('created_at', '<=', Carbon::create($salary->year, $salary->month, 1)->endOfMonth())
                ->whereDate('expiry_date', '>=', Carbon::create($salary->year, $salary->month, 1)->endOfMonth())
                ->get();

            // Get total leaves
            $leaves = $this->leave->builder()->where('status', 1)->where('user_id', $salary->staff->user_id)->withCount([
                'leave_detail as full_leave' => function ($q) use ($salary) {
                    $q->whereMonth('date', $salary->month)->whereYear('date', $salary->year)->where('type', 'Full');
                }
            ])->withCount([
                        'leave_detail as half_leave' => function ($q) use ($salary) {
                            $q->whereMonth('date', $salary->month)->whereYear('date', $salary->year)->whereNot('type', 'Full');
                        }
                    ])->get();

            $allow_leaves = 0;
            if ($salary) {
                $allow_leaves = $salary->paid_leaves;
            }

            $total_leaves = $leaves->sum('full_leave') + ($leaves->sum('half_leave') / 2);
            // Total days
            $days = Carbon::now()->year($salary->year)->month($salary->month)->daysInMonth;

            $pdf = PDF::loadView('payroll.slip', compact('schoolSetting', 'salary', 'total_leaves', 'days', 'allow_leaves', 'transportationPayments'));
            return $pdf->stream($salary->title . '-' . $salary->staff->user->full_name . '.pdf');
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('payroll-delete');
        try {
            $expense = $this->expense->builder()->where('id', $id)->first();
            if (!$expense) {
                ResponseService::errorResponse('Expense not found');
            }
            $this->sessionYearsTrackingsService->deleteSessionYearsTracking('App\Models\Expense', $expense->id, Auth::user()->id, $expense->session_year_id, Auth::user()->school_id);
            $this->expense->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Payroll Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }

}
