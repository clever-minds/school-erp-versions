{{-- Student Transportation Report Tab --}}

@if ($transportation)
    <div class="svr-transport-grid">

        {{-- ── Left: Plan Details + Shift Card ── --}}
        <div>
            <div class="svr-transport-panel">
                <h6 class="svr-transport-panel__title">{{ __('plan_details') }}</h6>

                <div class="svr-transport-row">
                    <div class="svr-transport-row__label">
                        <i class="fa fa-check-circle"></i>
                        {{ __('plan_status') }}
                    </div>
                    <div class="svr-transport-row__value">
                        {{ $transportation['plan']['plan_status'] ?? '-' }}
                    </div>
                </div>

                <div class="svr-transport-row">
                    <div class="svr-transport-row__label">
                        <i class="fa fa-calendar"></i>
                        {{ __('start_date') }}
                    </div>
                    <div class="svr-transport-row__value">
                        {{ $transportation['plan']['date'] ?? '-' }}
                    </div>
                </div>

                <div class="svr-transport-row">
                    <div class="svr-transport-row__label">
                        <i class="fa fa-bus"></i>
                        {{ __('vehicle_assignment') }}
                    </div>
                    <div class="svr-transport-row__value">
                        {{ $transportation['plan']['vehicle_assignment'] ?? '-' }}
                    </div>
                </div>

                <div class="svr-transport-row">
                    <div class="svr-transport-row__label">
                        <i class="fa fa-calendar"></i>
                        {{ __('expiry_date') }}
                    </div>
                    <div class="svr-transport-row__value">
                        {{ $transportation['plan']['expiry_date'] ?? '-' }}
                    </div>
                </div>

                <div class="svr-transport-row">
                    <div class="svr-transport-row__label">
                        <i class="fa fa-money"></i>
                        {{ __('paid_amount') }}
                    </div>
                    <div class="svr-transport-row__value text-green" style="font-weight:700;">
                        {{ $transportation['plan']['paid_amount'] ?? '-' }}
                    </div>
                </div>
            </div>

            {{-- Shift Card --}}
            <div class="svr-shift-card">
                <div class="svr-shift-card__label">{{ __('shift_details') }}</div>
                <div class="svr-shift-card__name">{{ $transportation['shift']['name'] ?? '-' }}</div>
                <div class="svr-shift-times">
                    <div class="svr-shift-time-pill">
                        <span>{{ __('Start') }}</span>
                        {{ $transportation['shift']['start_time'] ?? '-' }}
                    </div>
                    <div class="svr-shift-time-pill">
                        <span>{{ __('End') }}</span>
                        {{ $transportation['shift']['end_time'] ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right: Route & Vehicle ── --}}
        <div>
            <div class="svr-transport-panel">
                <h6 class="svr-transport-panel__title">{{ __('route_and_vehicle') }}</h6>

                {{-- Route Name Card Row --}}
                <div class="svr-route-card-row">
                    <div class="svr-route-card-row__label">{{ __('route_name') }}</div>
                    <div class="svr-route-card-row__value">{{ $transportation['route']['route_name'] ?? '-' }}</div>
                </div>

                {{-- Pickup Point | Pickup Time + Drop Time --}}
                <div class="svr-route-card-row d-flex align-items-stretch" style="padding: 0;">
                    {{-- Pickup Point (left) --}}
                    <div class="svr-route-card-half" style="border-right: 1px solid #eef0f4;">
                        <div class="svr-route-card-row__label">{{ __('pickup_point_name') }}</div>
                        <div class="svr-route-card-row__value">{{ $transportation['route']['pickup_point_name'] ?? '-' }}</div>
                    </div>

                    {{-- Pickup Time + Drop Time (right) --}}
                    <div class="svr-route-card-half d-flex" style="gap: 0;">
                        <div class="svr-route-card-half" style="border-right: 1px solid #eef0f4;">
                            <div class="svr-route-card-row__label">{{ __('pickup_time') }}</div>
                            <div class="svr-route-card-row__value">{{ $transportation['route']['pickup_time'] ?? '-' }}</div>
                        </div>
                        @if(!empty($transportation['route']['drop_time']))
                            <div class="svr-route-card-half">
                                <div class="svr-route-card-row__label">{{ __('drop_time') }}</div>
                                <div class="svr-route-card-row__value">{{ $transportation['route']['drop_time'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Vehicle info --}}
                @if(!empty($transportation['vehicle']['name']) || !empty($transportation['vehicle']['number']))
                    <div class="px-3 pb-3" style="border-top: 1px solid #f1f5f9; padding-top: 10px;">
                        <div class="svr-route-sub-item__label mb-2">{{ __('vehicle_details') }}</div>
                        <div class="d-flex gap-16">
                            @if(!empty($transportation['vehicle']['name']))
                                <div class="mr-4">
                                    <span style="font-size:11px; color:#94a3b8; text-transform: uppercase; font-weight:700;">{{ __('name') }}</span>
                                    <div style="font-size:13px; font-weight:600; color:#1e293b;">{{ $transportation['vehicle']['name'] }}</div>
                                </div>
                            @endif
                            @if(!empty($transportation['vehicle']['number']))
                                <div class="mr-4">
                                    <span style="font-size:11px; color:#94a3b8; text-transform: uppercase; font-weight:700;">{{ __('number') }}</span>
                                    <div style="font-size:13px; font-weight:600; color:#1e293b;">{{ $transportation['vehicle']['number'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Driver & Helper --}}
                <div class="svr-people-grid">
                    {{-- Driver --}}
                    <div>
                        @if(isset($transportation['vehicle']['driver']))
                            <div class="svr-person-card">
                                <a data-toggle="lightbox" href="{{ $transportation['vehicle']['driver']['image'] }}">
                                    <img src="{{ $transportation['vehicle']['driver']['image'] }}"
                                         class="svr-person-card__avatar"
                                         alt="Driver"
                                         onerror="onErrorImage(event)">
                                </a>
                                <div>
                                    <div class="svr-person-card__role">{{ __('driver') }}</div>
                                    <div class="svr-person-card__name">{{ $transportation['vehicle']['driver']['full_name'] ?? '-' }}</div>
                                    <div class="svr-person-card__phone">
                                        <i class="fa fa-phone" style="font-size:10px;"></i>
                                        {{ $transportation['vehicle']['driver']['mobile'] ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="svr-no-data" style="padding:16px; font-size:12px;">
                                {{ __('no_driver_assigned') }}
                            </div>
                        @endif
                    </div>

                    {{-- Helper --}}
                    <div>
                        @if(isset($transportation['vehicle']['helper']))
                            <div class="svr-person-card">
                                <a data-toggle="lightbox" href="{{ $transportation['vehicle']['helper']['image'] }}">
                                    <img src="{{ $transportation['vehicle']['helper']['image'] }}"
                                         class="svr-person-card__avatar"
                                         alt="Helper"
                                         onerror="onErrorImage(event)">
                                </a>
                                <div>
                                    <div class="svr-person-card__role">{{ __('helper') }}</div>
                                    <div class="svr-person-card__name">{{ $transportation['vehicle']['helper']['full_name'] ?? '-' }}</div>
                                    <div class="svr-person-card__phone">
                                        <i class="fa fa-phone" style="font-size:10px;"></i>
                                        {{ $transportation['vehicle']['helper']['mobile'] ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="svr-no-data" style="padding:16px; font-size:12px;">
                                {{ __('no_helper_assigned') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@else
    <div class="svr-no-data" style="padding: 60px 20px;">
        <i class="fa fa-bus fa-2x mb-3 d-block" style="color:#94a3b8;"></i>
        {{ __('did_not_opt_for_transportation_service') }}
    </div>
@endif


{{-- Transportation Attendance Stats --}}
    <div class="row mb-3 mt-2">
        <div class="col-6 col-md mt-3">
            <div class="attendance-stat-card attendance-stat-card--total shadow-sm">
                <div class="attendance-stat-card__label">{{ __('total_days') }}</div>
                <div class="attendance-stat-card__value" id="trans_total_days">0</div>
            </div>
        </div>
        <div class="col-6 col-md mt-3">
            <div class="attendance-stat-card attendance-stat-card--present shadow-sm">
                <div class="attendance-stat-card__label">{{ __('present') }}</div>
                <div class="attendance-stat-card__value" id="trans_present_count">0</div>
            </div>
        </div>
        <div class="col-6 col-md mt-3">
            <div class="attendance-stat-card attendance-stat-card--absent shadow-sm">
                <div class="attendance-stat-card__label">{{ __('absent') }}</div>
                <div class="attendance-stat-card__value text-danger" id="trans_absent_count">0</div>
            </div>
        </div>
        <div class="col-6 col-md mt-3">
            <div class="attendance-stat-card attendance-stat-card--holiday shadow-sm">
                <div class="attendance-stat-card__label">{{ __('holiday') }}</div>
                <div class="attendance-stat-card__value" id="trans_holiday_count" style="color: #64748b;">0</div>
            </div>
        </div>
        <div class="col-6 col-md mt-3">
            <div class="attendance-stat-card attendance-stat-card--pct shadow-sm">
                <div class="attendance-stat-card__label">{{ __('attendance') }} %</div>
                <div class="attendance-stat-card__value" id="trans_attendance_percentage" style="color: #d97706;">0%</div>
            </div>
        </div>
    </div>

    {{-- Transportation Attendance Calendar --}}
    <div class="attendance-cal-wrapper shadow-sm">
        <div class="attendance-cal-top">
            <h6 class="mb-0">{{ __('Attendance Calendar') }}</h6>
            <div class="attendance-month-filter">
                <div class="btn-group shadow-sm bg-light rounded" role="group" aria-label="View Mode" style="border: 1px solid #e2e8f0; padding: 2px;">
                    <button type="button" class="btn btn-sm btn-light pickup-btn active shadow-sm" style="font-weight: 600; border: none; padding: 4px 12px; font-size: 13px;" id="pickupView">{{ __('pickup') }}</button>
                    <button type="button" class="btn btn-sm btn-light drop-btn" style="color: #64748b; font-weight: 600; border: none; background: transparent; padding: 4px 12px; font-size: 13px;" id="dropView">{{ __('drop') }}</button>
                </div>
                <select name="month" class="attendance-cal-month-select" id="trans_attendance_month">
                    @foreach($attendanceMonths ?? [] as $month)
                        <option value="{{ $month->id ?? $month['id'] }}" data-year="{{ $month->year ?? $month['year'] }}"
                            {{ (($month->id ?? $month['id']) == now()->month && ($month->year ?? $month['year']) == now()->year) || ($loop->first && !count(array_filter(is_array($attendanceMonths) ? $attendanceMonths : $attendanceMonths->toArray(), fn($m) => ($m->id ?? $m['id']) == now()->month && ($m->year ?? $m['year']) == now()->year))) ? 'selected' : '' }}>
                            {{ $month->name ?? $month['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="attendance-cal-grid" id="trans_attendance_calendar_grid">
            <div class="attendance-cal-grid__day-header">S</div>
            <div class="attendance-cal-grid__day-header">M</div>
            <div class="attendance-cal-grid__day-header">T</div>
            <div class="attendance-cal-grid__day-header">W</div>
            <div class="attendance-cal-grid__day-header">T</div>
            <div class="attendance-cal-grid__day-header">F</div>
            <div class="attendance-cal-grid__day-header">S</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentTransType = 'pickup';

            loadTransAttendanceData();
            const monthElem = document.getElementById('trans_attendance_month');
            if(monthElem) monthElem.addEventListener('change', loadTransAttendanceData);

            const pickupBtn = document.getElementById('pickupView');
            const dropBtn = document.getElementById('dropView');

            if (pickupBtn) {
                pickupBtn.addEventListener('click', function() {
                    currentTransType = 'pickup';
                    pickupBtn.classList.add('active', 'shadow-sm');
                    pickupBtn.style.color = '';
                    pickupBtn.style.background = '';
                    
                    if (dropBtn) {
                        dropBtn.classList.remove('active', 'shadow-sm');
                        dropBtn.style.color = '#64748b';
                        dropBtn.style.background = 'transparent';
                    }
                    loadTransAttendanceData();
                });
            }

            if (dropBtn) {
                dropBtn.addEventListener('click', function() {
                    currentTransType = 'drop';
                    dropBtn.classList.add('active', 'shadow-sm');
                    dropBtn.style.color = '';
                    dropBtn.style.background = '';
                    
                    if (pickupBtn) {
                        pickupBtn.classList.remove('active', 'shadow-sm');
                        pickupBtn.style.color = '#64748b';
                        pickupBtn.style.background = 'transparent';
                    }
                    loadTransAttendanceData();
                });
            }

            function loadTransAttendanceData() {
                const monthSelect = document.getElementById('trans_attendance_month');
                if (!monthSelect || monthSelect.selectedIndex === -1) return;

                const month = monthSelect.value;
                const year = monthSelect.options[monthSelect.selectedIndex].getAttribute('data-year');
                const type = currentTransType;
                const studentId = '{{ $student->user_id ?? $student->id }}';
                const sessionYearId = '{{ $session_year_id ?? 0 }}';

                renderTransCalendarLoading();

                fetch(`{{ route('reports.student.transportation_attendance.report') }}?month=${month}&attendance_year=${year}&student_id=${studentId}&session_year_id=${sessionYearId}&type=${type}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        renderTransAttendanceData(data, parseInt(month), parseInt(year));
                    })
                    .catch(error => {
                        console.error('Error fetching transportation attendance data:', error);
                        renderTransCalendarError();
                    });
            }

            function renderTransCalendarLoading() {
                const grid = document.getElementById('trans_attendance_calendar_grid');
                while (grid.children.length > 7) grid.removeChild(grid.lastChild);
                for (let i = 0; i < 7; i++) {
                    const cell = document.createElement('div');
                    cell.className = 'svr-cal-cell svr-cal-cell--empty';
                    cell.style.opacity = '0.3';
                    grid.appendChild(cell);
                }
            }

            function renderTransCalendarError() {
                const grid = document.getElementById('trans_attendance_calendar_grid');
                while (grid.children.length > 7) grid.removeChild(grid.lastChild);
                const msg = document.createElement('div');
                msg.style.cssText = 'grid-column: 1/-1; text-align:center; padding:20px; color:#ef4444; font-size:13px;';
                msg.textContent = 'Failed to load transportation attendance data';
                grid.appendChild(msg);
            }

            function renderTransAttendanceData(data, month, year) {
                const grid = document.getElementById('trans_attendance_calendar_grid');
                while (grid.children.length > 7) grid.removeChild(grid.lastChild);

                const daysInMonth = new Date(year, month, 0).getDate();
                const firstDayOfWeek = new Date(year, month - 1, 1).getDay(); // 0=Sun

                if (data.success && data.attendance !== undefined) {
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

                        const holiday = data.holiday && data.holiday.length > 0
                            ? data.holiday.find(h => h.date === dateStr) : null;

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
                                // Add tooltip for details if needed
                                const details = data.details && data.details.length > 0
                                    ? data.details.find(d => d.date === dateStr) : null;
                                if(details) {
                                    let titleStr = '';
                                    details.records.forEach(r => {
                                        titleStr += `${r.type}: ${r.time} \n`;
                                    });
                                    cell.title = titleStr;
                                }
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

                    if (data.summary) {
                        document.getElementById('trans_total_days').textContent = data.summary.total_days || 0;
                        document.getElementById('trans_present_count').textContent = data.summary.present_count || 0;
                        document.getElementById('trans_absent_count').textContent = data.summary.absent_count || 0;
                        document.getElementById('trans_holiday_count').textContent = data.summary.holiday_count || 0;
                        document.getElementById('trans_attendance_percentage').textContent = `${data.summary.attendance_percentage || 0}%`;
                    }

                    if (typeof $ !== 'undefined') $('[title]').tooltip();
                } else {
                    const msg = document.createElement('div');
                    msg.style.cssText = 'grid-column:1/-1; text-align:center; padding:24px; color:#94a3b8; font-size:13px;';
                    msg.textContent = 'No transportation attendance records found for this month';
                    grid.appendChild(msg);

                    ['trans_total_days','trans_present_count','trans_absent_count','trans_holiday_count'].forEach(id => {
                        document.getElementById(id).textContent = '0';
                    });
                    document.getElementById('trans_attendance_percentage').textContent = '0%';
                }
            }
        });
    </script>