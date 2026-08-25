@extends('layouts.master')

@section('title')
    {{ __('staff_report') }} - {{ $user->first_name }} {{ $user->last_name }}
@endsection

@section('css')
    <link href="{{ asset('css/teacher-view-reports.css') }}" rel="stylesheet">
    <link href="{{ asset('css/attendance-report-shared.css') }}" rel="stylesheet">
    <link href="{{ asset('css/transportation-logs-tab.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <!-- Header -->
    <div class="tvr-header mb-0">
        <div class="tvr-header__avatar-wrap">
            <img src="{{ $user->image ?? asset('images/default-user.png') }}" class="tvr-header__avatar" alt="Profile Image" onerror="this.src='{{ asset('images/default-user.png') }}'">
            <span class="tvr-header__online-dot {{ (isset($user) && $user->status == 1) ? 'bg-success' : 'bg-danger' }}"></span>
        </div>

        <div class="tvr-header__meta">
            <div class="tvr-header__label">{{ __('staff_report') }}</div>
            <h2 class="tvr-header__name">
                {{ $user->first_name }} {{ $user->last_name }}
            </h2>
            <div class="tvr-header__badges">
                <span class="tvr-header__id-chip">
                    <i class="fa fa-id-badge"></i> {{ __('id') }}: EMP- {{ $user->staff->id ?? 'EMP-0000' }}
                </span>
                
                @if(isset($user->staff) && $user->staff->qualification)
                <span class="tvr-header__room-badge"><span class="text-muted">{{ __('qualification') }}:</span> <span class="text-dark font-weight-bold">{{ strtoupper($user->staff->qualification) }}</span></span>
                @endif
                
                @if(isset($user->staff) && $user->staff->salary)
                <span class="tvr-header__room-badge">
                    <span class="font-weight-bold text-success">{{ __('salary') }}:</span> 
                    <span class="text-success">{{ $schoolSettings['currency_symbol'] ?? '$' }}{{ number_format($user->staff->salary, 0) }}</span>
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
                <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">{{ __('attendance') }}</a>
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
            <li class="nav-item">
                <a class="nav-link" id="transportation-logs-tab" data-toggle="tab" href="#transportation-logs" role="tab">{{ __('transportation_logs') }}</a>
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
                            <div class="col-5 tvr-field__label"><i class="fa fa-calendar-check me-2"> </i>{{ __('joining_date') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ explode(' ', $user->staff->joining_date)[0] ?? '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-calendar me-2"> </i>{{ __('dob') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $user->dob }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-phone me-2"> </i>{{ __('mobile_number') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $user->mobile ?? '-' }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-envelope me-2"> </i>{{ __('email_address') }}</div>
                            <div class="col-7 tvr-field__value text-right text-truncate">{{ $user->email ?? '-' }}</div>
                        </div>

                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-user-secret me-2"> </i>{{ __('role') }}</div>
                            <div class="col-7 tvr-field__value text-right text-truncate">{{ $user->roles->pluck('name')->implode(', ') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-md-6 mb-3">
                    <!-- Professional Details Card -->
                    <div class="tvr-info-panel">
                        <div class="tvr-info-panel__title">
                            <i class="fa fa-vcard-o"></i>
                            {{ __('professional_details') }}
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-hand-holding-dollar me-2"></i>{{ __('base_salary') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ $schoolSettings['currency_symbol'] ?? '$' }} {{ number_format($user->staff->salary ?? 0, 0) }}</div>
                        </div>
                        
                        <div class="row tvr-field align-items-start">
                            <div class="col-5 tvr-field__label"><i class="fa fa-info-circle me-2"></i>{{ __('account_status') }}</div>
                            <div class="col-7 tvr-field__value text-right">{{ (isset($user) && $user->status) ? __('active') : __('inactive') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance Tab -->
        <div class="tab-pane fade" id="attendance" role="tabpanel">
            @include('reports.teacher.attendance-report-tab', ['teacher' => $user])
        </div>
        
        <!-- Salary Tab -->
        <div class="tab-pane fade py-3" id="salary-structure" role="tabpanel">
            @include('reports.teacher.salary-structure-tab', ['salary_structure' => $salary_structure])
        </div>
        
        <!-- Leaves Tab -->
        <div class="tab-pane fade py-3" id="leaves" role="tabpanel">
            @include('reports.teacher.leaves-tab', ['teacher' => $user])
        </div>
        
        <!-- Transportation Tab -->
        <div class="tab-pane fade py-3" id="transportation" role="tabpanel">
            @include('reports.teacher.transportation-report-tab', ['transportation' => $transportation, 'sessionYear' => $sessionYear])
        </div>

        <!-- Transportation Logs Tab -->
        <div class="tab-pane fade py-3" id="transportation-logs" role="tabpanel">
            @include('reports.shared.transportation-logs-tab', ['transportationLogs' => $transportationLogs])
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
