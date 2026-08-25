<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Auth;
use Exception;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // check staff management feature is enabled
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenRedirect('task-list');

        $users = User::where('status', 1)->whereNot('id', Auth::user()->id)->has('staff')->get();


        return view('task.index', compact('users'));
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
        ResponseService::noFeatureThenSendJson('Staff Management');
        ResponseService::noAnyPermissionThenSendJson(['task-create', 'task-assign']);

        $request->validate(
            [
                'title' => 'required',
                'description' => 'required',
                'due_date' => 'required',
                'user_id' => 'required_if:type,2',
            ],
            [
                'user_id.required_if' => __('Please select user'),
            ]
        );

        $isAdminAssigned = 0;
        if (Auth::user()->can('task-assign')) {
            $isAdminAssigned = 1;
        }

        try {
            $task = new Task();
            $task->user_id = $request->type == 1 ? Auth::user()->id : $request->user_id;
            $task->title = $request->title;
            $task->description = $request->description;
            $task->due_date = date('Y-m-d', strtotime($request->due_date));
            $task->is_admin_assigned = $isAdminAssigned;
            $task->assigned_by = Auth::user()->id;
            $task->save();

            if ($request->type == 2) {
                // send app push notification
                send_notification(
                    [$request->user_id],
                    'New Task Assigned',
                    $task->title,
                    'task_assigned',
                    ['task_id' => $task->id]
                );
            }

            ResponseService::successResponse('Task created successfully');
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse('Something went wrong');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Staff Management');
        ResponseService::noPermissionThenSendJson('task-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'due_date');
        $order = request('order', 'ASC');

        $sql = Task::with('user');
        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('title', 'LIKE', "%$search%")
                    ->orwhere('description', 'LIKE', "%$search%")
                    ->orwhere('due_date', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orwhere('last_name', 'LIKE', "%$search%")
                            ->orwhere('email', 'LIKE', "%$search%")
                            ->orwhere('dob', 'LIKE', "%$search%")
                            ->orWhereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                    });
            });
        }

        if (!Auth::user()->can('task-assign')) {
            $sql->where('user_id', Auth::user()->id);
        }

        if (!Auth::user()->hasRole('School Admin')) {
            $sql = $sql->whereNot('user_id', Auth::user()->school->admin_id);
        }

        if ($request->status) {
            if ($request->status == 'overdue') {
                $sql->where('status', '!=', 'completed')
                    ->where('due_date', '<', now()->format('Y-m-d'));
            } else {
                $sql->where('status', $request->status);
            }
        }

        if ($request->type) {
            if ($request->type == 'staff_task') {
                $sql->whereNot('user_id', Auth::user()->id);
            } else if ($request->type == 'my_task') {
                $sql->where('user_id', Auth::user()->id);
            }
        }

        if ($request->user_id) {
            $sql->where('user_id', $_GET['user_id']);
        }

        $total = $sql->count();
        if ($offset >= $total && $total > 0) {
            $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
            $offset = $lastPage;
        }
        $sql->orderByRaw("CASE WHEN status = 'in_progress' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END ASC")
            ->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {

            $operate = BootstrapTableService::editButton(route('tasks.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('tasks.destroy', $row->id));
            $tempRow = $row->toArray();


            $tempRow['is_admin_assigned'] = 2;

            if (Auth::user()->id != $row->user_id) {
                $tempRow['is_admin_assigned'] = 1;
            }

            $tempRow['no'] = $no++;
            $tempRow['plain_due_date'] = $row->getRawOriginal('due_date');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenSendJson('Staff Management');
        ResponseService::noPermissionThenSendJson('task-edit');

        $request->validate(
            [
                'title' => 'required',
                'description' => 'required',
                'due_date' => 'required',
                'user_id' => 'required_if:type,2',
            ],
            [
                'user_id.required_if' => __('Please select user'),
            ]
        );

        $isAdminAssigned = 0;
        if (Auth::user()->can('task-assign')) {
            $isAdminAssigned = 1;
        }

        $task = Task::findOrFail($id);
        $task->user_id = $request->type == 1 ? Auth::user()->id : $request->user_id;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->due_date = date('Y-m-d', strtotime($request->due_date));
        $task->is_admin_assigned = $isAdminAssigned;
        $task->save();

        ResponseService::successResponse('Task Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenSendJson('Staff Management');
        ResponseService::noPermissionThenSendJson('task-delete');

        $task = Task::findOrFail($id);
        $task->delete();

        ResponseService::successResponse('Task Deleted successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        ResponseService::noFeatureThenSendJson('Staff Management');
        ResponseService::noPermissionThenSendJson('task-edit');

        try {
            $task = Task::findOrFail($id);
            $task->status = $request->status;
            $task->save();
            ResponseService::successResponse('Status updated successfully');
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse('Something went wrong');
        }
    }
}
