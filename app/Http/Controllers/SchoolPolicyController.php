<?php

namespace App\Http\Controllers;

use App\Repositories\SchoolPolicy\SchoolPolicyInterface;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SchoolPolicyController extends Controller
{
    private SchoolPolicyInterface $schoolPolicy;

    public function __construct(SchoolPolicyInterface $schoolPolicy)
    {
        $this->schoolPolicy = $schoolPolicy;
    }

    public function index()
    {
        // ResponseService::noPermissionThenRedirect('school-policy-list'); // Add permission if needed
        return view('school-policy.index');
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('POLICY_REQUEST: ' . json_encode($request->all()));
        // ResponseService::noPermissionThenSendJson('school-policy-create');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            if ($request->hasFile('file')) {
                $data['file_url'] = $request->file('file')->store('school_policies', 'public');
            }
            $this->schoolPolicy->create($data);
            return ResponseService::successResponse('Policy created successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, "SchoolPolicyController -> store");
        }
    }

    public function show(Request $request)
    {
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $query = \App\Models\SchoolPolicy::where('school_id', auth()->user()->school_id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%");
            });
        }

        $total = $query->count();
        $res = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = BootstrapTableService::editButton(route('school-policy.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('school-policy.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function update(Request $request, $id)
    {
        // ResponseService::noPermissionThenSendJson('school-policy-edit');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            if ($request->hasFile('file')) {
                $data['file_url'] = $request->file('file')->store('school_policies', 'public');
            }
            $this->schoolPolicy->update($id, $data);
            return ResponseService::successResponse('Policy updated successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, "SchoolPolicyController -> update");
        }
    }

    public function destroy($id)
    {
        // ResponseService::noPermissionThenSendJson('school-policy-delete');
        try {
            $this->schoolPolicy->delete($id);
            return ResponseService::successResponse('Policy deleted successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, "SchoolPolicyController -> destroy");
        }
    }
}
