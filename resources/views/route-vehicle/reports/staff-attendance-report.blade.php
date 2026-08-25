<!-- Staff Attendance Report Tab -->
<div class="p-3">
    <div class="attendance-summary-header d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--rv-text-primary); font-size: 1.25rem;">{{ __('attendance_summary') }}</h5>
            <p class="small mb-0" style="color: var(--rv-text-secondary);">{{ __('track_and_monitor_daily_trip_attendance') }}</p>
        </div>

        <div class="d-flex align-items-center gap-3 ml-auto">
            <!-- View toggles -->
            <div class="btn-group btn-group-toggle p-1 bg-white border rounded-pill shadow-xs" data-toggle="buttons">
                <label class="btn btn-sm btn-outline-primary border-0 pickup-btn rounded-pill px-4 active" id="pickupView">
                    <input type="radio" name="options" checked> <i class="fa fa-arrow-circle-down mr-1"></i> {{ __('pickup') }}
                </label>
                <label class="btn btn-sm btn-outline-primary border-0 drop-btn rounded-pill px-4" id="dropView">
                    <input type="radio" name="options"> <i class="fa fa-arrow-circle-up mr-1"></i> {{ __('drop') }}
                </label>
            </div>

            <!-- Month navigation -->
            <div class="monthlyAttendanceDateSection d-flex align-items-center border rounded-pill px-2 py-1 bg-white shadow-xs">
                <button id="prevMonth" class="btn btn-sm btn-link text-secondary p-1 prev-month">
                    <i class="fa fa-chevron-left"></i>
                </button>
                <div class="px-3">
                    <h6 id="currentMonth" class="mb-0 fw-bold current-month" style="min-width: 120px; text-align: center; color: var(--rv-text-primary);"> - </h6>
                </div>
                <button id="nextMonth" class="btn btn-sm btn-link text-secondary p-1 next-month">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="rv-modern-card card mb-4">
        <div class="card-header border-0 bg-transparent py-4 px-4">
            <div class="attendance-legends d-flex flex-wrap align-items-center gap-4">
                <div class="legend-item"><i class="fa fa-circle text-success mr-2"></i> <span class="small font-weight-bold text-secondary">{{ __('present') }}</span></div>
                <div class="legend-item"><i class="fa fa-circle text-danger mr-2"></i> <span class="small font-weight-bold text-secondary">{{ __('absent') }}</span></div>
                <div class="legend-item"><i class="fa fa-circle text-info mr-2"></i> <span class="small font-weight-bold text-secondary">{{ __('holiday') }}</span></div>
                <div class="legend-item"><i class="fa fa-circle text-secondary mr-2"></i> <span class="small font-weight-bold text-secondary">{{ __('not_marked') }}</span></div>
            </div>
        </div>
        
        <div class="rv-sticky-container">
            <div class="table-responsive">
                <table class="table table-hover attendance-table">
                    <thead>
                        <tr>
                            <th class="text-left py-3 px-4">{{ __('staff_details') }}</th>
                            @for($i = 1; $i <= 31; $i++)
                                <th class="py-3">{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody id="attendance_data" class="attendance-data">
                        <!-- Attendance data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initAttendanceSection({
            sectionId: 'staffAttendanceSection',
            fetchUrl: '{{ route("route-vehicle.user.attendance.report") }}',
            userIds: JSON.parse('{{ $staffs ?? "[]" }}'),
            sessionYearId: '{{ $session_year_id }}',
            isStaff: true
        });
    });
</script>