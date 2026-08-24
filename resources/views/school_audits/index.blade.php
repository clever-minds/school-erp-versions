@extends('layouts.master')

@section('title')
    {{ __('School Audits') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('School Audits') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('School Audits') }}
                            @can('school-audit-create')
                            <a href="{{ route('school-audits.compare') }}" class="btn btn-info btn-sm float-right ml-2"><i class="fa fa-exchange-alt"></i> {{ __('Compare Audits') }}</a>
                            <a href="{{ route('school-audits.create') }}" class="btn btn-theme btn-sm float-right">{{ __('create') . ' ' . __('School Audit') }}</a>
                            @endcan
                        </h4>

                        <ul class="nav nav-tabs mt-4" id="auditTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true" onclick="changeArchiveStatus('active')">{{ __('Active Audits') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="archived-tab" data-toggle="tab" href="#archived" role="tab" aria-controls="archived" aria-selected="false" onclick="changeArchiveStatus('archived')">{{ __('Archived Audits') }}</a>
                            </li>
                        </ul>

                        <div class="row mt-3">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('school-audits.index') }}"
                                       data-click-to-select="true" data-side-pagination="server"
                                       data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                       data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                                       data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2"
                                       data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                                       data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                       data-export-data-type='all' data-export-options='{ "fileName": "school-audits-list-<?= date('d-m-y') ?>" }'
                                       data-query-params="queryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"> {{ __('id') }} </th>
                                        <th scope="col" data-field="no"> {{ __('no.') }} </th>
                                        <th scope="col" data-field="school_name" data-sortable="false">{{ __('School') }} </th>
                                        <th scope="col" data-field="auditor_name" data-sortable="false">{{ __('Auditor') }} </th>
                                        <th scope="col" data-field="audit_type" data-sortable="true">{{ __('Type') }} </th>
                                        <th scope="col" data-field="audit_date" data-sortable="true">{{ __('Date') }} </th>
                                        <th scope="col" data-field="status_badge" data-escape="false">{{ __('Status') }} </th>
                                        <th data-events="schoolAuditEvents" data-width="150" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
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
@endsection

@section('script')
<script>
    let currentArchiveStatus = 'active';

    function changeArchiveStatus(status) {
        currentArchiveStatus = status;
        $('#table_list').bootstrapTable('refresh');
    }

    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search,
            archive_status: currentArchiveStatus
        };
    }
    
    window.schoolAuditEvents = {
        // Any custom JS events for table rows can go here
    };
</script>
@endsection
