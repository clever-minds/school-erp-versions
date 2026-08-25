<div class="card shadow-sm border-0 tvr-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="tvr-card-title mb-0">{{ __('leave_details') }}</h5>
            
            <div class="tvr-month-filter">
                <select id="leave_month" class="form-control form-control-sm shadow-sm border-0 bg-light" style="font-weight: 600; color: #475569; width: auto; display: inline-block;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table mb-0 tvr-table">
                <thead>
                    <tr>
                        <th>{{ __('date') }}</th>
                        <th>{{ __('type') }}</th>
                        <th>{{ __('reason') }}</th>
                        <th>{{ __('status') }}</th>
                    </tr>
                </thead>

                <tbody id="leave_table_body">
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fa fa-spinner fa-spin mr-2"></i> {{ __('loading') }}...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        loadLeaveData();

        document.getElementById('leave_month').addEventListener('change', loadLeaveData);

        function loadLeaveData() {
            const month = document.getElementById('leave_month').value;
            const year = new Date().getFullYear();
            const teacherId = "{{ $teacher->id }}";

            document.getElementById('leave_table_body').innerHTML =
                '<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin mr-2"></i> Loading...</td></tr>';

            fetch(`{{ route('reports.teacher.leave.report') }}?teacher_id=${teacherId}&month=${month}&year=${year}`)
                .then(res => res.json())
                .then(data => renderLeaves(data))
                .catch(err => {
                    console.error(err);
                    document.getElementById('leave_table_body').innerHTML =
                        '<tr><td colspan="4" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle mr-2"></i> Failed to load</td></tr>';
                });
        }

        function renderLeaves(data) {
            const tbody = document.getElementById('leave_table_body');

            if (!data.success || data.leaves.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fa fa-file-o mb-2" style="font-size:24px; color:#cbd5e1; display:block;"></i> No Leave Records</td></tr>';
                return;
            }

            tbody.innerHTML = "";

            data.leaves.forEach(l => {
                let statusBadge = '';
                if (l.status == 1){
                    statusBadge = '<span class="badge" style="background:#e8fdf0; color:#10b981; font-weight:600; padding:4px 8px;">Approved</span>';
                } else if (l.status == 0){
                    statusBadge = '<span class="badge" style="background:#fef3c7; color:#f59e0b; font-weight:600; padding:4px 8px;">Pending</span>';
                } else {
                    statusBadge = '<span class="badge" style="background:#fee2e2; color:#ef4444; font-weight:600; padding:4px 8px;">Rejected</span>';
                }
                
                tbody.innerHTML += `
                <tr>
                    <td class="font-weight-bold text-dark"><i class="fa fa-calendar-o text-muted mr-2" style="font-size:11px;"></i> ${l.date_formatted}</td>
                    <td class="font-weight-medium">${l.type}</td>
                    <td class="text-muted">${l.reason ?? '-'}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
            });
        }
    });
</script>