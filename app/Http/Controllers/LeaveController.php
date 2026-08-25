<?php

namespace App\Http\Controllers;

use App\Repositories\Leave\LeaveInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class LeaveController extends Controller
{

    private LeaveInterface $leave;
    private SessionYearInterface $sessionYear;

    public function __construct(LeaveInterface $leave, SessionYearInterface $sessionYear)
    {
        $this->leave = $leave;
        $this->sessionYear = $sessionYear;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-list');

        $sessionYear = $this->sessionYear->builder()->pluck('name','id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();

        return view('leave.index',compact('sessionYear','current_session_year'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-create');
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
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-create');

        $request->validate([
            'reason'  => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
        ]);

        try {
            DB::beginTransaction();
            $sessionYear = app(CachingService::class)->getDefaultSessionYear();
            $data = [
                'user_id' => Auth::user()->id,
                'reason' => $request->reason,
                'from_date' => date('Y-m-d',strtotime($request->from_date)),
                'to_date' => date('Y-m-d',strtotime($request->to_date)),
                'session_year_id' => $sessionYear->id
            ];
            $this->leave->create($data);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Leave Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Leave  $leave
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        $sql = $this->leave->builder()->where('user_id',Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                });
                });
            });

        if ($session_year_id) {
            $sql->where('session_year_id',$session_year_id);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            if ($row->status == 0) {
                $operate .= BootstrapTableService::editButton(route('leave.update', $row->id));
                $operate .= BootstrapTableService::deleteButton(route('leave.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Leave  $leave
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Leave  $leave
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('leave-edit');

        $request->validate([
            'reason'  => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
        ]);
        try {
            DB::beginTransaction();
            $data = [
                'reason' => $request->reason,
                'from_date' => date('Y-m-d',strtotime($request->from_date)),
                'to_date' => date('Y-m-d',strtotime($request->to_date)),
            ];
            $this->leave->update($id, $data);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Leave Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Leave  $leave
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noAnyPermissionThenRedirect(['leave-delete','approve-leave']);
        try {
            DB::beginTransaction();
            $this->leave->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Leave Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }

    public function leave_request()
    {
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('approve-leave');

        $sessionYear = $this->sessionYear->builder()->pluck('name','id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();
        return view('leave.leave_request',compact('sessionYear','current_session_year'));
    }

    public function leave_request_show()
    {
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('approve-leave');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        $sql = $this->leave->builder()->with('user')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%")->orwhereHas('user',function($q) use($search) {
                        $q->whereRaw('concat(first_name," ",last_name) like ?', "%$search%");
                    });
                });
                });
            });

        if ($session_year_id) {
            $sql->where('session_year_id',$session_year_id);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            $operate .= BootstrapTableService::editButton(route('leave.status.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('leave.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function leave_status_update(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Staff Leave Management');
        ResponseService::noPermissionThenRedirect('approve-leave');
        try {
            DB::beginTransaction();
            $this->leave->update($request->id,['status' => $request->status]);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Leave Controller -> Leave Status Method");
            ResponseService::errorResponse();
        }
    }

}
