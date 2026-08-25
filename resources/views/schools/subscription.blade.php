@extends('layouts.master')

@section('title')
    {{ __('subscription') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('subscription') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-success"></div>
                        <p class="text-muted mb-2">{{ __('registration') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['registration'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-success"></div>
                        <p class="text-muted mb-2">{{ __('active') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['active'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-danger"></div>
                        <p class="text-muted mb-2">{{ __('deactivate') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['deactivate'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-danger"></div>
                        <p class="text-muted mb-2">{{ __('over_due') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['over_due'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-danger"></div>
                        <p class="text-muted mb-2">{{ __('unpaid') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['unpaid'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 grid-margin stretch-card">
                <div class="card aligner-wrapper">
                    <div class="custom-card-body">
                        <div class="absolute left top bottom h-100 v-strock-2 bg-success"></div>
                        <p class="text-muted mb-2">{{ __('paid') }}</p>
                        <div class="d-flex align-items-center">
                            <h3 class="font-weight-medium mb-2">{{ $data['paid'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('subscription') }}
                        </h4>
                        <div id="toolbar" class="row">
                            <div class="col-sm-12 col-md-4">
                                <label class="filter-menu">{{ __('status') }}</label>
                                {!! Form::select('status', ['0' => __('all'),'1' => __('current_cycle'), '2' => __('paid'), '3' => __('over_due'), '4' => __('failed'), '5' => __('pending'), '6' => __('next_billing_cycle'), '7' => __('unpaid')], null, ['class' => 'form-control', 'id' => 'status']) !!}
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ url('subscriptions/report/show') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="start_date" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{ "fileName": "{{ __('online') . ' ' . __('exam') }}-<?= date(' d-m-y') ?> " ,"ignoreColumn":["operate"]}' data-show-export="true" data-query-params="subscriptionReportQueryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false"> {{ __('id') }}</th>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="logo" data-formatter="imageFormatter"> {{ __('logo') }}</th>
                                    <th scope="col" data-field="school_name">{{ __('school_name') }}</th>
                                    <th scope="col" data-field="plan">{{ __('plan') }}</th>
                                    <th scope="col" data-field="billing_cycle">{{ __('billing_cycle') }} </th>
                                    <th scope="col" data-field="bill_date">{{ __('bill_date') }} </th>
                                    <th scope="col" data-field="amount">{{ __('bill_amount') }} ({{ $settings['currency_symbol'] }})</th>
                                    <th scope="col" data-field="status" class="text-center" data-formatter="subscriptionStatusFormatter">{{ __('status') }} </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

