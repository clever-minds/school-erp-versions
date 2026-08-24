<?php

namespace App\Http\Controllers;

use App\Models\AuditCategory;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuditCategoryController extends Controller
{
    public function index()
    {
        return view('audit_categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:audit_categories,name'
        ]);
        AuditCategory::create($request->all());
        return ResponseService::successResponse('Data Stored Successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:audit_categories,name,' . $id
        ]);
        $category = AuditCategory::findOrFail($id);
        $category->update($request->all());
        return ResponseService::successResponse('Data Updated Successfully');
    }

    public function destroy($id)
    {
        try {
            AuditCategory::findOrFail($id)->delete();
            return ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, 'AuditCategoryController -> destroy');
        }
    }

    public function show($id)
    {
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = AuditCategory::when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%");
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = BootstrapTableService::editButton(route('audit-categories.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('audit-categories.destroy', $row->id));
            
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
