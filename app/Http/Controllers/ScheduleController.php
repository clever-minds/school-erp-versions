<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ScheduleController extends Controller
{
    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        $this->cache = $cache;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['schedule-create', 'schedule-list']);
        $current_sessionYear = $this->cache->getDefaultSessionYear();
        return view('schedules.index', compact('current_sessionYear'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('schedule-create');
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            $data['school_id'] = Auth::user()->school_id;
            Schedule::create($data);
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Schedule Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenRedirect('schedule-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = Schedule::owner()
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('title', 'LIKE', "%$search%")
                        ->orWhere('description', 'LIKE', "%$search%")
                        ->orWhere('date', 'LIKE', "%$search%");
                }
            });

        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = $offset + 1;

        foreach ($res as $row) {
            $operate = BootstrapTableService::editButton(route('schedules.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('schedules.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('schedule-edit');
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $schedule = Schedule::owner()->findOrFail($id);
            $schedule->update($request->all());
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Schedule Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('schedule-delete');
        try {
            $schedule = Schedule::owner()->findOrFail($id);
            $schedule->delete();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Schedule Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }
}
