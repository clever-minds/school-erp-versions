{{-- Teacher Attendance Report Tab --}}

{{-- Stat Cards Row --}}
<div class="row">
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--total shadow-sm">
            <div class="attendance-stat-card__label">{{ __('total_days') }}</div>
            <div class="attendance-stat-card__value" id="total_days">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--present shadow-sm">
            <div class="attendance-stat-card__label">{{ __('present') }}</div>
            <div class="attendance-stat-card__value" id="present_count">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--absent shadow-sm">
            <div class="attendance-stat-card__label">{{ __('absent') }}</div>
            <div class="attendance-stat-card__value text-danger" id="absent_count">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--holiday shadow-sm">
            <div class="attendance-stat-card__label">{{ __('holidays') }}</div>
            <div class="attendance-stat-card__value" id="holiday_count" style="color: #64748b;">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--half shadow-sm">
            <div class="attendance-stat-card__label text-uppercase text-muted font-weight-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">{{ __('half_days') }}</div>
            <div class="attendance-stat-card__value" id="half_day_count" style="color: #f59e0b;">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--pct shadow-sm">
            <div class="attendance-stat-card__label">{{ __('percentage') }}</div>
            <div class="attendance-stat-card__value" id="attendance_percentage">0%</div>
        </div>
    </div>
</div>

{{-- Attendance Calendar --}}
<div class="attendance-cal-wrapper shadow-sm border-0">
    <div class="attendance-cal-top">
        <h5 class="mb-0">{{ __('monthly_attendance_tracker') }}</h5>
        <div class="attendance-month-filter">
            <select name="month" class="attendance-cal-month-select shadow-sm" id="attendance_month">
                @foreach($attendanceMonths as $month)
                    <option value="{{ $month->id }}" data-year="{{ $month->year }}"
                        {{ ($month->id == now()->month && $month->year == now()->year) || ($loop->first && !count(array_filter($attendanceMonths, fn($m) => $m->id == now()->month && $m->year == now()->year))) ? 'selected' : '' }}>
                        {{ $month->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="attendance-cal-grid" id="attendance_calendar_grid">
        {{-- Day Headers --}}
        <div class="attendance-cal-grid__day-header">S</div>
        <div class="attendance-cal-grid__day-header">M</div>
        <div class="attendance-cal-grid__day-header">T</div>
        <div class="attendance-cal-grid__day-header">W</div>
        <div class="attendance-cal-grid__day-header">T</div>
        <div class="attendance-cal-grid__day-header">F</div>
        <div class="attendance-cal-grid__day-header">S</div>
        {{-- Day cells populated via JS --}}
    </div>
        
    {{-- Legends --}}
    <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-center gap-3">
        <div class="d-flex align-items-center mr-3"><span class="badge" style="background:#e8fdf0; color:#10b981; min-width:30px;">P</span> <small class="ml-2 text-muted font-weight-bold">{{ __('present') }}</small></div>
        <div class="d-flex align-items-center mr-3"><span class="badge" style="background:#fee2e2; color:#ef4444; min-width:30px;">A</span> <small class="ml-2 text-muted font-weight-bold">{{ __('absent') }}</small></div>
        <div class="d-flex align-items-center mr-3"><span class="badge" style="background:#f1f5f9; color:#64748b; min-width:30px;">H</span> <small class="ml-2 text-muted font-weight-bold">{{ __('holiday') }}</small></div>
        <div class="d-flex align-items-center mr-3"><span class="badge" style="background:#fef3c7; color:#f59e0b; min-width:30px;">FH/SH</span> <small class="ml-2 text-muted font-weight-bold">{{ __('half_day') }}</small></div>
        <div class="d-flex align-items-center"><span class="badge" style="background:#e0e7ff; color:#6366f1; min-width:30px;">L</span> <small class="ml-2 text-muted font-weight-bold">{{ __('leave') }}</small></div>
    </div>
</div>
{{-- </div> --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadAttendanceData();
        document.getElementById('attendance_month').addEventListener('change', loadAttendanceData);

        function loadAttendanceData() {
            const monthSelect = document.getElementById('attendance_month');
            if (!monthSelect || monthSelect.selectedIndex === -1) return;

            const month = monthSelect.value;
            const year = monthSelect.options[monthSelect.selectedIndex].getAttribute('data-year');
            const teacherId = '{{ $teacher->user_id ?? $teacher->id }}';
            const sessionYearId = '{{ $sessionYear->id }}'; // For teacher it uses $sessionYear->id based on existing file

            // Show loading
            renderCalendarLoading();

            fetch(`{{ route('reports.teacher.attendance.report') }}?month=${month}&attendance_year=${year}&teacher_id=${teacherId}&session_year_id=${sessionYearId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    renderAttendanceData(data, parseInt(month), parseInt(year));
                })
                .catch(error => {
                    console.error('Error fetching attendance data:', error);
                    renderCalendarError();
                });
        }

        function renderCalendarLoading() {
            const grid = document.getElementById('attendance_calendar_grid');
            while (grid.children.length > 7) {
                grid.removeChild(grid.lastChild);
            }
            for (let i = 0; i < 7; i++) {
                const cell = document.createElement('div');
                cell.className = 'attendance-cal-cell attendance-cal-cell--empty';
                cell.style.opacity = '0.3';
                grid.appendChild(cell);
            }
        }

        function renderCalendarError() {
            const grid = document.getElementById('attendance_calendar_grid');
            while (grid.children.length > 7) grid.removeChild(grid.lastChild);
            const msg = document.createElement('div');
            msg.style.cssText = 'grid-column: 1/-1; text-align:center; padding:20px; color:#ef4444; font-size:13px;';
            msg.textContent = 'Failed to load attendance data';
            grid.appendChild(msg);
        }

        function renderAttendanceData(data, month, year) {
            const grid = document.getElementById('attendance_calendar_grid');
            while (grid.children.length > 7) grid.removeChild(grid.lastChild);

            const daysInMonth = new Date(year, month, 0).getDate();
            const firstDayOfWeek = new Date(year, month - 1, 1).getDay(); // 0=Sun

            if (data.success && data.attendance !== undefined) {
                // Determine leave data
                const leaves = data.leaves || {};

                // Add empty cells before 1st
                for (let i = 0; i < firstDayOfWeek; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'attendance-cal-cell attendance-cal-cell--empty';
                    grid.appendChild(empty);
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = `${year}-${month.toString().padStart(2,'0')}-${day.toString().padStart(2,'0')}`;
                    const cell = document.createElement('div');
                    cell.className = 'attendance-cal-cell';

                    const numEl = document.createElement('div');
                    numEl.className = 'attendance-cal-cell__num';
                    numEl.textContent = day;

                    const statusEl = document.createElement('div');
                    statusEl.className = 'attendance-cal-cell__status';

                    const holiday = data.holiday && data.holiday.length ? data.holiday.find(h => h === dateStr) : null;
                    const att = data.attendance && data.attendance.length ? data.attendance.find(a => a.get_date_original === dateStr) : null;
                    const hasLeave = leaves[dateStr];

                    if (holiday) {
                         cell.classList.add('attendance-cal-cell--holiday');
                         statusEl.textContent = 'H';
                    } else if (att) {
                        const t = String(att.type);
                        if (t === '1') {
                            cell.classList.add('attendance-cal-cell--present');
                            statusEl.textContent = 'P';
                        } else if (t === '0') {
                            cell.classList.add('attendance-cal-cell--absent');
                            statusEl.textContent = 'A';
                        } else if (t === '3') {
                            cell.classList.add('attendance-cal-cell--holiday');
                            statusEl.textContent = 'H';
                        } else if (t === '4') {
                            cell.classList.add('attendance-cal-cell--half');
                            statusEl.textContent = 'FH';
                        } else if (t === '5') {
                            cell.classList.add('attendance-cal-cell--half');
                            statusEl.textContent = 'SH';
                        }
                    } else if (hasLeave) {
                        cell.classList.add('attendance-cal-cell--leave');
                        statusEl.textContent = 'L';
                    } else {
                         // null/not taken
                         cell.classList.add('attendance-cal-cell--null');
                    }

                    cell.appendChild(numEl);
                    if (statusEl.textContent) {
                         cell.appendChild(statusEl);
                    }
                    grid.appendChild(cell);
                }

                // Update summary dashboard
                if (data.summary) {
                    document.getElementById('total_days').innerText = data.summary.total_days || 0;
                    document.getElementById('present_count').innerText = data.summary.present_count || 0;
                    document.getElementById('absent_count').innerText = data.summary.absent_count || 0;
                    document.getElementById('holiday_count').innerText = data.summary.holiday_count || 0;
                    document.getElementById('half_day_count').innerText = data.summary.half_count || 0;
                    document.getElementById('attendance_percentage').innerText = `${data.summary.attendance_percentage || 0}%`;
                }

            } else {
                renderCalendarError();
                document.getElementById('total_days').innerText = '0';
                document.getElementById('present_count').innerText = '0';
                document.getElementById('absent_count').innerText = '0';
                document.getElementById('holiday_count').innerText = '0';
                document.getElementById('half_day_count').innerText = '0';
                document.getElementById('attendance_percentage').innerText = '0%';
            }
        }
    });
</script>