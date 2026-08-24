<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Students;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;

class ConsentFormController extends Controller
{
    /**
     * Display the main view.
     */
    public function index()
    {
        if (!Auth::user()->can('consent-form-list')) {
            return ResponseService::noPermissionThenRedirect('consent-form-list');
        }

        return view('consent_forms.index');
    }

    /**
     * Return list for the datatable.
     */
    public function list(Request $request)
    {
        if (!Auth::user()->can('consent-form-list')) {
            return ResponseService::noPermissionThenSendJson('consent-form-list');
        }

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = Students::with(['user', 'class_section.class', 'class_section.section']);

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('first_name', 'LIKE', "%$search%")
                        ->orWhere('last_name', 'LIKE', "%$search%");
                });
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_name'] = $row->user ? $row->user->first_name . ' ' . $row->user->last_name : '-';
            
            $class_name = '-';
            if ($row->class_section && $row->class_section->class) {
                $class_name = $row->class_section->class->name . ' - ' . ($row->class_section->section->name ?? '');
            }
            $tempRow['class_section'] = $class_name;
            
            $tempRow['consent_form_date'] = $row->consent_form_date ? date('Y-m-d h:i A', strtotime($row->consent_form_date)) : '-';
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
