<div class="card shadow-sm border-0 tvr-card">
    <div class="card-body p-4">
        <h5 class="tvr-card-title mb-4">{{ __('timetable') }}</h5>
        
        <div id="calendar-wrapper" class="table-responsive">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<style>
    /* ── Timetable Adjustments ───────────────────────── */
    #calendar-wrapper {
        width: 100%;
        max-height: fit-content;    
        overflow-y: auto;
        overflow-x: auto;
    }

    #calendar {
        min-width: 900px;
    }
    
    /* Modernize FullCalendar internals if possible (mostly handled by JS/native CSS, just light tweaks) */
    .fc-theme-standard th {
        background-color: #f8fafc !important;
        border-color: #eef0f4 !important;
        padding: 12px 0 !important;
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        text-transform: uppercase;
        border-top: none !important;
        border-right: none !important;
        border-left: none !important;
        border-bottom: 2px solid #eef0f4 !important;
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: #f1f5f9 !important;
    }
    .fc-timegrid-slot {
        height: 60px !important; /* Make slots taller for breathing room */
    }
    .fc-v-event {
        background-color: #e8fdf0 !important;
        border: 1px solid #10b981 !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
        padding: 4px !important;
    }
    .fc-event-main {
        color: #1e293b !important;
        font-weight: 600 !important;
    }
    .fc-event-time {
        color: #64748b !important;
        font-weight: 500 !important;
        font-size: 11px !important;
    }
</style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // Load events
            @foreach($timetables as $timetable)
                teacherTimetable.addEvent({
                    title: "{{ $timetable->title }}",
                    daysOfWeek: [days.indexOf("{{ $timetable->day }}")],
                    startTime: "{{ $timetable->start_time }}",
                    endTime: "{{ $timetable->end_time }}",
                    color: "{{ $timetable->subject->bg_color ?? 'Black' }}",
                    id: "{{ $timetable->id }}",
                    class_section: "{{ $timetable->class_section->full_name }}"
                });
            @endforeach

            // Apply slot options once
            teacherTimetable.setOption("slotMinTime", "{{ $timetableSettingsData['timetable_start_time'] ?? '00:00:00' }}");
            teacherTimetable.setOption("slotMaxTime", "{{ $timetableSettingsData['timetable_end_time'] ?? '23:59:00' }}");
            teacherTimetable.setOption("slotDuration", "{{ $timetableSettingsData['timetable_duration'] ?? '00:30:00' }}");
        });

        // IMPORTANT: Fix Responsive Calendar in Bootstrap Tabs
        $('a[data-toggle="tab"][href="#timetable"]').on('shown.bs.tab', function () {

            setTimeout(function () {

                if (teacherTimetable) {

                    // destroy → FULL CLEAN RESET
                    teacherTimetable.destroy();

                    // re-render calendar properly
                    teacherTimetable.render();

                    // force size recalculation → MOST IMPORTANT for responsiveness
                    teacherTimetable.updateSize();
                }

            }, 50); // small delay ensures tab DOM is visible
        });
</script>
    