@extends('layouts.master')

@section('title')
    {{ __('Teacher Interview Details') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Application Details') }}
            </h3>
            <a href="{{ route('teacher-interviews.index') }}" class="btn btn-primary btn-sm"><i class="fa fa-arrow-left"></i> {{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Applicant Information') }}</h4>
                        <ul class="list-ticked">
                            <li><strong>{{ __('School') }}:</strong> {{ $application->school->name ?? '-' }}</li>
                            <li><strong>{{ __('Name') }}:</strong> {{ $application->name }}</li>
                            <li><strong>{{ __('Email') }}:</strong> {{ $application->email }}</li>
                            <li><strong>{{ __('Phone') }}:</strong> {{ $application->phone }}</li>
                            <li><strong>{{ __('Applied On') }}:</strong> {{ $application->created_at->format('d M, Y h:i A') }}</li>
                            <li><strong>{{ __('Resume') }}:</strong> 
                                @if($application->resume_path)
                                    <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank">{{ __('View / Download') }}</a>
                                @else
                                    <span class="text-muted">{{ __('Not Provided') }}</span>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Update Status') }}</h4>
                        
                        @php
                            $userRoles = Auth::user()->roles->pluck('name')->toArray();
                            $isSuperAdmin = in_array('Super Admin', $userRoles);
                            $hasPerm = Auth::user()->can('teacher-interview-update-status');
                            
                            $isAdvancedStatus = in_array($application->status, ['Demo Completed', 'Document Verification', 'Hired', 'Rejected']);
                            
                            // If they are not Super Admin and the status is advanced, they can't edit
                            $canEdit = $isSuperAdmin || $hasPerm || !$isAdvancedStatus;
                        @endphp
                        
                        @if($canEdit)
                        
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('teacher-interviews.update-status', $application->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Current Status') }}</label>
                                <select name="status" id="status-select" class="form-control" required>
                                    <option value="Pending" {{ $application->status == 'Pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="Shortlisted" {{ $application->status == 'Shortlisted' ? 'selected' : '' }}>{{ __('Shortlisted') }}</option>
                                    <option value="Interview Scheduled" {{ $application->status == 'Interview Scheduled' ? 'selected' : '' }}>{{ __('Interview Scheduled') }}</option>
                                    <option value="Demo Scheduled" {{ $application->status == 'Demo Scheduled' ? 'selected' : '' }}>{{ __('Demo Scheduled') }}</option>
                                    @if($isSuperAdmin)
                                    <option value="Demo Completed" {{ $application->status == 'Demo Completed' ? 'selected' : '' }}>{{ __('Demo Completed') }}</option>
                                    <option value="Document Verification" {{ $application->status == 'Document Verification' ? 'selected' : '' }}>{{ __('Document Verification') }}</option>
                                    <option value="Hired" {{ $application->status == 'Hired' ? 'selected' : '' }}>{{ __('Hired') }}</option>
                                    <option value="Rejected" {{ $application->status == 'Rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                                    @endif
                                </select>
                            </div>

                            <div id="interview-details" style="{{ $application->status == 'Interview Scheduled' ? '' : 'display: none;' }}">
                                <div class="form-group">
                                    <label>{{ __('Interview Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="interview_date" class="form-control" value="{{ $interview->interview_date ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Interview Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="time" class="form-control" value="{{ $interview->time ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Venue / Location') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="location" class="form-control" value="{{ $interview->location ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Instructions for Candidate') }}</label>
                                    <textarea name="instructions" class="form-control" rows="3">{{ $interview->instructions ?? '' }}</textarea>
                                </div>
                            </div>

                            <div id="demo-details" style="display: {{ $application->status == 'Demo Scheduled' ? 'block' : 'none' }}; margin-top: 15px;">
                                <div class="form-group">
                                    <label>{{ __('Subject') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="demo_subject" class="form-control" value="{{ old('demo_subject', $application->demoClass->subject ?? '') }}" {{ $application->status == 'Demo Scheduled' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Class Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="demo_class_name" class="form-control" value="{{ old('demo_class_name', $application->demoClass->class_name ?? '') }}" {{ $application->status == 'Demo Scheduled' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Demo Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="demo_date" class="form-control" value="{{ old('demo_date', isset($application->demoClass->date) ? \Carbon\Carbon::parse($application->demoClass->date)->format('Y-m-d') : '') }}" {{ $application->status == 'Demo Scheduled' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="demo_time" class="form-control" value="{{ old('demo_time', isset($application->demoClass->time) ? \Carbon\Carbon::parse($application->demoClass->time)->format('H:i') : '') }}" {{ $application->status == 'Demo Scheduled' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Venue/Location') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="demo_location" class="form-control" value="{{ old('demo_location', $application->demoClass->location ?? '') }}" {{ $application->status == 'Demo Scheduled' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Instructions') }}</label>
                                    <textarea name="demo_instructions" class="form-control" rows="3">{{ old('demo_instructions', $application->demoClass->instructions ?? '') }}</textarea>
                                </div>
                            </div>

                            <div id="demo-completed-details" style="display: {{ $application->status == 'Demo Completed' ? 'block' : 'none' }}; margin-top: 15px;">
                                <div class="form-group">
                                    <label>{{ __('Demo Overall Rating (out of 5)') }} <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" min="0" max="5" name="demo_overall_rating" class="form-control" value="{{ old('demo_overall_rating', $application->demoClass->overall_rating ?? '') }}" {{ $application->status == 'Demo Completed' ? 'required' : '' }}>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Demo Remarks') }}</label>
                                    <textarea name="demo_remarks" class="form-control" rows="3">{{ old('demo_remarks', $application->demoClass->remarks ?? '') }}</textarea>
                                </div>
                            </div>

                            <!-- Document Verification Fields -->
                            <div id="document-verification-container" style="display: {{ $application->status == 'Document Verification' ? 'block' : 'none' }};">
                                <div class="form-group mt-3">
                                    <label>{{ __('Verification Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="document_verification_date" class="form-control" value="{{ old('document_verification_date', isset($application->document_verification_date) ? \Carbon\Carbon::parse($application->document_verification_date)->format('Y-m-d') : '') }}" {{ $application->status == 'Document Verification' ? 'required' : '' }}>
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Verification Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="document_verification_time" class="form-control" value="{{ old('document_verification_time', isset($application->document_verification_time) ? \Carbon\Carbon::parse($application->document_verification_time)->format('H:i') : '') }}" {{ $application->status == 'Document Verification' ? 'required' : '' }}>
                                </div>
                            </div>

                            <!-- Hired Fields (Offer Letter details) -->
                            <div id="hired-details-container" style="display: {{ $application->status == 'Hired' ? 'block' : 'none' }};">
                                <div class="form-group mt-3">
                                    <label>{{ __('Designation') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="designation" class="form-control" value="{{ old('designation', $offer->designation ?? '') }}">
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Department') }}</label>
                                    <input type="text" name="department" class="form-control" value="{{ old('department', $offer->department ?? '') }}">
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Salary (Per Month)') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="salary" class="form-control" value="{{ old('salary', $offer->salary ?? '') }}">
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Joining Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', isset($offer->joining_date) ? \Carbon\Carbon::parse($offer->joining_date)->format('Y-m-d') : '') }}">
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Reporting Time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="reporting_time" class="form-control" value="{{ old('reporting_time', isset($offer->reporting_time) ? \Carbon\Carbon::parse($offer->reporting_time)->format('H:i') : '') }}">
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ __('Job Location') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="job_location" class="form-control" value="{{ old('job_location', $offer->job_location ?? $application->school->name ?? '') }}">
                                </div>
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fa fa-info-circle"></i> {{ __('An Offer Letter will be emailed to the candidate upon submission.') }}
                                </div>
                            </div>

                            <script>
                                document.getElementById('status-select').addEventListener('change', function() {
                                    const status = this.value;
                                    
                                    // Toggle Interview Fields
                                    const interviewFields = document.getElementById('interview-details');
                                    interviewFields.style.display = (status === 'Interview Scheduled') ? 'block' : 'none';
                                    
                                    // Toggle Demo Fields
                                    const demoFields = document.getElementById('demo-details');
                                    demoFields.style.display = (status === 'Demo Scheduled') ? 'block' : 'none';
                                    
                                    // Toggle Demo Completed Fields
                                    const demoCompletedFields = document.getElementById('demo-completed-details');
                                    demoCompletedFields.style.display = (status === 'Demo Completed') ? 'block' : 'none';
                                    
                                    // Toggle Document Verification Fields
                                    const docFields = document.getElementById('document-verification-container');
                                    docFields.style.display = (status === 'Document Verification') ? 'block' : 'none';
                                    
                                    // Toggle Hired Fields
                                    const hiredFields = document.getElementById('hired-details-container');
                                    hiredFields.style.display = (status === 'Hired') ? 'block' : 'none';
                                });
                            </script>

                            <div class="form-group">
                                <label>{{ __('Remarks (Optional)') }}</label>
                                <textarea name="remarks" class="form-control" rows="4" placeholder="{{ __('Add any private remarks here...') }}">{{ $application->remarks }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary theme-btn">{{ __('Update Status') }}</button>
                        </form>
                        @else
                        {{-- Read-only status for unauthorized users --}}
                        <div class="form-group">
                            <label>{{ __('Current Status') }}</label>
                            <div class="mt-1">
                                @php
                                    $statusColors = [
                                        'Pending'            => 'warning',
                                        'Shortlisted'        => 'info',
                                        'Interview Scheduled'=> 'primary',
                                        'Demo Scheduled'     => 'info',
                                        'Hired'              => 'success',
                                        'Rejected'           => 'danger',
                                    ];
                                    $badgeColor = $statusColors[$application->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $badgeColor }} p-2" style="font-size: 0.9rem;">
                                    {{ __($application->status) }}
                                </span>
                            </div>
                        </div>
                        @if($application->remarks)
                        <div class="form-group mt-2">
                            <label>{{ __('Remarks') }}</label>
                            <p class="text-muted">{{ $application->remarks }}</p>
                        </div>
                        @endif
                        <div class="alert alert-warning mt-3" role="alert">
                            <i class="fa fa-lock mr-1"></i> {{ __('You do not have permission to update the status. Please contact Super Admin.') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($application->joiningDocuments && $application->joiningDocuments->count() > 0)
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Uploaded Verification Documents') }}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Document Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($application->joiningDocuments as $doc)
                                    <tr>
                                        <td>{{ $doc->document_type }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary mr-2" data-toggle="modal" data-target="#updateDocModal-{{ $doc->id }}">
                                                <i class="fa fa-edit"></i> {{ __('Update Status') }}
                                            </button>

                                            @php
                                                $badgeClass = 'secondary';
                                                if ($doc->status == 'Pending') $badgeClass = 'warning';
                                                elseif ($doc->status == 'Verified') $badgeClass = 'success';
                                                elseif ($doc->status == 'Rejected') $badgeClass = 'danger';
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}">{{ __($doc->status) }}</span>

                                            <!-- Modal -->
                                            <div class="modal fade" id="updateDocModal-{{ $doc->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ __('Update Document Status') }} ({{ $doc->document_type }})</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form action="{{ route('teacher-interviews.update-document-status', $doc->id) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="form-group text-left">
                                                                    <label>{{ __('Status') }}</label>
                                                                    <select name="status" class="form-control" required id="doc-status-{{ $doc->id }}" onchange="toggleRemarks(this, {{ $doc->id }})">
                                                                        <option value="Pending" {{ $doc->status == 'Pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                                                        <option value="Verified" {{ $doc->status == 'Verified' ? 'selected' : '' }}>{{ __('Verified') }}</option>
                                                                        <option value="Rejected" {{ $doc->status == 'Rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group text-left" id="remarks-container-{{ $doc->id }}" style="display: {{ $doc->status == 'Rejected' ? 'block' : 'none' }};">
                                                                    <label>{{ __('Remarks / Reason') }}</label>
                                                                    <textarea name="remarks" class="form-control" rows="3">{{ $doc->remarks }}</textarea>
                                                                    <small class="form-text text-muted">{{ __('Candidate will receive an email with these remarks to re-upload.') }}</small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                                                                <button type="submit" class="btn btn-theme">{{ __('Update') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-info btn-rounded btn-icon" title="{{ __('View Document') }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ asset('storage/' . $doc->file_path) }}" download class="btn btn-sm btn-primary btn-rounded btn-icon" title="{{ __('Download') }}">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(count($feedbackQuestions) > 0)
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">{{ __('Interview Performance Feedback') }}</h4>
                            @if(count($feedbacks) > 0)
                                <a href="{{ route('teacher-interviews.download-pdf', $application->id) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> {{ __('Download PDF') }}</a>
                            @endif
                        </div>
                        
                        <form action="{{ route('teacher-interviews.save-feedback', $application->id) }}" method="POST">
                            @csrf
                            @php
                                $isFeedbackSubmitted = count($feedbacks) > 0;
                            @endphp
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">{{ __('#') }}</th>
                                            <th width="45%">{{ __('Question') }}</th>
                                            <th width="50%">{{ __('Feedback / Remarks') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $index = 0; @endphp
                                        @foreach($feedbackQuestions->groupBy(function($q) { return $q->category ? $q->category->name : 'General'; }) as $category => $questions)
                                            <tr class="table-secondary">
                                                <td colspan="3"><strong>{{ $category }}</strong></td>
                                            </tr>
                                            @foreach($questions as $question)
                                            <tr>
                                                <td>{{ ++$index }}</td>
                                                <td class="text-wrap" style="min-width: 200px;">{{ $question->feedback_question }}</td>
                                                <td>
                                                    @php
                                                        $currentAnswer = isset($feedbacks[$question->id]) ? $feedbacks[$question->id]->interviewer_feedback : '';
                                                    @endphp
                                                    
                                                    @if(in_array($question->type, ['Rating', 'rating']) || empty($question->type))
                                                        <div class="d-flex align-items-center flex-wrap">
                                                            @if($question->optionGroup)
                                                                @foreach($question->optionGroup->option_values as $opt)
                                                                    <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                        <label class="form-check-label" for="q_{{ $question->id }}_{{ $loop->index }}">
                                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_{{ $loop->index }}" value="{{ $opt['label'] }}" {{ $currentAnswer == $opt['label'] ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                            {{ $opt['label'] }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                @for($i = 1; $i <= 5; $i++)
                                                                    <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                        <label class="form-check-label" for="q_{{ $question->id }}_{{ $i }}">
                                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_{{ $i }}" value="{{ $i }}" {{ $currentAnswer == $i ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                            {{ $i }} Star
                                                                        </label>
                                                                    </div>
                                                                @endfor
                                                            @endif
                                                        </div>
                                                    @elseif(in_array($question->type, ['Yes/No', 'boolean']))
                                                        <div class="d-flex align-items-center flex-wrap">
                                                            <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                <label class="form-check-label" for="q_{{ $question->id }}_yes">
                                                                    <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_yes" value="Yes" {{ $currentAnswer == 'Yes' ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                    {{ __('Yes') }}
                                                                </label>
                                                            </div>
                                                            <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                <label class="form-check-label" for="q_{{ $question->id }}_no">
                                                                    <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_no" value="No" {{ $currentAnswer == 'No' ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                    {{ __('No') }}
                                                                </label>
                                                            </div>
                                                            <div class="form-check form-check-inline mt-0 mb-2">
                                                                <label class="form-check-label" for="q_{{ $question->id }}_na">
                                                                    <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_na" value="N/A" {{ $currentAnswer == 'N/A' ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                    {{ __('N/A') }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @elseif($question->type == 'Custom')
                                                        @php
                                                            $options = $question->custom_options ? array_map('trim', explode(',', $question->custom_options)) : [];
                                                        @endphp
                                                        <div class="d-flex align-items-center flex-wrap">
                                                            @foreach($options as $opt)
                                                                @if($opt)
                                                                    <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" value="{{ $opt }}" {{ $currentAnswer == $opt ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                            {{ $opt }}
                                                                        </label>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @elseif($question->type == 'Conditional')
                                                        @php
                                                            $conditionalOptions = $question->custom_options ? array_map('trim', explode(',', $question->custom_options)) : ['Excellent', 'Good', 'Average', 'Unsatisfactory'];
                                                            $targetVisibleOptions = array_merge(['Yes'], $conditionalOptions);
                                                        @endphp
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input conditional-trigger" type="radio" name="feedbacks[{{ $question->id }}]" value="Yes" {{ in_array($currentAnswer, $targetVisibleOptions) ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }} onclick="toggleConditionalRating(this, {{ $question->id }})">
                                                                    {{ __('Yes') }}
                                                                </label>
                                                            </div>
                                                            <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input conditional-trigger" type="radio" name="feedbacks[{{ $question->id }}]" value="No" {{ $currentAnswer == 'No' ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }} onclick="toggleConditionalRating(this, {{ $question->id }})">
                                                                    {{ __('No') }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="align-items-center flex-wrap conditional-target-{{ $question->id }}" style="display: {{ in_array($currentAnswer, $targetVisibleOptions) ? 'flex' : 'none' }}; margin-left: 20px; border-left: 2px solid #ccc; padding-left: 10px;">
                                                            @foreach($conditionalOptions as $opt)
                                                                @if($opt)
                                                                    <div class="form-check form-check-inline mt-0 mb-2 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" value="{{ $opt }}" {{ $currentAnswer == $opt ? 'checked' : '' }} {{ $isFeedbackSubmitted ? 'disabled' : '' }}>
                                                                            {{ $opt }}
                                                                        </label>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @elseif($question->type == 'Text')
                                                        <input type="text" name="feedbacks[{{ $question->id }}]" class="form-control" value="{{ $currentAnswer }}" {{ $isFeedbackSubmitted ? 'readonly' : '' }}>
                                                    @elseif($question->type == 'Paragraph')
                                                        <textarea name="feedbacks[{{ $question->id }}]" class="form-control" rows="2" {{ $isFeedbackSubmitted ? 'readonly' : '' }}>{{ $currentAnswer }}</textarea>
                                                    @elseif($question->type == 'Number')
                                                        <input type="number" name="feedbacks[{{ $question->id }}]" class="form-control" value="{{ $currentAnswer }}" {{ $isFeedbackSubmitted ? 'readonly' : '' }}>
                                                    @elseif($question->type == 'Date')
                                                        <input type="date" name="feedbacks[{{ $question->id }}]" class="form-control" value="{{ $currentAnswer }}" {{ $isFeedbackSubmitted ? 'readonly' : '' }}>
                                                    @else
                                                        <textarea name="feedbacks[{{ $question->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter your feedback here...') }}" {{ $isFeedbackSubmitted ? 'readonly' : '' }}>{{ $currentAnswer }}</textarea>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                @if(!$isFeedbackSubmitted)
                                    <button type="submit" class="btn btn-primary theme-btn">{{ __('Save Feedback') }}</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
@section('script')
<script>
    document.getElementById('status-select').addEventListener('change', function() {
        var detailsDiv = document.getElementById('interview-details');
        var inputs = detailsDiv.querySelectorAll('input');
        
        var demoDiv = document.getElementById('demo-details');
        var demoInputs = demoDiv.querySelectorAll('input');

        var demoCompletedDiv = document.getElementById('demo-completed-details');
        var demoCompletedInputs = demoCompletedDiv.querySelectorAll('input');

        var docVerDiv = document.getElementById('document-verification-details');
        var docVerInputs = docVerDiv.querySelectorAll('input');

        if (this.value === 'Interview Scheduled') {
            detailsDiv.style.display = 'block';
            inputs.forEach(input => input.setAttribute('required', 'required'));
            demoDiv.style.display = 'none';
            demoInputs.forEach(input => input.removeAttribute('required'));
            demoCompletedDiv.style.display = 'none';
            demoCompletedInputs.forEach(input => input.removeAttribute('required'));
            docVerDiv.style.display = 'none';
            docVerInputs.forEach(input => input.removeAttribute('required'));
        } else if (this.value === 'Demo Scheduled') {
            demoDiv.style.display = 'block';
            demoInputs.forEach(input => input.setAttribute('required', 'required'));
            detailsDiv.style.display = 'none';
            inputs.forEach(input => input.removeAttribute('required'));
            demoCompletedDiv.style.display = 'none';
            demoCompletedInputs.forEach(input => input.removeAttribute('required'));
            docVerDiv.style.display = 'none';
            docVerInputs.forEach(input => input.removeAttribute('required'));
        } else if (this.value === 'Demo Completed') {
            demoCompletedDiv.style.display = 'block';
            demoCompletedInputs.forEach(input => input.setAttribute('required', 'required'));
            detailsDiv.style.display = 'none';
            inputs.forEach(input => input.removeAttribute('required'));
            demoDiv.style.display = 'none';
            demoInputs.forEach(input => input.removeAttribute('required'));
            docVerDiv.style.display = 'none';
            docVerInputs.forEach(input => input.removeAttribute('required'));
        } else if (this.value === 'Document Verification') {
            docVerDiv.style.display = 'block';
            docVerInputs.forEach(input => input.setAttribute('required', 'required'));
            detailsDiv.style.display = 'none';
            inputs.forEach(input => input.removeAttribute('required'));
            demoDiv.style.display = 'none';
            demoInputs.forEach(input => input.removeAttribute('required'));
            demoCompletedDiv.style.display = 'none';
            demoCompletedInputs.forEach(input => input.removeAttribute('required'));
        } else {
            detailsDiv.style.display = 'none';
            inputs.forEach(input => input.removeAttribute('required'));
            demoDiv.style.display = 'none';
            demoInputs.forEach(input => input.removeAttribute('required'));
            demoCompletedDiv.style.display = 'none';
            demoCompletedInputs.forEach(input => input.removeAttribute('required'));
            docVerDiv.style.display = 'none';
            docVerInputs.forEach(input => input.removeAttribute('required'));
        }
    });

    function toggleConditionalRating(el, questionId) {
        if (el.value === 'Yes') {
            $('.conditional-target-' + questionId).css('display', 'flex');
        } else if (el.value === 'No') {
            $('.conditional-target-' + questionId).css('display', 'none');
            // Uncheck the sub-options if No is selected
            $('.conditional-target-' + questionId + ' input[type="radio"]').prop('checked', false);
        }
    }

    function toggleRemarks(selectElement, id) {
        if (selectElement.value === 'Rejected') {
            $('#remarks-container-' + id).show();
            $('#remarks-container-' + id + ' textarea').prop('required', true);
        } else {
            $('#remarks-container-' + id).hide();
            $('#remarks-container-' + id + ' textarea').prop('required', false);
        }
    }
</script>
@endsection
