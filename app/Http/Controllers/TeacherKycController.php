<?php

namespace App\Http\Controllers;

use App\Models\TeacherDocument;
use App\Models\Staff;
use App\Models\User;
use App\Services\ResponseService;
use App\Services\BootstrapTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TeacherKycController extends Controller
{
    public function index()
    {
        ResponseService::noPermissionThenRedirect('staff-kyc-manage');
        return view('staff.kyc_index');
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('staff-kyc-manage');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search');

        $sql = User::role('Teacher')->with(['staff', 'teacher_documents']);

        if ($search) {
            $sql->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('mobile', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($res as $user) {
            $docs = $user->teacher_documents;
            $requiredDocs = ['id_proof', 'address_proof', 'marksheet', 'degree', 'experience_letter'];
            $docStatus = "";
            
            foreach ($requiredDocs as $docType) {
                $doc = $docs->where('type', $docType)->first();
                $color = 'text-danger';
                $icon = 'fa-times-circle';
                if ($doc) {
                    if ($doc->status == 1) {
                        $color = 'text-success';
                        $icon = 'fa-check-circle';
                    } elseif ($doc->status == 0) {
                        $color = 'text-warning';
                        $icon = 'fa-clock-o';
                    }
                }
                $docStatus .= "<span class='{$color} mr-2' title='".ucfirst(str_replace('_', ' ', $docType))."'><i class='fa {$icon}'></i> ".ucfirst(substr($docType, 0, 2))."</span>";
            }

            $operate = BootstrapTableService::button('fa fa-eye', route('staff.kyc.details', $user->id), ['btn-gradient-info'], ['title' => __('View Details')]);

            $rows[] = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'status' => $docStatus,
                'kyc_completed' => $user->staff->kyc_completed ? 'Completed' : 'Pending',
                'operate' => $operate
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function details($id)
    {
        ResponseService::noPermissionThenRedirect('staff-kyc-manage');
        $user = User::role('Teacher')->with(['staff', 'teacher_documents'])->findOrFail($id);
        return view('staff.kyc_details', compact('user'));
    }

    public function updateStatus(Request $request)
    {
        ResponseService::noPermissionThenSendJson('staff-kyc-manage');
        $request->validate([
            'document_id' => 'required',
            'status' => 'required|in:1,2', // 1: Approve, 2: Reject
            'rejection_reason' => 'required_if:status,2'
        ]);

        try {
            DB::beginTransaction();
            $doc = TeacherDocument::findOrFail($request->document_id);
            $doc->update([
                'status' => $request->status,
                'rejection_reason' => $request->status == 2 ? $request->rejection_reason : null
            ]);

            // Check if all docs are approved for this user
            $user = User::with('teacher_documents')->findOrFail($doc->user_id);
            $requiredDocs = ['id_proof', 'address_proof', 'marksheet', 'degree', 'experience_letter'];
            $allApproved = true;
            foreach ($requiredDocs as $type) {
                $d = $user->teacher_documents->where('type', $type)->where('status', 1)->first();
                if (!$d) {
                    $allApproved = false;
                    break;
                }
            }

            if ($allApproved) {
                $user->staff->update(['kyc_completed' => 1]);
            } else {
                $user->staff->update(['kyc_completed' => 0]);
            }

            DB::commit();
            return ResponseService::successResponse('Document status updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ResponseService::logErrorResponse($e);
        }
    }

    public function teacherKyc()
    {
        ResponseService::noPermissionThenRedirect('staff-kyc-upload');
        $user = Auth::user()->load('teacher_documents');
        return view('teacher.kyc', compact('user'));
    }

    public function teacherUpload(Request $request)
    {
        ResponseService::noPermissionThenSendJson('staff-kyc-upload');
        $request->validate([
            'type' => 'required|in:id_proof,address_proof,marksheet,degree,experience_letter',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $user = Auth::user();
            $type = $request->type;
            $file = $request->file('file');

            $path = $file->store('teacher_documents', 'public');

            TeacherDocument::updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                [
                    'file_url' => $path,
                    'status' => 0, // Reset to pending
                    'rejection_reason' => null,
                    'school_id' => $user->school_id
                ]
            );

            return ResponseService::successResponse('Document uploaded successfully. It is now pending approval.');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e);
        }
    }
}
