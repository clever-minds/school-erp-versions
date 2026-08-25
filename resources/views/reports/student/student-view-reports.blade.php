@extends('layouts.master')

@section('title')
    {{ __('student_profile') }} - {{ $student->user->first_name }} {{ $student->user->last_name }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/student-view-reports.css') }}">
    <link rel="stylesheet" href="{{ asset('css/attendance-report-shared.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student-diary-tab.css') }}">
    <link rel="stylesheet" href="{{ asset('css/transportation-logs-tab.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        
        {{-- ── Student Header Banner ── --}}
        <div class="svr-header mb-0">
            <div class="svr-header__avatar-wrap">
                <img src="{{ $student->user->image ?? asset('images/default-user.png') }}"
                     class="svr-header__avatar"
                     alt="{{ $student->user->first_name }}'s Photo"
                     onerror="this.src='{{ asset('images/default-user.png') }}'">
                <span class="svr-header__online-dot"></span>
            </div>

            <div class="svr-header__meta">
                <div class="svr-header__label">{{ __('student_profile') }}</div>
                <h2 class="svr-header__name">{{ $student->user->first_name }} {{ $student->user->last_name }}</h2>
                <div class="svr-header__badges">
                    <span class="svr-header__id-chip">
                        <i class="fa fa-id-badge"></i>
                        ID: {{ $student->admission_no }}
                    </span>
                    @if($student->class_section)
                        <span class="svr-header__room-badge">
                            <i class="fa fa-chalkboard-user"></i>
                            {{ $student->class_section->full_name ?? '' }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- No export action per user requirement --}}
        </div>

        {{-- ── Tab Strip ── --}}
        <div class="svr-tabs-wrapper mt-3 mb-0">
            <ul class="nav svr-nav-tabs" id="studentTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">
                        {{ __('profile') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" id="diary-tab" data-toggle="tab" href="#diary" role="tab">
                        {{ __('diary') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                        {{ __('attendance') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="exam-tab" data-toggle="tab" href="#exam" role="tab">
                        {{ __('exam') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="fees-tab" data-toggle="tab" href="#fees" role="tab">
                        {{ __('Fees') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="transportation-tab" data-toggle="tab" href="#transportation" role="tab">
                        {{ __('transportation') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="transportation-logs-tab" data-toggle="tab" href="#transportation-logs" role="tab">
                        {{ __('transportation_logs') }}
                    </a>
                </li>
            </ul>
        </div>

        {{-- ── Tab Content ── --}}
        <div class="tab-content svr-tab-content" id="studentTabContent">

            {{-- Profile Tab --}}
            <div class="tab-pane fade show active py-3" id="profile" role="tabpanel">
                <div class="row">
                    {{-- Basic Info + Address --}}
                    <div class="col-md-6 mb-3">
                        <div class="svr-info-panel">
                            <div class="svr-info-panel__title">
                                <i class="fa fa-user-circle"></i>
                                {{ __('basic_information') }}
                            </div>

                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-calendar-check me-2"> </i>{{ __('joining_session_year') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->join_session_year->name ?? '-' }}</div>
                            </div>

                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-calendar-check me-2"> </i>{{ __('admission_date') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->admission_date ?? '-' }}</div>
                            </div>
                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-calendar me-2"> </i>{{ __('dob') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->user->dob ?? '-' }}</div>
                            </div>
                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-phone me-2"> </i>{{ __('mobile_number') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->user->mobile ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Parent/Guardian Info --}}
                    <div class="col-md-6 mb-3">
                        <div class="svr-info-panel">
                            <div class="svr-info-panel__title">
                                <i class="fa fa-users"></i>
                                {{ __('parent_guardian_information') }}
                            </div>
                            @if(isset($student->guardian))
                                <div class="row svr-field align-items-start">
                                    <div class="col-5 svr-field__label"><i class="fa fa-user me-2"> </i>{{ __('name') }}</div>
                                    <div class="col-7 svr-field__value text-right">{{ $student->guardian->first_name }} {{ $student->guardian->last_name }}</div>
                                </div>
                                <div class="row svr-field align-items-start">
                                    <div class="col-5 svr-field__label"><i class="fa fa-envelope me-2"></i>{{ __('email') }}</div>
                                    <div class="col-7 svr-field__value text-right">{{ $student->guardian->email }}</div>
                                </div>
                                <div class="row svr-field align-items-start">
                                    <div class="col-5 svr-field__label"><i class="fa fa-mars me-2"></i>{{ __('gender') }}</div>
                                    <div class="col-7 svr-field__value text-right">{{ ucfirst($student->guardian->gender) }}</div>
                                </div>
                                <div class="row svr-field align-items-start">
                                    <div class="col-5 svr-field__label"><i class="fa fa-phone me-2"></i>{{ __('mobile_number') }}</div>
                                    <div class="col-7 svr-field__value text-right">{{ $student->guardian->mobile }}</div>
                                </div>
                            @else
                                <div class="svr-no-data">
                                    <i class="fa fa-info-circle fa-2x mb-2 d-block"></i>
                                    {{ __('no_guardian_information_available') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Address Info --}}
                    <div class="col-md-6 mb-3">
                        <div class="svr-info-panel">
                            <div class="svr-info-panel__title">
                                <i class="fa fa-map-marker"></i>
                                {{ __('address_information') }}
                            </div>
                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-map-marker me-2"> </i>{{ __('current_address') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->user->current_address ?: '-' }}</div>
                            </div>
                            <div class="row svr-field align-items-start">
                                <div class="col-5 svr-field__label"><i class="fa fa-map-marker me-2"> </i>{{ __('permanent_address') }}</div>
                                <div class="col-7 svr-field__value text-right">{{ $student->user->permanent_address ?: '-' }}</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Diary Tab --}}
            <div class="tab-pane fade py-3" id="diary" role="tabpanel">
                @include('reports.student.diary-report-tab')
            </div>

            {{-- Attendance Tab --}}
            <div class="tab-pane fade" id="attendance" role="tabpanel">
                @include('reports.student.attendance-report-tab', ['sessionYears' => $sessionYears])
            </div>

            {{-- Exam Tab --}}
            <div class="tab-pane fade py-3" id="exam" role="tabpanel">
                @include('reports.student.exam-report-tab')
            </div>

            {{-- Fees Tab --}}
            <div class="tab-pane fade py-3" id="fees" role="tabpanel">
                @include('reports.student.fees-report-tab', ['studentFees' => $studentFees])
            </div>

            {{-- Transportation Tab --}}
            <div class="tab-pane fade py-3" id="transportation" role="tabpanel">
                @include('reports.student.transportation-report-tab', ['transportation' => $transportation])
            </div>

            {{-- Transportation Logs Tab --}}
            <div class="tab-pane fade py-3" id="transportation-logs" role="tabpanel">
                @include('reports.shared.transportation-logs-tab')
            </div>

        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/student-diary-tab.js') }}"></script>
    <script>
        $(function () {


            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection