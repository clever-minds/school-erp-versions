@extends('layouts.master')

@section('title')
    {{ __('sent_notifications') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('sent_notifications') }} - {{ $teacher->first_name }} {{ $teacher->last_name }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('sent_notifications') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu">{{ __('date') }}</label>
                                <input type="date" name="date" id="filter_date" class="form-control">
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ $url }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="false" data-fixed-number="2"
                               data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-export-data-type='all'
                               data-query-params="notificationQueryParams" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="title" data-sortable="true">{{ __('title') }}</th>
                                <th scope="col" data-field="message" data-sortable="true">{{ __('message') }}</th>
                                <th scope="col" data-field="classes" data-sortable="false">{{ __('classes') }}</th>
                                <th scope="col" data-field="operate" data-sortable="false" data-events="userEvents" data-escape="false">{{ __('Action') }}</th>
                                <th scope="col" data-field="date" data-sortable="true">{{ __('date') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="usersModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ __('Students List') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="users_toolbar" class="d-flex">
                            <select id="sent_filter" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                <option value="1">{{ __('Sent') }}</option>
                                <option value="0">{{ __('Not Sent') }}</option>
                            </select>
                        </div>
                        <table class='table' id='users_table'
                               data-toggle="table"
                               data-pagination="true"
                               data-page-list="[5, 10, 20, 50]"
                               data-search="true"
                               data-toolbar="#users_toolbar"
                               data-show-columns="false"
                               data-show-refresh="false"
                               data-mobile-responsive="true"
                               data-sort-name="no" data-sort-order="asc"
                               data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="no" data-sortable="true">{{ __('No.') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('Student Name') }}</th>
                                    <th scope="col" data-field="class_section" data-sortable="true">{{ __('Class') }}</th>
                                    <th scope="col" data-field="sent_badge" data-sortable="true" data-escape="false">{{ __('Notification Sent') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function notificationQueryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                date: $('#filter_date').val(),
            };
        }

        $('#filter_date').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });

        window.userEvents = {
            'click .view-users': function (e, value, row, index) {
                $('#usersModal').modal('show');
                $('#users_table').bootstrapTable('showLoading');
                $.ajax({
                    url: "{{ url('timetable/teacher/sent-notifications') }}/" + row.id + "/users",
                    type: 'GET',
                    success: function (response) {
                        let data = [];
                        if (response.data && response.data.length > 0) {
                            response.data.forEach((student, i) => {
                                let badgeClass = student.sent ? 'badge-success' : 'badge-danger';
                                let badgeText = student.sent ? 'Yes' : 'No';
                                data.push({
                                    no: i + 1,
                                    name: student.name,
                                    class_section: student.class_section,
                                    sent_status: student.sent ? 1 : 0,
                                    sent_badge: `<label class="badge ${badgeClass}">${badgeText}</label>`
                                });
                            });
                        }
                        $('#users_table').bootstrapTable('load', data);
                        $('#users_table').bootstrapTable('hideLoading');
                        
                        $('#sent_filter').off('change').on('change', function() {
                            var val = $(this).val();
                            if (val === "") {
                                $('#users_table').bootstrapTable('filterBy', {});
                            } else {
                                $('#users_table').bootstrapTable('filterBy', { sent_status: parseInt(val) });
                            }
                        });
                        $('#sent_filter').val('');
                        $('#users_table').bootstrapTable('filterBy', {});
                    },
                    error: function () {
                        $('#users_table').bootstrapTable('hideLoading');
                        $('#users_table').bootstrapTable('load', []);
                    }
                });
            }
        };
    </script>
@endsection
