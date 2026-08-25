{{-- Student Exam Report Tab --}}
<div id="exam_reports_container">

    {{-- Loading --}}
    <div class="svr-loading" id="exam_loading">
        <div class="spinner-border text-theme" role="status">
            <span class="sr-only">{{ __('Loading') }}</span>
        </div>
        <p class="mt-2 mb-0">{{ __('Loading') }}</p>
    </div>

    {{-- No data --}}
    <div class="svr-no-data" id="no_exam_data" style="display:none;">
        <i class="fa fa-folder-open-o fa-2x mb-2 d-block"></i>
        {{ __('no_exam_results_available_for_this_student') }}
    </div>

    {{-- Results --}}
    <div id="exam_results_wrapper" style="display:none;"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadExamData();

        function loadExamData() {
            const sessionYearId = '{{ $session_year_id }}';
            const studentId = '{{ $student->user_id }}';

            document.getElementById('exam_loading').style.display = 'block';
            document.getElementById('no_exam_data').style.display = 'none';
            document.getElementById('exam_results_wrapper').style.display = 'none';

            fetch(`{{ route('reports.student.exam.report') }}?student_id=${studentId}&session_year_id=${sessionYearId}`)
                .then(response => response.json())
                .then(data => renderExamData(data))
                .catch(error => {
                    console.error('Error fetching exam data:', error);
                    document.getElementById('exam_loading').style.display = 'none';
                    const noData = document.getElementById('no_exam_data');
                    noData.style.display = 'block';
                    noData.textContent = 'Failed to load exam data. Please try again.';
                });
        }

        function renderExamData(data) {
            document.getElementById('exam_loading').style.display = 'none';
            const wrapper = document.getElementById('exam_results_wrapper');
            wrapper.innerHTML = '';

            if (data.success && data.exams && data.exams.length > 0) {
                const offlineExams = data.exams.filter(e => !e.exam_type || e.exam_type === 'Offline Exam');
                const onlineExams  = data.exams.filter(e => e.exam_type === 'Online Exam');

                offlineExams.forEach(exam => wrapper.appendChild(createOfflineExamSection(exam)));

                if (onlineExams.length > 0) {
                    wrapper.appendChild(createOnlineExamSection(onlineExams));
                }

                wrapper.style.display = 'block';
                if (typeof $().tooltip === 'function') $('[data-toggle="tooltip"]').tooltip();
            } else {
                const noData = document.getElementById('no_exam_data');
                noData.style.display = 'block';
                noData.textContent = "{{ __('No exam records found for the selected session year.') }}";
            }
        }

        /* ── Offline exam section ── */
        function createOfflineExamSection(exam) {
            const section = document.createElement('div');
            section.className = 'svr-exam-section';

            // Determine overall status chip
            const result = exam.summary ? exam.summary.result : 'Not Attempted';
            let chipClass = 'svr-status-chip--completed';
            if (result === 'Not Attempted') chipClass = 'svr-status-chip--not-attempted';
            else if (result === 'Fail') chipClass = 'svr-status-chip--fail';

            const statusLabel = result === 'Pass' ? 'COMPLETED'
                              : result === 'Not Attempted' ? 'NOT ATTEMPTED'
                              : result ? result.toUpperCase() : 'COMPLETED';

            section.innerHTML = `
                <div class="svr-exam-section__header">
                    <h6 class="svr-exam-section__name">${exam.name || exam.exam_title || '{{ __("Exam") }}'}</h6>
                    <span class="svr-status-chip ${chipClass}">${statusLabel}</span>
                </div>
                <div class="table-responsive">
                    <table class="svr-exam-table">
                        <thead>
                            <tr>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Max Marks') }}</th>
                                <th>{{ __('Obtained') }}</th>
                                <th>{{ __('Grade') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody id="exam_body_${exam.id || Date.now()}"></tbody>
                        <tfoot id="exam_foot_${exam.id || Date.now()}"></tfoot>
                    </table>
                </div>
            `;

            const tbody = section.querySelector('tbody');
            const tfoot = section.querySelector('tfoot');

            if (exam.subjects && exam.subjects.length > 0) {
                exam.subjects.forEach(sub => {
                    const tr = document.createElement('tr');
                    const passBadge = sub.is_pass
                        ? '<span class="svr-pass-badge svr-pass-badge--pass">{{ __("Pass") }}</span>'
                        : '<span class="svr-pass-badge svr-pass-badge--fail">{{ __("Fail") }}</span>';
                    tr.innerHTML = `
                        <td><strong>${sub.name}</strong>${sub.code ? ` <small class="text-muted">(${sub.code})</small>` : ''}</td>
                        <td>${sub.max_marks}</td>
                        <td>${sub.obtained_marks}</td>
                        <td>${sub.grade || '-'}</td>
                        <td>${passBadge}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else if (exam.summary && exam.summary.result === 'Not Attempted') {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">{{ __("This exam was not attempted by the student") }}</td></tr>';
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">{{ __("No subject data available") }}</td></tr>';
            }

            // Summary / total row
            if (exam.summary && exam.subjects && exam.subjects.length > 0) {
                const hasSummaryRow = exam.subjects.some(s => s.name === 'Overall Result' || s.code === 'ALL');
                if (!hasSummaryRow) {
                    const resultBadge = exam.summary.result === 'Pass'
                        ? `<span class="svr-pass-badge svr-pass-badge--pass">PASS (${Math.round(exam.summary.percentage || 0)}%)</span>`
                        : `<span class="svr-pass-badge svr-pass-badge--fail">${exam.summary.result} (${Math.round(exam.summary.percentage || 0)}%)</span>`;
                    tfoot.innerHTML = `
                        <tr>
                            <td>{{ __('Total') }}</td>
                            <td>${exam.summary.max_marks}</td>
                            <td>${exam.summary.obtained_marks}</td>
                            <td>-</td>
                            <td>${resultBadge}</td>
                        </tr>
                    `;
                }
            }

            return section;
        }

        /* ── Online exam section ── */
        function createOnlineExamSection(exams) {
            const section = document.createElement('div');
            section.className = 'svr-online-exam-section';
            section.innerHTML = `
                <div class="svr-online-exam-section__header">
                    <span class="svr-online-exam-label">{{ __('Online Exams') }}</span>
                </div>
                <div id="online_exam_list"></div>
            `;

            const list = section.querySelector('#online_exam_list');
            exams.forEach(exam => {
                const initial = (exam.subject_name || exam.exam_title || 'E').charAt(0).toUpperCase();
                const item = document.createElement('div');
                item.className = 'svr-online-exam-item';
                item.innerHTML = `
                    <div class="svr-online-exam-icon">${initial}</div>
                    <div class="svr-online-exam-info">
                        <div class="svr-online-exam-title">${exam.exam_title || exam.name || '-'}</div>
                        <div class="svr-online-exam-date">{{ __('Taken') }}: ${exam.created_at || '-'}</div>
                    </div>
                    <div class="svr-online-exam-result">
                        <div class="svr-online-exam-pct">${Math.round(exam.percentage || 0)}%</div>
                        <div class="svr-online-exam-grade">{{ __('Grade') }} ${exam.grade || '-'}</div>
                    </div>
                `;
                list.appendChild(item);
            });

            return section;
        }
    });
</script>