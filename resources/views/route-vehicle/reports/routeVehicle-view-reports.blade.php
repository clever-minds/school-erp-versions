@extends('layouts.master')

@section('title')
    {{ __('routeVehicle_report') }}
@endsection

@section('css')
    <link href="{{ asset('assets/css/routeVehicle-reports.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        {{-- style="background-color: var(--rv-bg-page);" --}}
        <div class="page-header border-0 mb-4 d-flex justify-content-between align-items-center">
            <h1 class="page-title fw-bold mb-0" style="color: var(--rv-text-primary); font-size: 1.5rem;">
                {{ __('routeVehicle_report') }}
            </h1>
        </div>
        <div class="row">
            <!-- ── Left Info Card ──────────────────────────────────── -->
            <div class="col-md-4 grid-margin">
                <div class="rv-info-card card border-0 shadow-sm">

                    {{-- Blue Header --}}
                    <div class="rv-card-header card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="rv-bus-icon">
                                <i class="fa fa-bus text-white"></i>
                            </div>
                            <div class="rv-shift-badge">
                                <span class="rv-shift-label">{{ __('route_shift') }}</span>
                                <h5 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ $routeVehicles->route->shift->name ?? '-' }}</h5>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <span class="rv-route-title-label">{{ __('route_name') }}</span>
                            <h4 class="rv-route-name">{{ $routeVehicles->route->name ?? '-' }}</h4>
                        </div>
                    </div>

                    {{-- Stats Grid --}}
                    <div class="rv-stats-grid">
                        <div class="rv-stat-item shadow-xs">
                            <div class="rv-stat-label">{{ __('bus_number') }}</div>
                            <div class="rv-stat-value text-uppercase">{{ $routeVehicles->vehicle->vehicle_number ?? '-' }}</div>
                        </div>
                        <div class="rv-stat-item shadow-xs">
                            <div class="rv-stat-label">{{ __('total_capacity') }}</div>
                            <div class="rv-stat-value">{{ $routeVehicles->vehicle->capacity ?? '-' }} <small>{{ __('seats') }}</small></div>
                        </div>
                    </div>

                    {{-- Operational Schedule --}}
                    <div class="rv-schedule-section">
                        <div class="rv-schedule-label">{{ __('operational_schedules') }}</div>
                        <div class="rv-schedule-grid">
                            <div class="rv-schedule-item">
                                <div class="rv-schedule-item-label">{{ __('pickup_start') }}</div>
                                <div class="rv-time-green">{{ Carbon\Carbon::parse($routeVehicles->pickup_start_time)->format($originalTimeFormat) ?? '-' }}</div>
                            </div>
                            <div class="rv-schedule-item">
                                <div class="rv-schedule-item-label">{{ __('pickup_end') }}</div>
                                <div class="rv-time-blue">{{ Carbon\Carbon::parse($routeVehicles->pickup_end_time)->format($originalTimeFormat) ?? '-' }}</div>
                            </div>
                            <div class="rv-schedule-item">
                                <div class="rv-schedule-item-label">{{ __('drop_start') }}</div>
                                <div class="rv-time-green">{{ Carbon\Carbon::parse($routeVehicles->drop_start_time)->format($originalTimeFormat) ?? '-' }}</div>
                            </div>
                            <div class="rv-schedule-item">
                                <div class="rv-schedule-item-label">{{ __('drop_end') }}</div>
                                <div class="rv-time-blue">{{ Carbon\Carbon::parse($routeVehicles->drop_end_time)->format($originalTimeFormat) ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Assigned Staff --}}
                    <div class="rv-staff-section">
                        <div class="rv-section-label mt-5">{{ __('assigned_staff') }}</div>

                        {{-- Driver --}}
                        @if ($routeVehicles->driver)
                            <div class="rv-staff-row">
                                <div class="d-flex align-items-center w-100">
                                    <img src="{{ $routeVehicles->driver->image }}" class="rv-avatar-circle" onerror="onErrorImage(event)" />
                                    <div class="rv-staff-info">
                                        <p class="rv-staff-name mb-0">{{ $routeVehicles->driver->full_name ?? '-' }}</p>
                                        <p class="rv-staff-meta">{{ $routeVehicles->driver->mobile ?? $routeVehicles->driver->email ?? '-' }}</p>
                                    </div>
                                    <span class="rv-staff-badge driver ml-auto">{{ __('driver') }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Helper --}}
                        @if ($routeVehicles->helper)
                            <div class="rv-staff-row">
                                <div class="d-flex align-items-center w-100">
                                    <img src="{{ $routeVehicles->helper->image }}" class="rv-avatar-circle" onerror="onErrorImage(event)" />
                                    <div class="rv-staff-info">
                                        <p class="rv-staff-name mb-0">{{ $routeVehicles->helper->full_name ?? '-' }}</p>
                                        <p class="rv-staff-meta">{{ $routeVehicles->helper->mobile ?? $routeVehicles->helper->email ?? '-' }}</p>
                                    </div>
                                    <span class="rv-staff-badge helper ml-auto">{{ __('helper') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- ── Right Tabbed Content ────────────────────────────── -->
            <div class="col-md-8 grid-margin">
                <div class="card shadow-sm border-0" style="border-radius:12px;overflow:hidden;">
                    <div class="card-body p-4">
                        {{-- Pill-style Tab Nav --}}
                        <ul class="nav nav-pills rv-tab-nav" id="rvTabNav" role="tablist">
                            <li class="nav-item rv-tab-item">
                                <a class="nav-link rv-tab-link active" id="pickup-points-tab" data-toggle="pill"
                                    href="#pickup-points" role="tab">
                                    <i class="fa fa-map-marker"></i>
                                    {{ __('pickup_points') }}
                                </a>
                            </li>
                            <li class="nav-item rv-tab-item">
                                <a class="nav-link rv-tab-link" id="student_attendance-tab" data-toggle="pill"
                                    href="#studentAttendanceSection" role="tab">
                                    <i class="fa fa-users"></i>
                                    {{ __('student_attendance') }}
                                </a>
                            </li>
                            <li class="nav-item rv-tab-item">
                                <a class="nav-link rv-tab-link" id="staff_attendance-tab" data-toggle="pill"
                                    href="#staffAttendanceSection" role="tab">
                                    <i class="fa fa-user"></i>
                                    {{ __('staff_attendance') }}
                                </a>
                            </li>
                            <li class="nav-item rv-tab-item">
                                <a class="nav-link rv-tab-link" id="trip_details-tab" data-toggle="pill"
                                    href="#tripDetailsSection" role="tab">
                                    <i class="fa fa-clock-o"></i>
                                    {{ __('trip_details') }}
                                </a>
                            </li>
                            <li class="nav-item rv-tab-item">
                                <a class="nav-link rv-tab-link" id="report_issues-tab" data-toggle="pill"
                                    href="#reportIssuesSection" role="tab">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    {{ __('report_issues') }}
                                </a>
                            </li>
                        </ul>

                        {{-- Tab Content --}}
                        <div class="tab-content border-0 px-0 pt-4" id="rvTabContent">

                            {{-- Pickup Points --}}
                            <div class="tab-pane fade show active" id="pickup-points" role="tabpanel">
                                <div class="rv-stops-header">
                                    <h6 class="rv-stops-title">{{ __('pickup_points_sequence') }}</h6>
                                    @if(isset($pickupPoints) && count($pickupPoints) > 0)
                                        <span class="rv-stops-count">{{ count($pickupPoints) }} {{ __('stops_total') }}</span>
                                    @endif
                                </div>

                                @if(isset($pickupPoints) && count($pickupPoints) > 0)
                                    <div class="rv-timeline">
                                        @foreach($pickupPoints as $index => $routePickup)
                                            @php $pickup = $routePickup->pickupPoint; @endphp
                                            <div class="rv-timeline-item">
                                                <div class="rv-timeline-card">
                                                    <div>
                                                        <p class="rv-stop-name">{{ $pickup->name ?? '-' }}</p>
                                                        <p class="rv-stop-seq">{{ __('sequence_stop') }} #{{ $index + 1 }}</p>
                                                    </div>
                                                    <div class="rv-stop-times">
                                                        <div class="rv-time-col">
                                                            <span class="rv-time-col-label">{{ __('pickup') }}</span>
                                                            <span class="rv-time-col-value">{{ Carbon\Carbon::parse($routePickup->pickup_time)->format($originalTimeFormat) ?? '-' }}</span>
                                                        </div>
                                                        <div class="rv-time-col">
                                                            <span class="rv-time-col-label">{{ __('drop') }}</span>
                                                            <span class="rv-time-col-value">{{ Carbon\Carbon::parse($routePickup->drop_time)->format($originalTimeFormat) ?? '-' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-exclamation-circle fs-4 mb-2 d-block"></i>
                                        {{ __('no_pickup_points_added_for_this_route') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Student Attendance --}}
                            <div class="tab-pane fade" id="studentAttendanceSection" role="tabpanel">
                                @include('route-vehicle.reports.student-attendance-report', ['students' => $students, 'session_year_id' => $session_year_id])
                            </div>

                            {{-- Staff Attendance --}}
                            <div class="tab-pane fade" id="staffAttendanceSection" role="tabpanel">
                                @include('route-vehicle.reports.staff-attendance-report', ['staff' => $staffs, 'session_year_id' => $session_year_id])
                            </div>

                            {{-- Trip Details --}}
                            <div class="tab-pane fade" id="tripDetailsSection" role="tabpanel">
                                @include('route-vehicle.reports.trip-details-section', ['id' => $routeVehicles->id])
                            </div>

                            {{-- Report Issues --}}
                            <div class="tab-pane fade rv-issues-wrapper" id="reportIssuesSection" role="tabpanel">
                                @include('route-vehicle.reports.report-issues-section', ['id' => $routeVehicles->id])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/routeVehicle-reports.js') }}"></script>
@endpush