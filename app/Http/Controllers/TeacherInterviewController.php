<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewApplication;
use App\Models\TeacherInterview;
use App\Models\TeacherInterviewFeedback;
use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\StaffSupportSchool;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('teacher-interview-list')) {
            abort(403);
        }

        if (request()->wantsJson()) {
            $offset = request()->offset ?? 0;
            $limit = request()->limit ?? 10;
            $sort = request()->sort ?? 'id';
            $order = request()->order ?? 'DESC';
            $search = request()->search;

            $query = TeacherInterviewApplication::with('school');

            if (Auth::user()->hasRole('Super Admin')) {
                // Super Admin can see all
            } elseif (Auth::user()->hasRole('School Admin')) {
                $query->where('school_id', Auth::user()->school_id);
            } else {
                // Other users (interviewers) see only their assigned applications
                $query->whereHas('interview', function($q) {
                    $q->where('interviewer_id', Auth::id());
                });
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $query->orderBy($sort, $order)->skip($offset)->take($limit);
            $applications = $query->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;

            foreach ($applications as $application) {
                $operate = '<a href="' . route('teacher-interviews.show', $application->id) . '" class="btn btn-sm btn-info btn-rounded btn-icon" title="' . __('View Details') . '"><i class="fa fa-eye"></i></a>&nbsp;';
                
                if (Auth::user()->can('teacher-interview-edit') || Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('School Admin')) {
                    $operate .= '<button type="button" class="btn btn-sm btn-warning btn-rounded btn-icon assign-btn" data-id="' . $application->id . '" data-school="' . $application->school_id . '" title="' . __('Assign Interviewer') . '"><i class="fa fa-user-plus"></i></button>&nbsp;';
                }

                if ($application->resume_path) {
                    $operate .= '<a href="' . asset('storage/' . $application->resume_path) . '" target="_blank" class="btn btn-sm btn-primary btn-rounded btn-icon" title="' . __('Download Resume') . '"><i class="fa fa-download"></i></a>';
                }

                if ($application->status == 'Pending') {
                    $statusBadge = '<span class="badge badge-warning">' . $application->status . '</span>';
                } elseif ($application->status == 'Rejected') {
                    $statusBadge = '<span class="badge badge-danger">' . $application->status . '</span>';
                } elseif ($application->status == 'Hired') {
                    $statusBadge = '<span class="badge badge-success">' . $application->status . '</span>';
                } else {
                    $statusBadge = '<span class="badge badge-info">' . $application->status . '</span>';
                }

                $tempRow = $application->toArray();
                $tempRow['no'] = $no++;
                $tempRow['school_name'] = $application->school->name ?? '-';
                $tempRow['applied_on'] = $application->created_at->format('d M, Y');
                $tempRow['status_badge'] = $statusBadge;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        $staffMembers = User::where('school_id', Auth::user()->school_id)
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['Student', 'Parent', 'Guardian']);
                $q->where('name', 'HR');
            })->get();

        return view('teacher-interviews.index', compact('staffMembers'));
    }

    public function myInterviews()
    {
        if (!Auth::user()->can('assigned-teacher-interview')) {
            abort(403);
        }

        $interviews = TeacherInterview::with('application')
            ->where('interviewer_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('teacher-interviews.my-assigned', compact('interviews'));
    }

    public function show($id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-list') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }


        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            $interview = TeacherInterview::create([
                'application_id' => $id,
                'status' => 'Pending',
                'interviewer_id' => Auth::id() // Default to the viewer if not assigned yet
            ]);
        }

        $feedbackQuestions = TeacherInterviewFeedbackQuestion::with('optionGroup')->where('status', 'active')->get();
        $feedbacks = TeacherInterviewFeedback::where('interview_id', $interview->id)->get()->keyBy('question_id');
        $offer = \App\Models\TeacherOfferLetter::where('application_id', $application->id)->first();

        return view('teacher-interviews.show', compact('application', 'interview', 'feedbackQuestions', 'feedbacks', 'offer'));
    }

    public function showDocumentUploadForm($token)
    {
        $application = TeacherInterviewApplication::where('document_upload_token', $token)
            ->where('document_upload_token_expires_at', '>', now())
            ->firstOrFail();

        return view('teacher-interviews.document-upload', compact('application', 'token'));
    }

    public function submitDocuments(Request $request, $token)
    {
        $application = TeacherInterviewApplication::where('document_upload_token', $token)
            ->where('document_upload_token_expires_at', '>', now())
            ->firstOrFail();

        $request->validate([
            'identity_proof' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
            'degree_certificate' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
            'experience_letter' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $documents = [
            'Identity Proof' => 'identity_proof',
            'Degree Certificate' => 'degree_certificate',
            'Experience Letter' => 'experience_letter'
        ];

        foreach ($documents as $type => $inputName) {
            if ($request->hasFile($inputName)) {
                $path = $request->file($inputName)->store('teacher_joining_documents', 'public');
                
                \App\Models\TeacherJoiningDocument::create([
                    'application_id' => $application->id,
                    'document_type' => $type,
                    'file_path' => $path,
                    'status' => 'Pending'
                ]);
            }
        }

        // We do not clear the token here so that redirect()->back() can still find the application
        // and display the success message. If they upload again, it will just add new records.
        $application->save();

        return redirect()->back()->with('success', __('Documents uploaded successfully. Our team will verify them shortly.'));
    }

    public function updateDocumentStatus(Request $request, $id)
    {
        // Add permission check if needed
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');
        $hasPermission = Auth::user()->can('teacher-interview-update-status');

        if (!$isSuperAdmin && (!$hasPermission)) {
            abort(403, 'You are not authorized to update document status.');
        }

        $request->validate([
            'status' => 'required|in:Pending,Verified,Rejected',
            'remarks' => 'nullable|string'
        ]);

        $document = \App\Models\TeacherJoiningDocument::findOrFail($id);
        $document->status = $request->status;
        if ($request->has('remarks')) {
            $document->remarks = $request->remarks;
        }
        $document->save();

        if ($document->status == 'Rejected') {
            $application = \App\Models\TeacherInterviewApplication::findOrFail($document->application_id);
            
            // Ensure token is still valid or extend it
            if (!$application->document_upload_token || $application->document_upload_token_expires_at < now()) {
                $application->document_upload_token = \Illuminate\Support\Str::uuid()->toString();
                $application->document_upload_token_expires_at = now()->addDays(3);
                $application->save();
            }

            if ($application->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\TeacherDocumentRejectedMail($application, $document->document_type, $document->remarks));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Mail error (Document Rejected): ' . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', __('Document status updated successfully.'));
    }

    public function showOfferLetter($token)
    {
        $offer = \App\Models\TeacherOfferLetter::where('token', $token)
            ->where('token_expires_at', '>', now())
            ->firstOrFail();

        $application = \App\Models\TeacherInterviewApplication::findOrFail($offer->application_id);

        return view('teacher-interviews.offer-letter', compact('offer', 'application', 'token'));
    }

    public function actionOfferLetter(Request $request, $token)
    {
        $offer = \App\Models\TeacherOfferLetter::where('token', $token)
            ->where('token_expires_at', '>', now())
            ->firstOrFail();

        $application = \App\Models\TeacherInterviewApplication::findOrFail($offer->application_id);

        $request->validate([
            'action' => 'required|in:accept,reject'
        ]);

        if ($request->action == 'accept') {
            $offer->status = 'Accepted';
            $offer->save();

            // Generate registration token
            $application->registration_token = \Illuminate\Support\Str::uuid()->toString();
            $application->registration_token_expires_at = now()->addDays(7);
            $application->save();

            return redirect()->route('career.teacher-registration', $application->registration_token)
                ->with('success', __('Offer Accepted successfully. Please complete your registration.'));
        } else {
            $offer->status = 'Rejected';
            $offer->token = null;
            $offer->token_expires_at = null;
            $offer->save();

            return redirect()->back()->with('error', __('You have rejected the offer.'));
        }
    }

    public function showRegistrationForm($token)
    {
        $application = \App\Models\TeacherInterviewApplication::where('registration_token', $token)
            ->where('registration_token_expires_at', '>', now())
            ->firstOrFail();

        return view('teacher-interviews.teacher-registration', compact('application', 'token'));
    }

    public function submitRegistrationForm(Request $request, $token)
    {
        $application = \App\Models\TeacherInterviewApplication::where('registration_token', $token)
            ->where('registration_token_expires_at', '>', now())
            ->firstOrFail();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'current_address' => 'required|string',
            'permanent_address' => 'required|string',
            'qualification' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg,gif,webp'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('staff', 'public');
        }

        // Create or Update User Account for Teacher
        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'school_id' => $application->school_id,
            'gender' => $request->gender,
            'mobile' => $request->mobile,
            'dob' => date('Y-m-d', strtotime($request->dob)),
            'current_address' => $request->current_address,
            'permanent_address' => $request->permanent_address,
            'status' => 1
        ];

        if ($imagePath) {
            $userData['image'] = $imagePath;
        }

        // Switch to the school's database connection before creating the user
        $school = \App\Models\School::find($application->school_id);
        if ($school && $school->database_name) {
            \Illuminate\Support\Facades\Config::set('database.connections.school.database', $school->database_name);
            \Illuminate\Support\Facades\DB::purge('school');
            \Illuminate\Support\Facades\DB::connection('school')->reconnect();
            \Illuminate\Support\Facades\DB::setDefaultConnection('school');
        }

        $user = \App\Models\User::updateOrCreate(
            ['email' => $application->email],
            $userData
        );

        // Create or Update Staff Record
        \App\Models\Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'qualification' => $request->qualification,
                // Only set these defaults if the record doesn't already have them set properly
                'salary' => \App\Models\Staff::where('user_id', $user->id)->value('salary') ?? 0,
                'joining_date' => \App\Models\Staff::where('user_id', $user->id)->value('joining_date') ?? date('Y-m-d')
            ]
        );

        // Assign Teacher role
        $role = \Spatie\Permission\Models\Role::where('name', 'Teacher')->where('school_id', $application->school_id)->first();
        if (!$role) {
            $role = \Spatie\Permission\Models\Role::where('name', 'Teacher')->whereNull('school_id')->first();
        }
        if ($role) {
            $user->assignRole($role);
        }

        // Add to Session Year Tracking so the teacher appears in the school dashboard
        $sessionYear = app(\App\Services\CachingService::class)->getDefaultSessionYear();
        if ($sessionYear) {
            app(\App\Services\SessionYearsTrackingsService::class)->storeSessionYearsTracking(
                'App\Models\Teacher', 
                $user->id, 
                $user->id, // use the teacher's own ID as they are registering themselves
                $sessionYear->id, 
                $application->school_id, 
                null
            );
        }

        // Clear token
        $application->registration_token = null;
        $application->registration_token_expires_at = null;
        $application->save();

        return view('teacher-interviews.teacher-registration', [
            'application' => $application,
            'token' => $token,
            'success_message' => __('Registration successful! Your teacher account has been created.')
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $userRoles = Auth::user()->roles->pluck('name')->toArray();
        $isSuperAdmin = in_array('Super Admin', $userRoles);
        $hasPermission = Auth::user()->can('teacher-interview-update-status');

        // Note: we removed the abort check here because any authenticated user who reached this route
        // (and thus passed the middleware/index checks) might be allowed to update to non-advanced statuses.
        // We will restrict based on WHAT they are trying to update.

        // Restrict everyone except Super Admin from setting advanced statuses
        if (!$isSuperAdmin && !$hasPermission) {
            $allowedStatuses = ['Pending', 'Shortlisted', 'Interview Scheduled', 'Demo Scheduled'];
            $application = TeacherInterviewApplication::findOrFail($id);
            
            if (!in_array($request->status, $allowedStatuses) && $request->status !== $application->status) {
                abort(403, 'You are not authorized to set this advanced status.');
            }
            
            // Also restrict them from editing AT ALL if the application is ALREADY in an advanced status
            if (!in_array($application->status, $allowedStatuses)) {
                abort(403, 'You are not authorized to update this application anymore.');
            }
        }

        $request->validate([
            'status' => 'required|string|in:Pending,Shortlisted,Interview Scheduled,Demo Scheduled,Demo Completed,Document Verification,Hired,Rejected',
            'remarks' => 'nullable|string',
            'interview_date' => 'nullable|required_if:status,Interview Scheduled|date',
            'time' => 'nullable|required_if:status,Interview Scheduled',
            'location' => 'nullable|required_if:status,Interview Scheduled|string',
            'instructions' => 'nullable|string',
            'demo_subject' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_class_name' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_date' => 'nullable|required_if:status,Demo Scheduled|date',
            'demo_time' => 'nullable|required_if:status,Demo Scheduled',
            'demo_location' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_instructions' => 'nullable|string',
            'demo_overall_rating' => 'nullable|required_if:status,Demo Completed|numeric|min:0|max:5',
            'demo_remarks' => 'nullable|string',
            'document_verification_date' => 'nullable|required_if:status,Document Verification|date',
            'document_verification_time' => 'nullable|required_if:status,Document Verification',
            'designation' => 'nullable|required_if:status,Hired|string',
            'department' => 'nullable|string',
            'salary' => 'nullable|required_if:status,Hired|numeric',
            'joining_date' => 'nullable|required_if:status,Hired|date',
            'reporting_time' => 'nullable|required_if:status,Hired',
            'job_location' => 'nullable|required_if:status,Hired|string'
        ]);

        $application = TeacherInterviewApplication::findOrFail($id);
        
        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $application->status = $request->status;
        if ($request->has('remarks')) {
            $application->remarks = $request->remarks;
        }

        if ($request->status == 'Document Verification') {
            $application->document_verification_date = $request->document_verification_date;
            $application->document_verification_time = $request->document_verification_time;
            
            if (empty($application->document_upload_token)) {
                $application->document_upload_token = \Illuminate\Support\Str::uuid()->toString();
            }
            
            // Token expires exactly 1 hour before the cross-verification date and time
            $verificationDateTime = \Carbon\Carbon::parse($request->document_verification_date . ' ' . $request->document_verification_time);
            $application->document_upload_token_expires_at = $verificationDateTime->subHour();
        }

        $application->save();

        if ($request->status == 'Interview Scheduled') {
            $interview = \App\Models\TeacherInterview::updateOrCreate(
                ['application_id' => $id],
                [
                    'interviewer_id' => Auth::id(),
                    'status' => 'Scheduled',
                    'interview_date' => $request->interview_date,
                    'time' => $request->time,
                    'location' => $request->location,
                    'instructions' => $request->instructions
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\InterviewScheduledMail($application, $interview));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Interview Email failed: " . $e->getMessage());
            }
        } elseif ($request->status == 'Demo Scheduled') {
            $demoClass = \App\Models\TeacherDemoClass::updateOrCreate(
                ['application_id' => $id],
                [
                    'school_id' => $application->school_id,
                    'subject' => $request->demo_subject,
                    'class_name' => $request->demo_class_name,
                    'date' => $request->demo_date,
                    'time' => $request->demo_time,
                    'location' => $request->demo_location,
                    'instructions' => $request->demo_instructions,
                    'status' => 'Scheduled'
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\DemoScheduledMail($application, $demoClass));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Demo Email failed: " . $e->getMessage());
            }
        } elseif ($request->status == 'Demo Completed') {
            $demoClass = \App\Models\TeacherDemoClass::updateOrCreate(
                ['application_id' => $id],
                [
                    'school_id' => $application->school_id,
                    'status' => 'Completed',
                    'overall_rating' => $request->demo_overall_rating,
                    'remarks' => $request->demo_remarks
                ]
            );
        } elseif ($request->status == 'Document Verification') {
            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\DocumentVerificationMail($application));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Document Verification Email failed: " . $e->getMessage());
            }
        } elseif ($request->status == 'Hired') {
            $token = \Illuminate\Support\Str::uuid()->toString();
            $offer = \App\Models\TeacherOfferLetter::updateOrCreate(
                ['application_id' => $id],
                [
                    'designation' => $request->designation,
                    'department' => $request->department,
                    'salary' => $request->salary,
                    'joining_date' => $request->joining_date,
                    'reporting_time' => $request->reporting_time,
                    'job_location' => $request->job_location,
                    'token' => $token,
                    'token_expires_at' => \Carbon\Carbon::now()->addDays(7),
                    'status' => 'Sent'
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\TeacherOfferLetterMail($application, $offer));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Offer Letter Email failed: " . $e->getMessage());
            }

            // Send Notification to Principal
            $principal = \App\Models\User::role('Principal')->where('school_id', $application->school_id)->first();
            if ($principal) {
                $title = 'New Staff Hired';
                $body = "Candidate {$application->first_name} {$application->last_name} has been hired for the position of {$request->designation} at a salary of {$request->salary}.";
                $type = 'Notification';
                try {
                    send_notification([$principal->id], $title, $body, $type, []);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Principal Notification failed: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    public function assignInterviewer(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-edit') && !Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('School Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'interviewer_id' => 'required|exists:users,id'
        ]);

        $application = TeacherInterviewApplication::findOrFail($id);
        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            $interview = TeacherInterview::create([
                'application_id' => $id,
                'status' => 'Pending',
                'interviewer_id' => $request->interviewer_id
            ]);
        } else {
            $interview->interviewer_id = $request->interviewer_id;
            $interview->save();
        }

        return redirect()->back()->with('success', __('Interviewer assigned successfully.'));
    }

    public function saveFeedback(Request $request, $id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-manage') && !Auth::user()->can('teacher-interview-edit') && !Auth::user()->hasRole('HR Admin') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }

        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::firstOrCreate(
            ['application_id' => $id],
            ['interviewer_id' => Auth::id(), 'status' => 'Pending']
        );

        if ($request->has('feedbacks')) {
            foreach ($request->feedbacks as $question_id => $feedback_text) {
                if (!empty($feedback_text)) {
                    TeacherInterviewFeedback::updateOrCreate(
                        ['interview_id' => $interview->id, 'question_id' => $question_id],
                        ['interviewer_feedback' => $feedback_text]
                    );
                }
            }
        }

        return redirect()->back()->with('success', 'Interview feedback saved successfully.');
    }

    public function downloadPdf($id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-list') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }

        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            abort(404, 'Interview not found');
        }

        $feedbackQuestions = TeacherInterviewFeedbackQuestion::with('optionGroup')->where('status', 'active')->get();
        $feedbacks = TeacherInterviewFeedback::where('interview_id', $interview->id)->get()->keyBy('question_id');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('teacher-interviews.pdf', compact('application', 'interview', 'feedbackQuestions', 'feedbacks'));

        return $pdf->download('interview_feedback_' . str_replace(' ', '_', strtolower($application->name)) . '.pdf');
    }
}
