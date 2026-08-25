@extends('layouts.master')
@section('title')
    {{ __('dashboard') }}
@endsection
@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-theme text-white mr-2">
                    <i class="fa fa-home"></i>
                </span> {{ __('dashboard') }}
            </h3>
        </div>
        {{-- School Dashboard --}}
        @if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher') || Auth::user()->school_id)
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    {{-- License expire message --}}
                    @if ($license_expire <= ($settings['current_plan_expiry_warning_days'] ?? 0) && $subscription)
                        <div class="alert alert-danger" role="alert">
                            {{ __('Kindly note that your license will expire on') }} <strong class="package-expire-date">
                                {{ date('F d, Y', strtotime($subscription->end_date)) }}. </strong>
                            {{ __('If you want to modify your upcoming plan or remove any add-ons, please ensure that these changes are made before your current license expires') }}.
                        </div>
                    @endif
                </div>
                <div class="col-sm-12 col-md-12">
                    @foreach ($previous_subscriptions as $subscription)
                        @if ($subscription->status == 3)
                            <div class="alert alert-danger" role="alert">
                                {{ __('Please make the necessary payment as your license has expired on') }} <strong
                                    class="package-expire-date"> {{ date('F d, Y', strtotime($subscription->end_date)) }}.
                                </strong>
                            </div>
                        @endif
                        @if ($subscription->status == 4)
                            <div class="alert alert-danger" role="alert">
                                {{ __('We apologize for inconvenience but your payment was not successful Please try to process the payment again') }}.
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-danger card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_teachers') }}<i
                                    class="mdi mdi-chart-line mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $teacher }}</h2>
                            {{-- <h6 class="card-text">Increased by 60%</h6> --}}
                        </div>
                    </div>
                </div>

                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-info card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_students') }}<i
                                    class="mdi mdi-bookmark-outline mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $student }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-success card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('Total Guardians') }}<i
                                    class="mdi mdi-diamond mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $parent }}</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">

                <div class="col-md-7 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body v-scroll">
                            <h4 class="card-title">{{ __('teacher') }}</h4>
                            @if (!empty($teachers))
                                @foreach ($teachers as $row)
                                    <div class="wrapper d-flex align-items-center py-2 border-bottom">
                                        <img class="img-sm rounded-circle" src="{{ $row->image }}" alt="profile">
                                        <div class="wrapper ml-3">
                                            <h6 class="ml-1 mb-1">{{ $row->first_name . ' ' . $row->last_name }}</h6>
                                            <small class="text-muted mb-0">&nbsp;{{ $row->teacher->qualification }}</small>
                                        </div>
                                        <div class="badge badge-pill badge-success ml-auto px-1 py-1">
                                            <i class="mdi mdi-check"></i>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-5 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('gender') }}</h4>
                            <canvas id="gender-ratio-chart"></canvas>
                            <div id="gender-ratio-chart-legend"
                                class="rounded-legend legend-vertical legend-bottom-left pt-4"></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('noticeboard') }}</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th> {{ __('no.') }}</th>
                                            <th> {{ __('title') }}</th>
                                            <th> {{ __('description') }}</th>
                                            <th> {{ __('date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($announcement))
                                            @foreach ($announcement as $key => $row)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $row->title }}</td>
                                                    <td>{{ $row->description }}</td>
                                                    <td>{{ $row->created_at }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- End School Dashboard --}}

        {{-- Super Admin Dashboard --}}
        @if (Auth::user()->hasRole('Super Admin') || !Auth::user()->school_id)
            <div class="row">
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-info card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_schools') }}<i
                                    class="mdi mdi-bookmark-outline mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['total_school'] }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-success card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('active_schools') }}<i
                                    class="mdi mdi-diamond mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['active_school'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-danger card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('deactive_schools') }}<i
                                    class="mdi mdi-chart-line mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['deactive_school'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list').' '.__('pending') }} {{ __('bills') }}
                            </h4>

                            <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ url('subscriptions/report/show',1) }}" data-click-to-select="true" data-side-pagination="server" data-pagination="false" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-query-params="subscriptionReportQueryParams" data-show-export="true"
                                   data-export-options='{"fileName": "pending-bill-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}'>
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
        @endif

    </div>
@endsection
@section('script')
    @if ($boys || $girls)
        <script>
            (function($) {
                'use strict';
                $(function() {
                    Chart.defaults.global.legend.labels.usePointStyle = true;
                    if ($("#gender-ratio-chart").length) {
                        let ctx = document.getElementById('gender-ratio-chart').getContext("2d")
                        let gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 181);
                        gradientStrokeBlue.addColorStop(0, 'rgba(54, 215, 232, 1)');
                        gradientStrokeBlue.addColorStop(1, 'rgba(177, 148, 250, 1)');
                        let gradientLegendBlue =
                            'linear-gradient(to right, rgba(54, 215, 232, 1), rgba(177, 148, 250, 1))';

                        let gradientStrokeRed = ctx.createLinearGradient(0, 0, 0, 50);
                        gradientStrokeRed.addColorStop(0, 'rgba(255, 191, 150, 1)');
                        gradientStrokeRed.addColorStop(1, 'rgba(254, 112, 150, 1)');
                        let gradientLegendRed =
                            'linear-gradient(to right, rgba(255, 191, 150, 1), rgba(254, 112, 150, 1))';
                        let trafficChartData = {
                            datasets: [{
                                data: [{{ $boys }}, {{ $girls }}],
                                backgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                hoverBackgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                borderColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                legendColor: [
                                    gradientLegendBlue,
                                    gradientLegendRed
                                ]
                            }],

                            // These labels appear in the legend and in the tooltips when hovering different arcs
                            labels: [
                                "{{ __('boys') }}",
                                "{{ __('girls') }}"
                            ]
                        };
                        let trafficChartOptions = {
                            responsive: true,
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            },
                            legend: false,
                            legendCallback: function(chart) {
                                let text = [];
                                text.push('<ul>');
                                for (let i = 0; i < trafficChartData.datasets[0].data.length; i++) {
                                    text.push('<li><span class="legend-dots" style="background:' +
                                        trafficChartData.datasets[0].legendColor[i] + '"></span>');
                                    if (trafficChartData.labels[i]) {
                                        text.push(trafficChartData.labels[i]);
                                    }
                                    text.push('<span class="float-right">' + trafficChartData.datasets[0]
                                        .data[i] + "%" + '</span>')
                                    text.push('</li>');
                                }
                                text.push('</ul>');
                                return text.join('');
                            }
                        };
                        let trafficChartCanvas = $("#gender-ratio-chart").get(0).getContext("2d");
                        let trafficChart = new Chart(trafficChartCanvas, {
                            type: 'doughnut',
                            data: trafficChartData,
                            options: trafficChartOptions
                        });
                        $("#gender-ratio-chart-legend").html(trafficChart.generateLegend());
                    }
                    if ($("#inline-datepicker").length) {
                        $('#inline-datepicker').datepicker({
                            enableOnReadonly: true,
                            todayHighlight: true,
                        });
                    }
                });
            })(jQuery);
        </script>
    @endif
@endsection
