@extends('layouts.master')

@section('title')
    {{ __('Activity logs') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Activity logs') }}
            </h3>
        </div>
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    {{ __('list') . ' ' . __('activity logs') }}
                </h4>

                <div class="row" id="toolbar">
                    <div class="form-group col-sm-12 col-md-4">
                        <label class="filter-menu">{{ __('Select School Database') }} <span class="text-danger">*</span></label>
                        <select name="filter_database" id="filter_database" class="form-control">
                            <option value="">{{ __('Select Database') }}</option>
                            @foreach ($databases as $db)
                                <option value="{{ $db->database_name }}">{{ $db->name ?? $db->database_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table"
                               data-url="{{ route('activity.logs.fetch') }}"
                               data-click-to-select="true"
                               data-side-pagination="server"
                               data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true"
                               data-toolbar="#toolbar"
                               data-show-columns="true"
                               data-show-refresh="true"
                               data-fixed-columns="false"
                               data-trim-on-search="false"
                               data-mobile-responsive="true"
                               data-sort-name="id"
                               data-sort-order="desc"
                               data-maintain-selected="true"
                               data-export-types="['pdf','json','xml','csv','txt','sql','doc','excel']"
                               data-show-export="true"
                               data-export-options='{ "fileName": "activity-logs-<?= date("d-m-y") ?>" ,"ignoreColumn": ["operate"]}'
                               data-query-params="activityLogsQueryParams"
                               data-check-on-init="true"
                               data-escape="true">
                            <thead>
                            <tr>
                                <th data-field="state" data-checkbox="true"></th>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-formatter="indexFormatter">{{ __('no.') }}</th>
                                <th scope="col" data-field="user_id">{{ __('User Id') }}</th>
                                <th scope="col" data-field="user_name">{{ __('User Name') }}</th>
                                <th scope="col" data-field="model_name">{{ __('Model') }}</th>
                                <th scope="col" data-field="action">{{ __('Action') }}</th>
                                <th scope="col" data-field="record_id">{{ __('Record ID') }}</th>
                                <th scope="col" data-field="changes" data-escape="false">{{ __('Changes') }}</th>
                                <th scope="col" data-field="created_at">{{ __('Date & Time') }}</th>

                                @canany(['activity-log-edit','activity-log-delete'])
                                    <th data-events="activityLogEvents" class="align-button text-center" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                @endcanany
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

@section('script')
<script>
    function activityLogsQueryParams(p) {
        return {
            database_name: $('#filter_database').val(),
            limit: p.limit,
            offset: p.offset,
            order: p.order,
            sort: p.sort,
            search: p.search,
            _token: "{{ csrf_token() }}"
        };
    }

 

    // (Optional) handle row actions if required
    window.activityLogEvents = {
        'click .view': function (e, value, row, index) {
            alert('View Log ID: ' + row.id);
        }
    };

    $(document).on('change', '#filter_database', function () {
        $('#table_list').bootstrapTable('refresh');
    });
       function indexFormatter(value, row, index) {
            const table = $('#table_list');

            const options = table.bootstrapTable('getOptions');
            const pageSize = options.pageSize;
            const pageNumber = options.pageNumber;

            return (pageSize * (pageNumber - 1)) + (index + 1);
        }
</script>
@endsection
