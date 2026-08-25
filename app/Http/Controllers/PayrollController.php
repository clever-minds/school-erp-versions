<?php

namespace App\Http\Controllers;

use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Staff\StaffInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayrollController extends Controller
{
    private SessionYearInterface $sessionYear;
    private StaffInterface $staff;
    private ExpenseInterface $expense;

    public function __construct(SessionYearInterface $sessionYear, StaffInterface $staff, ExpenseInterface $expense)
    {
        $this->sessionYear = $sessionYear;
        $this->staff = $staff;
        $this->expense = $expense;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sessionYear = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->first();
        $sessionYear = date('Y', strtotime($sessionYear->start_date));

        return view('payroll.index', compact('sessionYear'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');

        $request->validate([
            'staff_id' => 'required',
            'date' => 'required'
        ],[
            'staff_id.required' => trans('no_records_found')
        ]);

        try {
            DB::beginTransaction();
            $sessionYear = app(CachingService::class)->getDefaultSessionYear();
            $data = array();
            foreach ($request->staff_id as $key => $salary) {
                $data[] = [
                    'title' => Carbon::create()->month($request->month)->format('F') .' - '. $request->year,
                    'description' => 'Salary',
                    'month' => $request->month,
                    'year' => $request->year,
                    'staff_id' => $key,
                    'amount' => $salary,
                    'session_year_id' => $sessionYear->id,
                    'date' => date('Y-m-d', strtotime($request->date)),
                ];
            }
            $this->expense->upsert($data,['staff_id','month','year'],['amount','session_year_id']);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Payroll Controller -> Store method');
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payroll  $payroll
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $month = request('month');
        $year = request('year');

        $sql = $this->staff->builder()->has('user')->with('user','expense')->whereHas('user',function($q){
            $q->Owner();
        })
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->orwhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orwhere('last_name', 'LIKE', "%$search%");
                    });
                });
                });
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $status = 0;
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;

            if (count($row->expense)) {
                $expense = $row->expense()->where('month', $month)->where('year', $year)->first();

                if ($expense) {
                    $status = 1;
                    $tempRow['salary'] = $expense->getRawOriginal('amount');
                }
            }
            $tempRow['status'] = $status;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payroll  $payroll
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payroll  $payroll
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-edit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payroll  $payroll
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenSendJson('payroll-delete');
    }
}
