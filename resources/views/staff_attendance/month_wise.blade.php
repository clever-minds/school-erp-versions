@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage').' '.__('attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('view').' '.__('attendance') }}
                        </h4>
                        <div class="row mt-4">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu">{{ __('month') }} <span class="text-danger">*</span></label>
                                {!! Form::selectMonth('month',null,['class' => 'form-control','id' => 'month']) !!}
                            </div>
                            <div class="col-sm-12 col-md-9 d-flex align-items-center justify-content-end">
                                <div class="mt-3">
                                    <span class="badge badge-success mr-2 p-2">P : Present</span>
                                    <span class="badge badge-danger mr-2 p-2">A : Absent</span>
                                    <span class="badge badge-warning mr-2 p-2">H : Half Day</span>
                                    <span class="badge badge-info p-2">W : Work From Home</span>
                                </div>
                            </div>
                        </div>

                        <div id="toolbar" class="mb-3 d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center">
                                <span class="mr-3 font-weight-bold">Mark All Empty:</span>
                                <button type="button" class="btn btn-success btn-sm mr-2" id="global-mark-p">P (Present)</button>
                                <button type="button" class="btn btn-secondary btn-sm" id="global-uncheck">Uncheck</button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-theme btn-sm" id="save-all-attendance">
                                    <i class="fa fa-save mr-1"></i> Submit All Changes
                                </button>
                            </div>
                        </div>

                        <div class="show_attendance_student_list">
                            <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                   data-toggle="table"  data-click-to-select="true"
                                   data-pagination="false"
                                   data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="false" data-toolbar="#toolbar"
                                   data-show-columns="false" data-show-refresh="false" data-fixed-columns="true"
                                   data-fixed-number="1" data-fixed-right-number="1" data-trim-on-search="false"
                                   data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                   data-maintain-selected="true" data-export-data-type='all' data-show-export="false"
                                   data-export-options='{ "fileName": "view-attendance-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                   data-query-params="AttendanceReportqueryParams" data-escape="true">
                                <thead>
                                <tr>
                                    <th data-field="full_name">{{ __('staff_name') }}</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
<style>
    /* Reduce padding for table cells */
    .show_attendance_student_list .table td, 
    .show_attendance_student_list .table th {
        padding: 4px 5px !important;
        vertical-align: middle;
    }
</style>
@endsection

@section('script')


    <script>
        function showToastMessage(message, type) {
            var toast_position = $('html').attr('dir') === 'rtl' ? 'top-left' : 'top-right';
            if (type === 'success' || type === 'info') {
                if (typeof showSuccessToast === 'function') {
                    showSuccessToast(message);
                } else if (typeof $.toast === 'function') {
                    $.toast({ text: message, showHideTransition: 'slide', icon: 'success', loaderBg: '#f96868', position: toast_position });
                } else {
                    console.log("Success: " + message);
                }
            } else {
                if (typeof showErrorToast === 'function') {
                    showErrorToast(message);
                } else if (typeof $.toast === 'function') {
                    $.toast({ text: message, showHideTransition: 'slide', icon: 'error', loaderBg: '#f2a654', position: toast_position, hideAfter: 5000 });
                } else {
                    console.error("Error: " + message);
                }
            }
        }
        
        const monthSelect = document.getElementById('month');

        async function handleSelectChange() {
            var month = $('#month').val();
            var table = $('#table_list');
            if(month) {
                try {
                    table.bootstrapTable('showLoading');
                    const response = await fetch(`{{ url('staff-attendance/month-wise/list') }}?month=${month}`);
                    const data = await response.json();
                    
                    // Destroy and recreate table to properly load dynamic columns and data
                    table.bootstrapTable('destroy').bootstrapTable({
                        columns: [
                            {
                                field: 'full_name',
                                title: 'Staff Name'
                            },
                            {
                                field: 'action',
                                title: 'Action',
                                formatter: actionFormatter
                            },
                            ...generateDayColumns(month)
                        ],
                        data: data.rows,
                        pagination: false,
                        search: false,
                        showColumns: false,
                        showRefresh: false,
                        fixedColumns: true,
                        fixedNumber: 1,
                        mobileResponsive: true,
                        sortName: 'id',
                        sortOrder: 'desc',
                        maintainSelected: true,
                        exportDataType: 'all',
                        showExport: false,
                        escape: true
                    });
                } catch (error) {
                    console.error('Error fetching attendance data:', error);
                    table.bootstrapTable('hideLoading');
                }
            }
        }

        monthSelect.addEventListener('change', handleSelectChange);

        function generateDayColumns(month) {
            var currentYear = new Date().getFullYear();
            const daysInMonth = new Date(currentYear, month, 0).getDate(); // Month is zero-indexed, so no need to subtract 1
            const columns = [];

            for (let day = 1; day <= daysInMonth; day++) {
                columns.push({
                    field: `day_${day}`,
                    title: `${day}`,
                    formatter: attendanceFormatter
                });
            }

            return columns;
            
        }

        function actionFormatter(value, row, index) {
            let userId = row.user_id;
            return `
                <div class="d-flex justify-content-center align-items-center" style="min-width: 90px;">
                    <span class="badge badge-success row-mark-p mr-2" data-userid="${userId}" style="cursor: pointer; font-size: 13px; padding: 5px 8px;">P</span>
                    <span class="badge badge-secondary row-uncheck" data-userid="${userId}" style="cursor: pointer; font-size: 11px; padding: 5px 8px;">Uncheck</span>
                </div>
            `;
        }

    
        function attendanceFormatter(value, row, index, field) {
            let day = field.replace('day_', '');
            let userId = row.user_id;

            let pSelected = value === 'P' ? 'selected' : '';
            let aSelected = value === 'A' ? 'selected' : '';
            let wfhSelected = value === 'W' ? 'selected' : '';
            let hdSelected = value === 'H' ? 'selected' : '';

            let colorClass = 'text-secondary';
            if (value === 'P') colorClass = 'text-success';
            else if (value === 'A') colorClass = 'text-danger';
            else if (value === 'W') colorClass = 'text-info';
            else if (value === 'H') colorClass = 'text-warning';

            return `
                <select class="form-control form-control-sm ${colorClass} update-attendance font-weight-bold" style="width: auto; padding: 2px; height: auto; font-size: 13px; min-width: 45px;" data-userid="${userId}" data-day="${day}">
                    <option value="">-</option>
                    <option value="P" class="text-success" ${pSelected}>P</option>
                    <option value="A" class="text-danger" ${aSelected}>A</option>
                    <option value="H" class="text-warning" ${hdSelected}>H</option>
                    <option value="W" class="text-info" ${wfhSelected}>W</option>
                </select>
            `;
        }    

        $(document).on('change', '.update-attendance', function() {
            let val = $(this).val();
            let userId = $(this).data('userid');
            let day = $(this).data('day');
            let month = $('#month').val();
            let currentYear = new Date().getFullYear();
            
            // Format date as YYYY-MM-DD
            let date = `${currentYear}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            
            let status = '';
            let type = '';

            if (val === 'P') {
                status = 1; 
                type = '';
            } else if (val === 'A') {
                status = 4;
                type = '';
            } else if (val === 'H') {
                status = 3;
                type = '';
            } else if (val === 'W') {
                status = 1;
                type = 'Work From Home';
            }

            let selectElem = $(this);
            selectElem.removeClass('text-success text-danger text-info text-warning text-secondary');
            if (val === 'P') selectElem.addClass('text-success');
            else if (val === 'A') selectElem.addClass('text-danger');
            else if (val === 'H') selectElem.addClass('text-warning');
            else if (val === 'W') selectElem.addClass('text-info');
            else selectElem.addClass('text-secondary');

            // Mark as dirty to save on submit
            selectElem.addClass('is-dirty');
        });

        $(document).on('click', '#global-mark-p', function() {
            try {
                let type = 'P';
                let count = 0;

                $('.update-attendance').each(function() {
                    let currentVal = $(this).val();
                    if(!currentVal || currentVal === '' || currentVal === '-') {
                        $(this).val(type);
                        $(this).removeClass('text-success text-danger text-info text-warning text-secondary');
                        if(type === 'P') $(this).addClass('text-success');
                        else $(this).addClass('text-danger');

                        $(this).addClass('is-dirty checkbox-marked');
                        count++;
                    }
                });

                if(count === 0) {
                    if (typeof showToastMessage === 'function') {
                        showToastMessage('No empty slots found to update', 'warning');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof showToastMessage === 'function') {
                    showToastMessage('JS Error: ' + e.message, 'error');
                }
            }
        });

        $(document).on('click', '#global-uncheck', function() {
            $('.update-attendance.checkbox-marked').each(function() {
                if($(this).val() === 'P') {
                    $(this).prop('selectedIndex', 0);
                    $(this).removeClass('text-success text-danger text-info text-warning text-secondary is-dirty checkbox-marked');
                    $(this).addClass('text-secondary');
                }
            });
        });

        $(document).on('click', '.row-mark-p', function() {
            try {
                let type = 'P';
                let userId = $(this).data('userid');
                let count = 0;

                $(`.update-attendance[data-userid="${userId}"]`).each(function() {
                    let currentVal = $(this).val();
                    if(!currentVal || currentVal === '' || currentVal === '-') {
                        $(this).val(type);
                        $(this).removeClass('text-success text-danger text-info text-warning text-secondary');
                        if(type === 'P') $(this).addClass('text-success');
                        else $(this).addClass('text-danger');

                        $(this).addClass('is-dirty checkbox-marked');
                        count++;
                    }
                });

                if(count === 0) {
                    if (typeof showToastMessage === 'function') {
                        showToastMessage('No empty slots found to update', 'warning');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof showToastMessage === 'function') {
                    showToastMessage('JS Error: ' + e.message, 'error');
                }
            }
        });

        $(document).on('click', '.row-uncheck', function() {
            let userId = $(this).data('userid');
            $(`.update-attendance[data-userid="${userId}"].checkbox-marked`).each(function() {
                if($(this).val() === 'P') {
                    $(this).prop('selectedIndex', 0);
                    $(this).removeClass('text-success text-danger text-info text-warning text-secondary is-dirty checkbox-marked');
                    $(this).addClass('text-secondary');
                }
            });
        });

        $(document).on('click', '#save-all-attendance', function() {
            let updates = [];
            let month = $('#month').val();
            let currentYear = new Date().getFullYear();

            $('.update-attendance.is-dirty').each(function() {
                let val = $(this).val();
                if(val && val !== '-' && val !== '') {
                    let day = $(this).data('day');
                    let userId = $(this).data('userid');
                    let date = `${currentYear}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    
                    let status = 1;
                    let type = '';
                    if (val === 'A') status = 4;
                    else if (val === 'H') status = 3;
                    else if (val === 'W') { status = 1; type = 'Work From Home'; }

                    updates.push({
                        user_id: userId,
                        date: date,
                        status: status,
                        type: type
                    });
                }
            });

            if (updates.length > 0) {
                let btn = $(this);
                let originalText = btn.html();
                btn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');
                btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route("staff-attendance.month-wise-bulk-save") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        attendances: updates
                    },
                    success: function(response) {
                        btn.html(originalText);
                        btn.prop('disabled', false);

                        if (!response.error) {
                            $('.update-attendance.is-dirty').removeClass('is-dirty checkbox-marked');
                            if (typeof showToastMessage === 'function') {
                                showToastMessage(response.message || 'Successfully Saved', 'success');
                            }
                        } else {
                            if (typeof showToastMessage === 'function') {
                                showToastMessage(response.message, 'error');
                            }
                        }
                    },
                    error: function(xhr) {
                        btn.html(originalText);
                        btn.prop('disabled', false);
                        console.error('Bulk save error:', xhr);
                        if (typeof showToastMessage === 'function') {
                            showToastMessage('An error occurred during bulk save', 'error');
                        }
                    }
                });
            } else {
                if (typeof showToastMessage === 'function') {
                    showToastMessage('No changes to save', 'info');
                }
            }
        });

        $(document).ready(function() {
            if ($('#month').val()) {
                handleSelectChange();
            }
        });
    
    </script>

@endsection
