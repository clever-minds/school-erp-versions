@extends('layouts.master')

@section('title')
    {{ __('Teacher Interviews') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interviews') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Applications List') }}
                        </h4>

                        <div class="row mt-3">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('teacher-interviews.index') }}"
                                       data-click-to-select="true" data-side-pagination="server"
                                       data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                       data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                                       data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2"
                                       data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                                       data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                       data-export-data-type='all' data-export-options='{ "fileName": "teacher-interviews-list-<?= date('d-m-y') ?>" }'
                                       data-query-params="queryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"> {{ __('id') }} </th>
                                        <th scope="col" data-field="no"> {{ __('no.') }} </th>
                                        <th scope="col" data-field="school_name" data-sortable="false">{{ __('School') }} </th>
                                        <th scope="col" data-field="name" data-sortable="true">{{ __('Name') }} </th>
                                        <th scope="col" data-field="email" data-sortable="true">{{ __('Email') }} </th>
                                        <th scope="col" data-field="phone" data-sortable="true">{{ __('Phone') }} </th>
                                        <th scope="col" data-field="applied_on" data-sortable="true">{{ __('Applied On') }} </th>
                                        <th scope="col" data-field="status_badge" data-escape="false">{{ __('Status') }} </th>
                                        <th data-events="teacherInterviewEvents" data-width="150" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Interviewer Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Interviewer') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="assignForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ __('Select Interviewer') }} <span class="text-danger">*</span></label>
                            <select name="interviewer_id" id="interviewer_id" class="form-control" required>
                                <option value="">{{ __('Select Interviewer') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary theme-btn">{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
    
    window.teacherInterviewEvents = {
        'click .assign-btn': function (e, value, row, index) {
            let applicationId = row.id;
            let schoolId = row.school_id;
            
            var formAction = "{{ url('teacher-interviews') }}/" + applicationId + "/assign";
            $('#assignForm').attr('action', formAction);
            
            let fetchUrl = "{{ url('get-staff-by-school') }}/" + (schoolId ? schoolId : 0) + "?role=HR";
            $.get(fetchUrl, function(data) {
                let options = '<option value="">{{ __("Select Interviewer") }}</option>';
                data.forEach(function(staff) {
                    options += `<option value="${staff.id}">${staff.first_name} ${staff.last_name}</option>`;
                });
                $('#interviewer_id').html(options);
                $('#assignModal').modal('show');
            }).fail(function() {
                alert('{{ __("Failed to fetch staff members.") }}');
            });
        }
    };
</script>
@endsection
