<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SessionYear;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Throwable;

class EventController extends Controller
{
    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        $this->cache = $cache;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['event-create', 'event-list']);
        $current_sessionYear = $this->cache->getDefaultSessionYear();
        $class_sections = app(\App\Repositories\ClassSection\ClassSectionInterface::class)->builder()->with('class', 'class.stream', 'section', 'medium')->get();
        return view('events.index', compact('current_sessionYear', 'class_sections'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('event-create');
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
            Event::create($data);
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Event Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenRedirect('event-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = Event::owner()->with('class_section.class', 'class_section.section', 'class_section.medium')
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
            $operate = BootstrapTableService::editButton(route('events.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('events.destroy', $row->id));

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
        ResponseService::noPermissionThenSendJson('event-edit');
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date',
            'title' => 'required',
            'class_section_id' => 'nullable|exists:class_sections,id',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $event = Event::owner()->findOrFail($id);
            $event->update($request->all());
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Event Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('event-delete');
        try {
            $event = Event::owner()->findOrFail($id);
            $event->delete();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Event Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }
}
