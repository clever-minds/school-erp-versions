<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ReminderController extends Controller
{
    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        $this->cache = $cache;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['reminder-create', 'reminder-list']);
        $current_sessionYear = $this->cache->getDefaultSessionYear();
        $class_sections = app(\App\Repositories\ClassSection\ClassSectionInterface::class)->builder()->with('class', 'class.stream', 'section', 'medium')->get();
        return view('reminders.index', compact('current_sessionYear', 'class_sections'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('reminder-create');
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date',
            'title' => 'required',
            'class_section_id' => 'nullable|exists:class_sections,id',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            $data['school_id'] = Auth::user()->school_id;
            Reminder::create($data);
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Reminder Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenRedirect('reminder-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = Reminder::owner()->with('class_section.class', 'class_section.section', 'class_section.medium')
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
            $operate = BootstrapTableService::editButton(route('reminders.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('reminders.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $tempRow['class_section_name'] = $row->class_section ? $row->class_section->full_name : __('All Classes');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('reminder-edit');
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date',
            'title' => 'required',
            'class_section_id' => 'nullable|exists:class_sections,id',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $reminder = Reminder::owner()->findOrFail($id);
            $reminder->update($request->all());
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Reminder Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('reminder-delete');
        try {
            $reminder = Reminder::owner()->findOrFail($id);
            $reminder->delete();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Reminder Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }
}
