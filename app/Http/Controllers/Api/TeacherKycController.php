<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherDocument;
use App\Models\Staff;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TeacherKycController extends Controller
{
    public function getKycStatus()
    {
        try {
            $user = Auth::user();
            $documents = TeacherDocument::where('user_id', $user->id)->get();
            
            $requiredDocs = ['id_proof', 'address_proof', 'marksheet', 'degree', 'experience_letter'];
            $status = [];

            foreach ($requiredDocs as $docType) {
                $doc = $documents->where('type', $docType)->first();
                $status[] = [
                    'type' => $docType,
                    'is_uploaded' => $doc ? true : false,
                    'status' => $doc ? $doc->status : null, // 0: Pending, 1: Approved, 2: Rejected
                    'file_url' => $doc ? $doc->file_url : null,
                    'rejection_reason' => $doc ? $doc->rejection_reason : null
                ];
            }

            return ResponseService::successResponse('KYC status fetched successfully', [
                'documents' => $status,
                'kyc_completed' => $user->staff->kyc_completed
            ]);
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e);
        }
    }

    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:id_proof,address_proof,marksheet,degree,experience_letter',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

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
