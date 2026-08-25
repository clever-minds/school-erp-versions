<?php

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\Subject;
use App\Models\Syllabus;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SyllabusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['syllabus-list']);

        $classes = ClassSchool::with('stream', 'medium', 'shift')->get();
        $subjects = Subject::with('medium')->get();


        return view('syllabus.index', compact('classes', 'subjects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        ResponseService::noAnyPermissionThenRedirect(['syllabus-create']);

        $validator = Validator::make($request->all(), [
            'class_id' => 'required',
            'subject_id' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        $is_exists = Syllabus::where('title', $request->title)->first();
        if ($is_exists) {
            ResponseService::errorResponse('Please enter a unique title for the syllabus.');
        }

        try {
            $syllabus = new Syllabus();
            $syllabus->class_id = $request->class_id;
            $syllabus->subject_id = $request->subject_id;
            $syllabus->title = $request->title;
            $syllabus->save();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Syllabus Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id = null, Request $request)
    {
        ResponseService::noPermissionThenRedirect('syllabus-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = Syllabus::where(function ($query) use ($search) {
            $query->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")->orwhere('title', 'LIKE', "%$search%")
                    ->orWhereHas('class', function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('subject', function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%$search%");
                    });
            });
        })
            ->with('class.stream', 'class.medium', 'class.shift', 'subject')->withCount('lesson_common');

        if ($request->class_id) {
            $sql->where('class_id', $request->class_id);
        }

        if ($request->subject_id) {
            $sql->where('subject_id', $request->subject_id);
        }

        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            //     $operate = BootstrapTableService::editButton(route('syllabus.update', $row->id));
            //     $operate .= BootstrapTableService::trashButton(route('syllabus.destroy', $row->id));

            $operate = BootstrapTableService::menuEditButton('edit', route('syllabus.update', $row->id));
            if ($row->status == 'active') {
                $operate .= BootstrapTableService::menuButton('inactive', route('syllabus.change-status', $row->id), ['syllabus-status'], []);
            } else {
                $operate .= BootstrapTableService::menuButton('active', route('syllabus.change-status', $row->id), ['syllabus-status'], []);
            }
            $operate .= BootstrapTableService::menuTrashButton('delete', route('syllabus.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = BootstrapTableService::menuItem($operate);
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Syllabus $syllabus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        ResponseService::noAnyPermissionThenRedirect(['syllabus-edit']);

        $validator = Validator::make($request->all(), [
            'class_id' => 'required',
            'subject_id' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        $is_exists = Syllabus::where('title', $request->title)->where('id', '!=', $id)->first();
        if ($is_exists) {
            ResponseService::errorResponse('Please enter a unique title for the syllabus.');
        }

        try {
            $syllabus = Syllabus::find($id);
            $syllabus->class_id = $request->class_id;
            $syllabus->subject_id = $request->subject_id;
            $syllabus->title = $request->title;
            $syllabus->save();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Syllabus Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ResponseService::noAnyPermissionThenRedirect(['syllabus-delete']);

        try {
            $syllabus = Syllabus::find($id);
            $syllabus->delete();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Syllabus Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function changeStatus($id)
    {
        ResponseService::noAnyPermissionThenRedirect(['syllabus-edit']);

        try {
            $syllabus = Syllabus::find($id);
            $syllabus->status = $syllabus->status == 'active' ? 'inactive' : 'active';
            $syllabus->save();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Syllabus Controller -> Change Status Method");
            ResponseService::errorResponse();
        }
    }
}
