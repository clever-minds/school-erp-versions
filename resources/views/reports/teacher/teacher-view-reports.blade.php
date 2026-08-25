@extends('layouts.master')

@section('title')
    {{ __('teacher_profile') }} - {{ $teacher->first_name }} {{ $teacher->last_name }}
@endsection

@section('css')
    <link href="{{ asset('css/teacher-view-reports.css') }}" rel="stylesheet">
    <link href="{{ asset('css/attendance-report-shared.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <!-- Header -->
    <div class="tvr-header mb-0">
        <div class="tvr-header__avatar-wrap">
            <img src="{{ $teacher->image ?? asset('images/default-user.png') }}" class="tvr-header__avatar" alt="Profile Image" onerror="this.src='{{ asset('images/default-user.png') }}'">
            <span class="tvr-header__online-dot {{ (isset($teacher) && $teacher->status == 1) ? 'bg-success' : 'bg-danger' }}"></span>
        </div>

        <div class="tvr-header__meta">
            <div class="tvr-header__label">{{ __('teacher_report') }}</div>
            <h2 class="tvr-header__name">
                {{ $teacher->first_name }} {{ $teacher->last_name }}
            </h2>
            <div class="tvr-header__badges">
                <span class="tvr-header__id-chip">
                    <i class="fa fa-id-badge"></i> {{ __('id') }}: EMP- {{ $teacher->staff->id ?? 'EMP-0000' }}
                </span>
                
                @if(isset($teacher->staff) && $teacher->staff->qualification)
                <span class="tvr-header__room-badge"><span class="text-muted">{{ __('qualification') }}:</span> <span class="text-dark font-weight-bold">{{ strtoupper($teacher->staff->qualification) }}</span></span>
                @endif
                
                @if(isset($teacher->staff) && $teacher->staff->salary)
                <span class="tvr-header__room-badge">
                    <span class="font-weight-bold text-success">{{ __('salary') }}:</span> 
                    <span class="text-success">{{ env('CURRENCY_SYMBOL', '₹') }}{{ number_format($teacher->staff->salary, 0) }}</span>
                </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tvr-tabs-wrapper mt-3 mb-0">
        <ul class="nav tvr-nav-tabs" id="teacherTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">{{ __('profile') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="assigned_class_and_subject-tab" data-toggle="tab" href="#assigned_class_and_subject" role="tab">{{ __('assignments') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">{{ __('attendance') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="timetable-tab" data-toggle="tab" href="#timetable" role="tab">{{ __('timetable') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="salary-structure-tab" data-toggle="tab" href="#salary-structure" role="tab">{{ __('salary') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="leaves-tab" data-toggle="tab" href="#leaves" role="tab">{{ __('leaves') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="transportation-tab" data-toggle="tab" href="#transportation" role="tab">{{ __('transportation') }}</a>
            </li>
        </ul>
    </div>
    
    <!-- Tab Content Area -->
    <div class="tab-content tvr-tab-content" id="teacherTabContent">
        
        <!-- Profile Tab -->
        <div class="tab-pane fade show active py-3" id="profile" role="tabpanel">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6 mb-3">
                    <!-- Basic Information Card -->
                    <div class="tvr-info-panel mb-4">
                        <div class="tvr-info-panel__title">
                            <i class="fa fa-user-circle"></i>
                            {{ __('basic_information') }}
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-calendar-check-o me-2"> </i>{{ __('joining_date') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ explode(' ', $teacher->staff->joining_date)[0] ?? '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-calendar me-2"> </i>{{ __('dob') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $teacher->dob ? date('d-m-Y', strtotime($teacher->dob)) : '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-phone me-2"> </i>{{ __('mobile_number') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $teacher->mobile ?? '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-envelope me-2"> </i>{{ __('email_address') }}</div>
                            <div class="col-7 tvr-field__value text-right text-truncate">{{ $teacher->email ?? '-' }}</div>
                        </div>
                    </div>
                    
                    <!-- Address Information Card -->
                    <div class="tvr-info-panel">
                        <div class="tvr-info-panel__title">
                            <i class="fa fa-map-marker"></i>
                            {{ __('address_information') }}
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-map-marker me-2"> </i>{{ __('current_address') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $teacher->current_address ?: '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-map-marker me-2"> </i>{{ __('permanent_address') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $teacher->permanent_address ?: '-' }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-md-6 mb-3">
                    <!-- Professional Details Card -->
                    <div class="tvr-info-panel h-100">
                        <div class="tvr-info-panel__title">
                            <i class="fa fa-vcard-o"></i>
                            {{ __('professional_details') }}
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-graduation-cap me-2"></i>{{ __('qualification') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ ucfirst($teacher->staff->qualification ?? '-') }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-venus-mars me-2"></i>{{ __('gender') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ ucfirst($teacher->gender) }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-money me-2"></i>{{ __('base_salary') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($teacher->staff->salary ?? 0, 0) }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-info-circle me-2"></i>{{ __('account_status') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ (isset($teacher) && $teacher->status) ? __('active') : __('inactive') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div class="tab-pane fade py-3" id="assigned_class_and_subject" role="tabpanel">
            @include('reports.teacher.assigned-class-subject-report-tab', ['teacher' => $teacher])
        </div>
        
        <!-- Attendance Tab -->
        <div class="tab-pane fade" id="attendance" role="tabpanel">
            @include('reports.teacher.attendance-report-tab', ['teacher' => $teacher])
        </div>
        
        <!-- Timetable Tab -->
        <div class="tab-pane fade py-3" id="timetable" role="tabpanel">
            @include('reports.teacher.timetable-tab', ['timetables' => $timetables, 'timetableSettingsData' => $timetableSettingsData])
        </div>
        
        <!-- Salary Tab -->
        <div class="tab-pane fade py-3" id="salary-structure" role="tabpanel">
            @include('reports.teacher.salary-structure-tab', ['salary_structure' => $salary_structure])
        </div>
        
        <!-- Leaves Tab -->
        <div class="tab-pane fade py-3" id="leaves" role="tabpanel">
            @include('reports.teacher.leaves-tab', ['teacher' => $teacher])
        </div>
        
        <!-- Transportation Tab -->
        <div class="tab-pane fade py-3" id="transportation" role="tabpanel">
            @include('reports.teacher.transportation-report-tab', ['transportation' => $transportation, 'sessionYear' => $sessionYear])
        </div>

    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        // We only use Bootstrap's native tab data-toggle mechanism.
        // No custom .tab('show') required to avoid double-firing.
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endsection
