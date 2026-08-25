{{-- Student Attendance Report Tab --}}

{{-- Stat Cards Row --}}
<div class="row mb-3">
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
            <div class="attendance-stat-card__label">{{ __('holiday') }}</div>
            <div class="attendance-stat-card__value" id="holiday_count" style="color: #64748b;">0</div>
        </div>
    </div>
    <div class="col-6 col-md mt-3">
        <div class="attendance-stat-card attendance-stat-card--pct shadow-sm">
            <div class="attendance-stat-card__label">{{ __('attendance') }} %</div>
            <div class="attendance-stat-card__value" id="attendance_percentage" style="color: #d97706;">0%</div>
        </div>
    </div>
</div>

{{-- Attendance Calendar --}}
<div class="attendance-cal-wrapper shadow-sm">
    <div class="attendance-cal-top">
        <h6 class="mb-0">{{ __('Attendance Calendar') }}</h6>
        <div class="attendance-month-filter">
            <select name="month" class="attendance-cal-month-select" id="attendance_month">
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadAttendanceData();
        document.getElementById('attendance_month').addEventListener('change', loadAttendanceData);

        function loadAttendanceData() {
            const monthSelect = document.getElementById('attendance_month');
            if (!monthSelect || monthSelect.selectedIndex === -1) return;

            const month = monthSelect.value;
            const year = monthSelect.options[monthSelect.selectedIndex].getAttribute('data-year');
            const studentId = '{{ $student->user_id ?? $student->id }}';
            const sessionYearId = '{{ $session_year_id }}';

            // Show loading
            renderCalendarLoading();

            fetch(`{{ route('reports.student.attendance.report') }}?month=${month}&attendance_year=${year}&student_id=${studentId}&session_year_id=${sessionYearId}`)
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
            // Keep day headers (first 7 children), remove cells
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
            // Remove old day cells (keep 7 header divs)
            while (grid.children.length > 7) grid.removeChild(grid.lastChild);

            const daysInMonth = new Date(year, month, 0).getDate();
            const firstDayOfWeek = new Date(year, month - 1, 1).getDay(); // 0=Sun

            if (data.success && data.attendance !== undefined) {
                // Add empty cells for days before the 1st
                for (let i = 0; i < firstDayOfWeek; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'attendance-cal-cell attendance-cal-cell--empty';
                    grid.appendChild(empty);
                }

                // Render each day
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = `${year}-${month.toString().padStart(2,'0')}-${day.toString().padStart(2,'0')}`;
                    const cell = document.createElement('div');
                    cell.className = 'attendance-cal-cell';

                    const numEl = document.createElement('div');
                    numEl.className = 'attendance-cal-cell__num';
                    numEl.textContent = day;

                    const statusEl = document.createElement('div');
                    statusEl.className = 'attendance-cal-cell__status';

                    // Check holiday
                    const holiday = data.holiday && data.holiday.length > 0
                        ? data.holiday.find(h => h.date === dateStr) : null;

                    // Check attendance
                    const att = data.attendance && data.attendance.length > 0
                        ? data.attendance.find(a => a.get_date_original === dateStr) : null;

                    if (holiday) {
                        cell.classList.add('attendance-cal-cell--holiday');
                        statusEl.textContent = 'H';
                        if (holiday.title) cell.title = holiday.title;
                    } else if (att) {
                        if (att.type == 1) {
                            cell.classList.add('attendance-cal-cell--present');
                            statusEl.textContent = 'P';
                        } else if (att.type == 0) {
                            cell.classList.add('attendance-cal-cell--absent');
                            statusEl.textContent = 'A';
                        } else if (att.type == 3) {
                            cell.classList.add('attendance-cal-cell--holiday');
                            statusEl.textContent = 'H';
                        } else {
                            statusEl.textContent = '–';
                        }
                    } else {
                        statusEl.textContent = '–';
                    }

                    cell.appendChild(numEl);
                    cell.appendChild(statusEl);
                    grid.appendChild(cell);
                }

                // Update summary stats
                if (data.summary) {
                    document.getElementById('total_days').textContent = data.summary.total_days || 0;
                    document.getElementById('present_count').textContent = data.summary.present_count || 0;
                    document.getElementById('absent_count').textContent = data.summary.absent_count || 0;
                    document.getElementById('holiday_count').textContent = data.summary.holiday_count || 0;
                    document.getElementById('attendance_percentage').textContent = `${data.summary.attendance_percentage || 0}%`;
                }

                // Tooltips
                if (typeof $ !== 'undefined') $('[title]').tooltip();
            } else {
                const msg = document.createElement('div');
                msg.style.cssText = 'grid-column:1/-1; text-align:center; padding:24px; color:#94a3b8; font-size:13px;';
                msg.textContent = 'No attendance records found for this month';
                grid.appendChild(msg);

                ['total_days','present_count','absent_count','holiday_count'].forEach(id => {
                    document.getElementById(id).textContent = '0';
                });
                document.getElementById('attendance_percentage').textContent = '0%';
            }
        }
    });
</script>