<?php

namespace App\Http\Controllers;

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
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;
use Throwable;

class PayrollController extends Controller {
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

    public function __construct(SessionYearInterface $sessionYear, StaffInterface $staff, ExpenseInterface $expense, LeaveMasterInterface $leaveMaster, CachingService $cache, SchoolSettingInterface $schoolSetting, LeaveInterface $leave, SessionYearInterface $sessionYearInterface, StaffSalaryInterface $staffSalary, StaffPayrollInterface $staffPayroll, SessionYearsTrackingsService $sessionYearsTrackingsService) {
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

    public function index() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sessionYear = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->first();
        $sessionYear = date('Y', strtotime($sessionYear->start_date));
        // Get months starting from session year
        $months = sessionYearWiseMonth();
        

        return view('payroll.index', compact('sessionYear', 'months'));
    }

    public function create() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');
    }

    public function store(Request $request) {
   
        ResponseService::noFeatureThenSendJson('Expense Management');
        ResponseService::noPermissionThenSendJson('payroll-create');

        $request->validate([
            'net_salary' => 'required',

            'user_id' => 'required'
        ], [
            'net_salary.required' => trans('no_records_found'),
            'user_id.required' => trans('Please select at least one record')
        ]);

        try {
            DB::beginTransaction();
            $user_ids = explode(",",$request->user_id);
            
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
            $staff_payroll_data  = array();
            foreach ($user_ids as $key => $user_id) {
                $data = [
                    'title'           => Carbon::create()->month($request->month)->format('F') . ' - ' . $request->year,
                    'description'     => 'Salary',
                    'month'           => $request->month,
                    'year'            => $request->year,
                    'staff_id'        => $user_id,
                    'basic_salary'    => $request->basic_salary[$user_id],
                    'paid_leaves'     => $request->paid_leave[$user_id],
                    'amount'          => $request->net_salary[$user_id],
                    'session_year_id' => $sessionYearInterface->id,
                    'date'            => $endDate->format('Y-m-d'),
                ];

                $expense = $this->expense->updateOrCreate(['staff_id' => $data['staff_id'], 'month' => $data['month'], 'year' => $data['year']], ['amount' => $data['amount'], 'session_year_id' => $data['session_year_id'],'basic_salary' => $data['basic_salary'],'date' => $data['date'],'title' => $data['title'],'paid_leaves' => $data['paid_leaves'],'description' => $data['description']]);

                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Expense', $expense->id, Auth::user()->id, $sessionYearInterface->id, Auth::user()->school_id, null);

                $staffSalary = $this->staffSalary->builder()->where('staff_id',$user_id)->get();
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
          
            $title = 'Payroll Update !!!' ;
            $body = "Your Payroll has been Updated.";
            $type = "assignment";

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

    public function show() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $month = request('month');
        $year = request('year');

        

        $startDate = Carbon::create(null, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::create(null, $month, 1)->endOfMonth()->format('Y-m-d');

        $leaveMaster = $this->leaveMaster->builder()->whereHas('session_year', function ($q) use ($month, $year) {
            $q->where(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '<=', $month)->whereYear('start_date', $year);
            })->orWhere(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '>=', $month)->whereYear('end_date', '<=', $year);
            });
        })->first();

        $sql = $this->staff->builder()->with(['user','staffSalary.payrollSetting', 'expense.staff_payroll.payroll_setting'])->whereHas('user', function ($q) {
            $q->whereNull('deleted_at')->Owner();
        })->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->orwhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orwhere('last_name', 'LIKE', "%$search%");
                });
            });
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        $userIds = $res->pluck('user.id')->toArray();
        $allAttendances = \App\Models\StaffAttendance::whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id');

        $allLeaves = \App\Models\LeaveDetail::whereHas('leave', function($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)->where('status', 1);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->with('leave:id,user_id')
            ->get()
            ->groupBy('leave.user_id');

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        // Calculate non-working days (Sundays + Holidays)
        $startOfMonthObj = Carbon::create($year, $month, 1);
        $endOfMonthObj = $startOfMonthObj->copy()->endOfMonth();
        $startOfMonthStr = $startOfMonthObj->format('Y-m-d');
        $endOfMonthStr = $endOfMonthObj->format('Y-m-d');
        $daysInMonth = $startOfMonthObj->daysInMonth;
        
        $holidays = \App\Models\Holiday::where(function($q) use ($startOfMonthStr, $endOfMonthStr) {
            $q->whereBetween('date', [$startOfMonthStr, $endOfMonthStr])
              ->orWhereBetween('end_date', [$startOfMonthStr, $endOfMonthStr])
              ->orWhere(function($q2) use ($startOfMonthStr, $endOfMonthStr) {
                  $q2->where('date', '<', $startOfMonthStr)->where('end_date', '>', $endOfMonthStr);
              });
        })->get();

        $holidayDates = [];
        foreach($holidays as $holiday) {
            $start = Carbon::parse($holiday->getRawOriginal('date'));
            $end = $holiday->getRawOriginal('end_date') ? Carbon::parse($holiday->getRawOriginal('end_date')) : $start->copy();
            for($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->month == $month && $d->year == $year) {
                    $holidayDates[] = $d->format('Y-m-d');
                }
            }
        }

        $sundayDates = [];
        for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
            if ($d->isSunday()) {
                $sundayDates[] = $d->format('Y-m-d');
            }
        }
        $nonWorkingDates = array_unique(array_merge($holidayDates, $sundayDates));
        $total_working_days = $daysInMonth - count($nonWorkingDates);

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $salary_deduction = 0;
            $salary = $row->salary;
            
            $userAttendances = $allAttendances->has($row->user->id) ? $allAttendances[$row->user->id] : collect();
            
            $total_present = 0;

            $attendance_map = [];
            // Process Attendance records (Only P, W and H give present marks)
            foreach ($userAttendances as $att) {
                $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                $attendance_map[$dateStr] = $att->status;

                if ($att->status == 1) { // 1 = Present / WFH
                    $total_present += 1;
                } elseif ($att->status == 3) { // 3 = Half Day
                    $total_present += 0.5;
                }
            }

            $monthDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $monthDates[] = [
                    'date' => $dateStr,
                    'is_working' => !in_array($dateStr, $nonWorkingDates),
                ];
            }

            $sandwiched_days = 0;
            $blocks = [];
            $current_block = [];
            foreach ($monthDates as $index => $dayInfo) {
                if (!$dayInfo['is_working']) {
                    $current_block[] = $index;
                } else {
                    if (!empty($current_block)) {
                        $blocks[] = $current_block;
                        $current_block = [];
                    }
                }
            }
            if (!empty($current_block)) {
                $blocks[] = $current_block;
            }

            foreach ($blocks as $block) {
                $first_index = $block[0];
                $last_index = end($block);
                
                $preceding_absent = false;
                if ($first_index > 0) {
                    $prev_index = $first_index - 1;
                    $prev_date = $monthDates[$prev_index]['date'];
                    $status = isset($attendance_map[$prev_date]) ? $attendance_map[$prev_date] : null;
                    if (!in_array($status, [1, 2, 3])) {
                        $preceding_absent = true;
                    }
                }
                
                $following_absent = false;
                if ($last_index < count($monthDates) - 1) {
                    $next_index = $last_index + 1;
                    $next_date = $monthDates[$next_index]['date'];
                    $status = isset($attendance_map[$next_date]) ? $attendance_map[$next_date] : null;
                    if (!in_array($status, [1, 2, 3])) {
                        $following_absent = true;
                    }
                }
                
                if ($preceding_absent && $following_absent) {
                    $sandwiched_days += count($block);
                }
            }

            // Unmarked working days are treated as leaves/absent
            $total_leave = max(0, $total_working_days - $total_present) + $sandwiched_days;

            $tempRow['total_leaves'] = $total_leave;
            $tempRow['salary_deduction'] = number_format($salary_deduction, 2);
            $allowanceAmount = [];
            $deductionAmount = [];
            foreach ($row->staffSalary as $salaryItem) {
                $payrollSetting = $salaryItem->payrollSetting;
        
                if (!$payrollSetting) {
                    continue;
                }
        
                if ($payrollSetting->type === 'allowance') {
               
                    if (isset($salaryItem->percentage)) {
                        $allowanceAmount[] = ($salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $allowanceAmount[] = $salaryItem->amount;
                    }
                  
                } elseif ($payrollSetting->type === 'deduction') {
                 
                    if (isset($salaryItem->percentage)) {
                        $deductionAmount[] = ($salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $deductionAmount[] = $salaryItem->amount;
                    }
                 
                }
            }
            $totalAllowanceAmount = array_sum($allowanceAmount);
            $totalDeductionAmount = array_sum($deductionAmount);

            $tempRow['allowances'] = isset($totalAllowanceAmount) ? number_format($totalAllowanceAmount,2) : 0;
            $tempRow['deductions'] = isset($totalDeductionAmount) ? number_format($totalDeductionAmount,2) : 0;

            if (isset($row->expense)) {
                // TODO : this line can be converted into filter searching instead of searching from query
                $expense = $row->expense()->where('month', $month)->where('year', $year)->first();
                if ($expense) {
                    $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/'.$expense->id), ['btn-gradient-info'], ['title' => trans("slip"),'target' => '_blank']);
                    $status = 1;
                    $tempRow['salary'] = $expense->basic_salary;
                    $salary = $expense->getRawOriginal('basic_salary');

                    $tempRow['status'] = $status;
                    $tempRow['paid_leaves'] = $expense->paid_leaves !== null ? $expense->paid_leaves : '-';
                    $paid_leaves = $expense->paid_leaves ?? 0;
                    if ($paid_leaves < $total_leave) {
                        $unpaid_leave = $total_leave - $paid_leaves;
                        $per_day_salary = $salary / 30;
                        $salary_deduction = $unpaid_leave * $per_day_salary;
                        $tempRow['salary_deduction'] = number_format($salary_deduction,2);
                    } else {
                        $tempRow['salary_deduction'] = number_format(0, 2);
                    }
                    $tempRow['net_salary'] = $expense->amount;
                    $tempRow['operate'] = $operate;
                    // Calculate allowances & deductions
                    if (count($expense->staff_payroll)) {
                        $allowance = $deduction = 0;
                        foreach ($expense->staff_payroll as $key => $staff_payroll) {
                            if ($staff_payroll->payroll_setting->type == 'allowance') {
                                if ($staff_payroll->amount) {
                                    $allowance += $staff_payroll->amount;
                                } else {
                                    $allowance += ($expense->basic_salary * $staff_payroll->percentage) / 100;
                                }
                            } else if($staff_payroll->payroll_setting->type == 'deduction') {
                                if ($staff_payroll->amount) {
                                    $deduction += $staff_payroll->amount;
                                } else {
                                    $deduction += ($expense->basic_salary * $staff_payroll->percentage) / 100;
                                }
                            }
                        }

                        if ($expense->amount > ($expense->basic_salary + $allowance - $salary_deduction - $deduction)) {
                            $allowance += $expense->amount - ($expense->basic_salary + $allowance - $deduction - $salary_deduction);
                        }

                        $expected = $expense->basic_salary + $allowance - $deduction - $salary_deduction;

                        if ($expense->amount < $expected) {
                            $deduction += $expected - $expense->amount;
                        }

                        $tempRow['allowances'] = number_format($allowance ,2);
                        $tempRow['deductions'] = number_format($deduction ,2);
                    } else {
                        $allowance = 0;
                        $deduction = 0;
                        $extra = 0;
                        $extra_deduction = 0;


                        if ($expense->amount > ($expense->basic_salary + $allowance - $salary_deduction - $deduction)) {
                            $extra = $expense->amount - ($expense->basic_salary + $allowance - $deduction - $salary_deduction);
                            $allowance += $extra;
                        } else {
                            $extra = 0;
                        }

                        $expected = $expense->basic_salary + $allowance - $deduction - $salary_deduction;

                        if ($expense->amount < $expected) {
                            $extra_deduction = $expected - $expense->amount;
                            $deduction += $extra_deduction;
                        } else {
                            $extra_deduction = 0;
                        }

                        $tempRow['allowances'] = number_format($allowance ,2);
                        $tempRow['deductions'] = number_format($deduction ,2);
                    }

                } else {
                    $salary = $row->salary;
                    $paid_leaves = 0;
                    if ($leaveMaster) {
                        $tempRow['paid_leaves'] = $leaveMaster->leaves;
                        $paid_leaves = $leaveMaster->leaves ?? 0;
                    } else {
                        $tempRow['paid_leaves'] = '-';
                    }

                    if ($paid_leaves < $total_leave) {
                        $unpaid_leave = $total_leave - $paid_leaves;
                        $per_day_salary = $salary / 30;
                        $salary_deduction = $unpaid_leave * $per_day_salary;
                        $tempRow['salary_deduction'] = number_format($salary_deduction,2);
                    } else {
                        $tempRow['salary_deduction'] = number_format(0, 2);
                    }
                    
                    $tempRow['net_salary'] = $salary - $salary_deduction + $totalAllowanceAmount - $totalDeductionAmount;
                }
            }
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function slip_index()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        try {
            $sessionYear = $this->sessionYear->builder()->pluck('name','id');
            $currentSessionYear = $this->cache->getDefaultSessionYear();

            $sessionYears = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->get();

            return view('payroll.list',compact('sessionYear','currentSessionYear','sessionYears'));
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

        $sql = $this->expense->builder()->where('staff_id',Auth::user()->staff->id)
        ->where(function($q) use($search){
            $q->when($search, function($q) use($search) {
                $q->where('title','LIKE',"%$search%")
                ->orWhere('basic_salary','LIKE',"%$search%")
                ->orWhere('amount','LIKE',"%$search%")
                ->where('staff_id',Auth::user()->staff->id);
            });
        })
        
        ->when($sessionYearId, function($q) use($sessionYearId) {
            $q->where('session_year_id',$sessionYearId);
        })
        ->where('staff_id',Auth::user()->staff->id);

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/'.$row->id), ['btn-gradient-info'], ['title' => trans("slip"),'target' => '_blank']);
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
            $salary = $this->expense->builder()->with('staff.user:id,first_name,last_name','staff_payroll.payroll_setting')->where('id',$id)->first();
            if (!$salary) {
                return redirect()->back()->with('error',trans('no_data_found'));
            }
            // Get total leaves from StaffAttendance and LeaveDetail
            $startDate = Carbon::create($salary->year, $salary->month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::create($salary->year, $salary->month, 1)->endOfMonth()->format('Y-m-d');
            
            $userAttendances = \App\Models\StaffAttendance::where('user_id', $salary->staff->user_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $daysInMonth = Carbon::create($salary->year, $salary->month, 1)->daysInMonth;
            
            // Calculate non-working days
            $startOfMonthObj = Carbon::create($salary->year, $salary->month, 1);
            $endOfMonthObj = $startOfMonthObj->copy()->endOfMonth();
            
            $holidays = \App\Models\Holiday::where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('date', '<', $startDate)->where('end_date', '>', $endDate);
                  });
            })->get();

            $holidayDates = [];
            foreach($holidays as $holiday) {
                $start = Carbon::parse($holiday->getRawOriginal('date'));
                $end = $holiday->getRawOriginal('end_date') ? Carbon::parse($holiday->getRawOriginal('end_date')) : $start->copy();
                for($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    if ($d->month == $salary->month && $d->year == $salary->year) {
                        $holidayDates[] = $d->format('Y-m-d');
                    }
                }
            }

            $sundayDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                if ($d->isSunday()) {
                    $sundayDates[] = $d->format('Y-m-d');
                }
            }
            $nonWorkingDates = array_unique(array_merge($holidayDates, $sundayDates));
            $total_working_days = $daysInMonth - count($nonWorkingDates);

            $total_present = 0;

            $attendance_map = [];
            foreach ($userAttendances as $att) {
                $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                $attendance_map[$dateStr] = $att->status;

                if ($att->status == 1) {
                    $total_present += 1;
                } elseif ($att->status == 3) {
                    $total_present += 0.5;
                }
            }

            $monthDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $monthDates[] = [
                    'date' => $dateStr,
                    'is_working' => !in_array($dateStr, $nonWorkingDates),
                ];
            }

            $sandwiched_days = 0;
            $blocks = [];
            $current_block = [];
            foreach ($monthDates as $index => $dayInfo) {
                if (!$dayInfo['is_working']) {
                    $current_block[] = $index;
                } else {
                    if (!empty($current_block)) {
                        $blocks[] = $current_block;
                        $current_block = [];
                    }
                }
            }
            if (!empty($current_block)) {
                $blocks[] = $current_block;
            }

            foreach ($blocks as $block) {
                $first_index = $block[0];
                $last_index = end($block);
                
                $preceding_absent = false;
                if ($first_index > 0) {
                    $prev_index = $first_index - 1;
                    $prev_date = $monthDates[$prev_index]['date'];
                    $status = isset($attendance_map[$prev_date]) ? $attendance_map[$prev_date] : null;
                    if (!in_array($status, [1, 2, 3])) {
                        $preceding_absent = true;
                    }
                }
                
                $following_absent = false;
                if ($last_index < count($monthDates) - 1) {
                    $next_index = $last_index + 1;
                    $next_date = $monthDates[$next_index]['date'];
                    $status = isset($attendance_map[$next_date]) ? $attendance_map[$next_date] : null;
                    if (!in_array($status, [1, 2, 3])) {
                        $following_absent = true;
                    }
                }
                
                if ($preceding_absent && $following_absent) {
                    $sandwiched_days += count($block);
                }
            }

            $total_leave = max(0, $total_working_days - $total_present) + $sandwiched_days;

            $allow_leaves = 0;
            if ($salary) {
                $allow_leaves = $salary->paid_leaves;
            }

            $total_leaves = $total_leave;
            // Total days
            $days = Carbon::now()->year($salary->year)->month($salary->month)->daysInMonth;

            $pdf = PDF::loadView('payroll.slip',compact('schoolSetting','salary','total_leaves','days','allow_leaves'))->setPaper('a5');
            return $pdf->stream($salary->title.'-'.$salary->staff->user->full_name.'.pdf');
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

}
