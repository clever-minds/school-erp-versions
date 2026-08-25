@extends('layouts.master')

@section('title')
    {{ __('subscription') }} {{ __('directory') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/subscription-module.css') }}" />
    <style>
        .custom-dashboard-row { gap: 20px; margin-bottom: 30px; }
        .school-info-formatter { display: flex; align-items: center; gap: 15px; }
        .school-info-formatter .avatar { width: 40px; height: 40px; border-radius: 8px; background: #f0f0ff; color: #5144f8; display: flex; align-items: center; justify-content: center; font-weight: bold; overflow: hidden; }
        .school-info-formatter .avatar img { width: 100%; height: 100%; object-fit: contain; }
        .school-info-formatter .details h6 { margin: 0; font-weight: 600; color: #343a40; font-size: 0.95rem; }
        .school-info-formatter .details small { color: #6c757d; }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title mb-0">
                {{ __('subscription') }} {{ __('directory') }}
            </h3>
        </div>

        <div class="row">
            <!-- Total Schools / Active -->
            <div class="col-sm-12 col-md-4 mb-4">
                <div class="sub-module-card h-100 mb-0 card">
                    <p class="sub-metric-title">{{ __('total_schools') }}</p>
                    <h2 class="sub-metric-value">{{ $data['registration'] }}</h2>
                    <p class="sub-metric-subtitle"><i class="fa fa-line-chart mr-1"></i> {{ $data['active'] }} {{ __('active') }} {{ __('schools') }}</p>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="col-sm-12 col-md-4 mb-4">
                <div class="sub-module-card h-100 mb-0 card">
                    <p class="sub-metric-title">{{ __('active_subscriptions') }}</p>
                    <h2 class="sub-metric-value text-success">{{ $data['registration'] - $data['deactivate'] }}</h2>
                    <p class="sub-metric-subtitle text-muted">{{ __('based_on_current_active_plans') }}</p>
                </div>
            </div>

            <!-- Overdue/Unpaid -->
            <div class="col-sm-12 col-md-4 mb-4">
                <div class="sub-module-card h-100 mb-0 card">
                    <p class="sub-metric-title">{{ __('pending_issues') }}</p>
                    <h2 class="sub-metric-value text-danger">{{ $data['over_due'] + $data['unpaid'] }}</h2>
                    <p class="sub-metric-subtitle text-danger"><i class="fa fa-exclamation-circle mr-1"></i> {{ $data['over_due'] }} {{ __('over_due') }} / {{ $data['unpaid'] }} {{ __('unpaid') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="sub-module-card sub-table-wrapper card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 font-weight-bold">{{ __('schools_directory') }}</h5>
                        <div id="toolbar" class="row">
                            <div class="ml-1 col-sm-12 col-md-4">
                                <label for="filter_board_id" class="filter-menu">{{ __('board') }}</label>
                                {{ Form::select('board_id', $boards, null, ['class' => 'form-control', 'id' => 'filter_board_id', 'placeholder' => __('all')]) }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table aria-describedby="mydesc" class='table mt-2' id='table_list' data-toggle="table"
                            data-url="{{ url('subscriptions/report/schools/show') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[10, 20, 50]"
                            data-search="true" data-toolbar="#toolbar" data-show-columns="false" data-show-refresh="false"
                            data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                            data-sort-order="desc" data-maintain-selected="true" data-escape="true" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="no">{{ __('no') }}.</th>
                                    <th scope="col" data-field="school_name" data-formatter="SchoolNameFormatter">{{ __('school') }}</th>
                                    <th scope="col" data-field="current_plan" data-formatter="currentPlanFormatter">{{ __('current_plan') }}</th>
                                    <th scope="col" data-field="type" data-formatter="packageTypeHistoryFormatter">{{ __('type') }} </th>
                                    <th scope="col" data-field="next_billing">{{ __('next_billing') }} </th>
                                    <th scope="col" data-field="operate" class="text-center" data-escape="false">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')
    <script>
        

        function currentPlanFormatter(value, row) {
            if (value === 'None') {
                return '<span class="sub-badge badge sub-badge-secondary">' + value + '</span>';
            }
            return '<span class="sub-badge badge sub-badge-plan">' + value + '</span>';
        }

        function schoolStatusFormatter(value, row) {
            // 1 => Current Cycle, 2 => Paid, 3 => Over Due, 4 => Failed, 5 => Pending, 6 => Next Billing Cycle, 7 => Unpaid
            if (value === 'Current Cycle' || value === 'Paid') {
                return '<span class="sub-badge sub-badge-success">' + window.trans["active"] + '</span>';
            } else if (value === 'Over Due' || value === 'Failed' || value === 'Pending' || value === 'Unpaid') {
                return '<span class="sub-badge sub-badge-danger">' + window.trans["over_due"] + '</span>';
            } else if (value === 'Inactive' || value === 'Bill Not Generated') {
                return '<span class="sub-badge sub-badge-secondary">' + window.trans["inactive"] + '</span>';
            }
            return '<span class="sub-badge sub-badge-success">' + value + '</span>';
        }
    </script>
@endsection