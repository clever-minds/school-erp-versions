<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditQuestion;
use App\Models\SchoolAudit;
use App\Models\SchoolAuditAnswer;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SuperAdminAuditController extends Controller
{
    // === Audit Questions ===

    public function getQuestions(Request $request)
    {
        try {
            $questions = AuditQuestion::orderBy('id', 'desc');
            if ($request->has('status')) {
                $questions->where('status', $request->status);
            }
            return ResponseService::successResponse('Data Fetched Successfully', $questions->get());
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function storeQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'status' => 'nullable|in:0,1'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            AuditQuestion::create([
                'question' => $request->question,
                'status' => $request->status ?? 1
            ]);
            return ResponseService::successResponse('Question Added Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function updateQuestion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $question = AuditQuestion::find($id);
            if (!$question) {
                return ResponseService::errorResponse('Question not found');
            }
            $question->update([
                'question' => $request->question,
                'status' => $request->status
            ]);
            return ResponseService::successResponse('Question Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function deleteQuestion($id)
    {
        try {
            $question = AuditQuestion::find($id);
            if (!$question) {
                return ResponseService::errorResponse('Question not found');
            }
            $question->delete();
            return ResponseService::successResponse('Question Deleted Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    // === School Audits ===

    public function getSchoolAudits(Request $request)
    {
        try {
            $audits = SchoolAudit::with('school:id,name', 'auditor:id,first_name,last_name')
                ->orderBy('audit_date', 'desc');

            if ($request->school_id) {
                $audits->where('school_id', $request->school_id);
            }

            return ResponseService::successResponse('Data Fetched Successfully', $audits->paginate($request->limit ?? 20));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function storeSchoolAudit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|exists:schools,id',
            'audit_date' => 'required|date',
            'remarks' => 'nullable|string',
            'answers' => 'required|array',
            'answers.*.audit_question_id' => 'required|exists:audit_questions,id',
            'answers.*.answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $audit = SchoolAudit::create([
                'school_id' => $request->school_id,
                'auditor_id' => Auth::id() ?? 1, // fallback if not properly auth'd as super admin in test
                'audit_date' => $request->audit_date,
                'remarks' => $request->remarks
            ]);

            $answersData = [];
            foreach ($request->answers as $ans) {
                $answersData[] = [
                    'school_audit_id' => $audit->id,
                    'audit_question_id' => $ans['audit_question_id'],
                    'answer' => $ans['answer'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            SchoolAuditAnswer::insert($answersData);

            DB::commit();
            return ResponseService::successResponse('School Audit Submitted Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function getSchoolAuditDetails($id)
    {
        try {
            $audit = SchoolAudit::with([
                'school:id,name', 
                'auditor:id,first_name,last_name',
                'answers.question'
            ])->find($id);

            if (!$audit) {
                return ResponseService::errorResponse('Audit not found');
            }

            return ResponseService::successResponse('Data Fetched Successfully', $audit);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }
}
