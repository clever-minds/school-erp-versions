<?php

namespace App\Http\Controllers;

use App\Models\AuditQuestion;
use App\Models\School;
use App\Models\SchoolAudit;
use App\Models\SchoolAuditAnswer;
use App\Models\StaffSupportSchool;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SchoolAuditController extends Controller
{
    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenRedirect('school-audit-list');

        if ($request->wantsJson()) {
            $offset = request('offset', 0);
            $limit = request('limit', 10);
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');
            $search = request('search');

            $sql = SchoolAudit::with('school', 'auditor');

            if (Auth::user()->hasRole('Super Admin')) {
                // Super Admin can see all
            } elseif (Auth::user()->hasRole('School Admin')) {
                $sql->where('school_id', Auth::user()->school_id);
            } else {
                // Other users (auditors) see only their assigned audits
                $sql->where('auditor_id', Auth::id());
            }

            $archiveStatus = request('archive_status', 'active');
            if ($archiveStatus == 'archived') {
                $sql->whereNotNull('archived_at');
            } else {
                $sql->whereNull('archived_at');
            }

            if (!empty($search)) {
                $sql->where(function($q) use ($search) {
                    $q->whereHas('school', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    })->orWhereHas('auditor', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%$search%")
                          ->orWhere('last_name', 'like', "%$search%");
                    });
                });
            }

            $total = $sql->count();

            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;
            foreach ($res as $row) {
                $operate = '';
                if (Auth::user()->can('school-audit-list') || $row->auditor_id == Auth::id()) {
                    $operate .= '<a href="' . route('school-audits.show', $row->id) . '" class="btn btn-xs btn-gradient-info btn-rounded btn-icon" title="View"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
                }
                if ($row->status == 0 && (Auth::user()->can('school-audit-edit') || $row->auditor_id == Auth::id())) {
                    $operate .= '<a href="' . route('school-audits.edit', $row->id) . '" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon" title="Conduct Audit"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                }
                if (Auth::user()->can('school-audit-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('school-audits.destroy', $row->id));
                }

                $tempRow = $row->toArray();
                $tempRow['no'] = $no++;
                $tempRow['school_name'] = $row->school ? $row->school->name : '-';
                $tempRow['auditor_name'] = $row->auditor ? $row->auditor->first_name . ' ' . $row->auditor->last_name : '-';
                $tempRow['status_badge'] = $row->status == 1 ? '<span class="badge badge-success">'.__('Completed').'</span>' : '<span class="badge badge-warning">'.__('Pending').'</span>';
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        return view('school_audits.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('school-audit-create');
        
        // Only show schools assigned to the logged-in user
        $assignedSchoolIds = StaffSupportSchool::where('user_id', Auth::id())->pluck('school_id')->toArray();
        if (!empty($assignedSchoolIds)) {
            $schools = School::where('status', 1)->whereIn('id', $assignedSchoolIds)->get();
        } else {
            $schools = School::where('status', 1)->get();
        }
        $categories = \App\Models\AuditCategory::where('status', 1)->with('questions')->get();

        return view('school_audits.create', compact('schools', 'categories'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('school-audit-create');

        $request->validate([
            'name' => 'required|string',
            'school_id' => 'required|exists:schools,id',
            'audit_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:audit_date',
            'frequency' => 'required|in:One-Time,Monthly,Quarterly,Half Yearly,Yearly',
            'remarks' => 'nullable|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:audit_categories,id',
        ]);

        try {
            DB::beginTransaction();

            $audit = SchoolAudit::create([
                'name' => $request->name,
                'school_id' => $request->school_id,
                'auditor_id' => $request->auditor_id ?? Auth::id(),
                'audit_date' => date('Y-m-d', strtotime($request->audit_date)),
                'due_date' => date('Y-m-d', strtotime($request->due_date)),
                'frequency' => $request->frequency,
                'remarks' => $request->remarks,
                'status' => 0,
            ]);

            // Attach categories
            $audit->categories()->attach($request->category_ids);

            // Fetch questions for these categories
            $questions = AuditQuestion::whereIn('audit_category_id', $request->category_ids)->where('status', 1)->get();

            foreach ($questions as $question) {
                SchoolAuditAnswer::create([
                    'school_audit_id' => $audit->id,
                    'audit_question_id' => $question->id,
                    'answer' => 'Pending',
                    'remarks' => null,
                ]);
            }

            DB::commit();
            return redirect()->route('school-audits.index')->with('success', trans('data_store_successfully'));
        } catch (Throwable $e) {
            DB::rollback();
            return redirect()->back()->with('error', trans('error_occurred'));
        }
    }

    public function show($id)
    {
        $audit = SchoolAudit::with(['school', 'auditor', 'answers.question'])->findOrFail($id);

        if (!Auth::user()->can('school-audit-list') && $audit->auditor_id != Auth::id()) {
            ResponseService::noPermissionThenRedirect('school-audit-list');
        }

        return view('school_audits.show', compact('audit'));
    }

    public function edit($id)
    {
        $audit = SchoolAudit::with(['school', 'auditor', 'answers.question'])->findOrFail($id);

        if (!Auth::user()->can('school-audit-edit') && $audit->auditor_id != Auth::id()) {
            ResponseService::noPermissionThenRedirect('school-audit-edit');
        }

        if ($audit->status == 1) {
            return redirect()->route('school-audits.show', $id)->with('error', __('Audit is already completed and cannot be modified.'));
        }

        return view('school_audits.edit', compact('audit'));
    }

    public function update(Request $request, $id)
    {
        $audit = SchoolAudit::findOrFail($id);

        if (!Auth::user()->can('school-audit-edit') && $audit->auditor_id != Auth::id()) {
            ResponseService::noPermissionThenRedirect('school-audit-edit');
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.id' => 'required|exists:school_audit_answers,id',
            'answers.*.answer' => 'required',
            'answers.*.remarks' => 'nullable|string',
            'answers.*.image' => 'nullable|image|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $audit = SchoolAudit::findOrFail($id);
            $totalScorable = 0;
            $earnedScore = 0;

            foreach ($request->answers as $key => $answerData) {
                $auditAnswer = SchoolAuditAnswer::findOrFail($answerData['id']);
                
                $imagePaths = [];
                if ($auditAnswer->image) {
                    $decoded = json_decode($auditAnswer->image, true);
                    $imagePaths = is_array($decoded) ? $decoded : [$auditAnswer->image];
                }
                
                if ($request->hasFile("answers.{$key}.images")) {
                    $imagePaths = []; // Override old if new ones uploaded, or append? Let's override for simplicity, or we can just save the new ones.
                    foreach ($request->file("answers.{$key}.images") as $file) {
                        $imagePaths[] = $file->store('audit_images', 'public');
                    }
                }

                $auditAnswer->update([
                    'answer' => $answerData['answer'],
                    'remarks' => $answerData['remarks'] ?? '',
                    'image' => count($imagePaths) > 0 ? json_encode($imagePaths) : null,
                ]);

                // Simple scoring logic for Yes/No/Number/Rating if needed, here we just do basic Yes/No for MVP
                if (in_array($auditAnswer->answer, ['Yes', 'No'])) {
                    $totalScorable++;
                    if ($auditAnswer->answer == 'Yes') {
                        $earnedScore++;
                    }
                } elseif (in_array($auditAnswer->answer, ['Excellent', 'Good', 'Average', 'Unsatisfactory'])) {
                    $totalScorable++;
                    if ($auditAnswer->answer == 'Excellent') {
                        $earnedScore += 1;
                    } elseif ($auditAnswer->answer == 'Good') {
                        $earnedScore += 0.75;
                    } elseif ($auditAnswer->answer == 'Average') {
                        $earnedScore += 0.50;
                    }
                }
            }

            $percentage = $totalScorable > 0 ? ($earnedScore / $totalScorable) * 100 : 0;

            $audit->update([
                'status' => 1, 
                'submission_date' => now(),
                'percentage_score' => $percentage
            ]);

            // Notify Super Admin
            try {
                $superAdminRoles = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
                if ($superAdminRoles) {
                    $superAdmins = \App\Models\User::role('Super Admin')->pluck('id')->toArray();
                    if (!empty($superAdmins)) {
                        $title = __('Audit Submitted');
                        $body = __('An audit for school') . ' ' . ($audit->school ? $audit->school->name : '') . ' ' . __('has been submitted by') . ' ' . Auth::user()->first_name . '.';
                        $type = 'School Audit';
                        send_notification($superAdmins, $title, $body, $type);
                    }
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error("Notification Error: " . $ex->getMessage());
            }

            DB::commit();
            return redirect()->route('school-audits.index')->with('success', trans('data_update_successfully'));
        } catch (Throwable $e) {
            DB::rollback();
            return redirect()->back()->with('error', trans('error_occurred'));
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('school-audit-delete');

        try {
            $audit = SchoolAudit::findOrFail($id);
            // Delete related answers
            $audit->answers()->delete();
            $audit->delete();
            return ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, 'SchoolAuditController -> destroy');
        }
    }

    public function downloadPdf($id)
    {
        ResponseService::noPermissionThenRedirect('school-audit-list');

        $audit = SchoolAudit::with(['school', 'auditor', 'answers.question'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('school_audits.pdf', compact('audit'));
        
        $fileName = 'school_audit_' . ($audit->school ? str_replace(' ', '_', strtolower($audit->school->name)) : 'report') . '.pdf';
        return $pdf->download($fileName);
    }

    public function emailPdf($id)
    {
        ResponseService::noPermissionThenRedirect('school-audit-list');

        $audit = SchoolAudit::with(['school', 'auditor', 'answers.question'])->findOrFail($id);

        if (!$audit->school || !$audit->school->support_email) {
            return redirect()->back()->with('error', __('School support email is not configured.'));
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('school_audits.pdf', compact('audit'));
            
            $fileName = 'school_audit_' . str_replace(' ', '_', strtolower($audit->school->name)) . '.pdf';
            
            // Temporary save the PDF
            $path = storage_path('app/public/' . $fileName);
            $pdf->save($path);

            \Illuminate\Support\Facades\Mail::to($audit->school->support_email)
                ->send(new \App\Mail\AuditReportMail($audit, $path));

            // Delete temporary PDF
            if (file_exists($path)) {
                unlink($path);
            }

            return redirect()->back()->with('success', __('Audit report emailed successfully to school.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email Error: " . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to send email.'));
        }
    }

    public function compare(Request $request)
    {
        ResponseService::noPermissionThenRedirect('school-audit-list');

        $schools = School::where('status', 1)->get();
        $audits = [];
        $audit1 = null;
        $audit2 = null;

        if ($request->school_id) {
            $audits = SchoolAudit::where('school_id', $request->school_id)->where('status', 1)->orderBy('audit_date', 'desc')->get();
        }

        if ($request->audit1_id && $request->audit2_id) {
            $audit1 = SchoolAudit::with(['school', 'answers.question'])->find($request->audit1_id);
            $audit2 = SchoolAudit::with(['school', 'answers.question'])->find($request->audit2_id);
        }

        return view('school_audits.compare', compact('schools', 'audits', 'audit1', 'audit2'));
    }
}
