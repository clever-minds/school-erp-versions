<?php

namespace App\Http\Controllers;

use App\Models\AuditQuestion;
use App\Models\AuditOptionGroup;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuditQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['audit-question-list','audit-question-create']);
        $categories = \App\Models\AuditCategory::all();
        $optionGroups = AuditOptionGroup::all();
        return view('audit_questions.index', compact('categories', 'optionGroups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('audit-question-create');
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'audit_category_id' => 'required|exists:audit_categories,id',
            'status' => 'nullable|in:0,1',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            AuditQuestion::create([
                'question' => $request->question,
                'audit_category_id' => $request->audit_category_id,
                'status' => $request->status ?? 1,
                'type' => $request->type,
                'custom_options' => $request->custom_options
            ]);
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "AuditQuestion Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        ResponseService::noPermissionThenRedirect('audit-question-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = AuditQuestion::with(['category'])
            ->when($search, function ($query) use ($search) {
                $query->where('question', 'LIKE', "%$search%")
                      ->orWhereHas('category', function($q) use($search) {
                          $q->where('name', 'LIKE', "%$search%");
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
            $operate = BootstrapTableService::editButton(route('audit-questions.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('audit-questions.destroy', $row->id));
            
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['category'] = $row->category ? $row->category->name : '-';

            $tempRow['status_text'] = $row->status == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('audit-question-edit');
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'audit_category_id' => 'required|exists:audit_categories,id',
            'status' => 'required|in:0,1',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $question = AuditQuestion::findOrFail($id);
            $question->update([
                'question' => $request->question,
                'audit_category_id' => $request->audit_category_id,
                'status' => $request->status,
                'type' => $request->type,
                'custom_options' => $request->custom_options
            ]);
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "AuditQuestion Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('audit-question-delete');
        try {
            AuditQuestion::findOrFail($id)->delete();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "AuditQuestion Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }
}
