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

        {{-- Super Admin Dashboard --}}
        <div class="row">
            {{-- Total Schools --}}
            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="dashboard-card-title mb-1">{{ __('total_schools') }}</p>
                                <h3 class="dashboard-card-value">{{ $super_admin['total_school'] }}</h3>
                                <p class="dashboard-card-insight mb-0 text-success">
                                    <i class="fa fa-arrow-trend-up"></i> +{{ $super_admin['new_schools_this_month'] }} {{ __('this_month') }}
                                </p>

                            </div>
                            <div class="card-icon-soft bg-soft-blue">
                                <i class="fa fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Plans --}}
            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="dashboard-card-title mb-1">{{ __('active_plans') }}</p>
                                <h3 class="dashboard-card-value">{{ $super_admin['active_plans_count'] }}</h3>
                                <p class="dashboard-card-insight mb-0 text-muted">
                                    {{ __('out_of') }} {{ $super_admin['total_school'] }} {{ __('schools') }}
                                </p>
                            </div>
                            <div class="card-icon-soft bg-soft-green">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Revenue --}}
            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="dashboard-card-title mb-1">{{ __('monthly_revenue') }}</p>
                                <h3 class="dashboard-card-value">{{ $settings['currency_symbol'] ?? '$' }}{{ number_format($super_admin['current_month_revenue']) }}</h3>
                                <p class="dashboard-card-insight mb-0 {{ $super_admin['revenue_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="fa {{ $super_admin['revenue_growth'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i> 
                                    {{ number_format(abs($super_admin['revenue_growth']), 2) }}% vs {{ __('last_month') }}
                                </p>

                            </div>
                            <div class="card-icon-soft bg-soft-purple">
                                <i class="fa fa-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Packages --}}
            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="dashboard-card-title mb-1">{{ __('active_packages') }}</p>
                                <h3 class="dashboard-card-value">{{ $super_admin['active_packages_count'] }}</h3>
                                <p class="dashboard-card-insight mb-0 text-muted">
                                    {{ $super_admin['total_addons_count'] }} {{ __('addons') }} {{ __('available') }}
                                </p>
                            </div>
                            <div class="card-icon-soft bg-soft-orange">
                                <i class="fa fa-archive"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">

            @if (($settings['wildcard_domain'] ?? 0) == 0 || ($settings['notification_settings'] ?? 0) == 0)
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title text-dark"><i class="fa fa-info-circle" aria-hidden="true"></i>
                                {{ __('server_configuration') }} - <span
                                    class="">{{ __('required_for_full_system_functionality') }}</span></h4>
                            <div class="list-wrapper">
                                <ul class="d-flex flex-column todo-list todo-list-custom">
                                    @foreach ($server_configuration as $key => $value)
                                        @if (($settings[$key] ?? 0) == 0)
                                            <li class="{{ $loop->index == 0 ? 'border-bottom' : '' }}">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="checkbox server-configuration-checkbox" id="{{ $key }}" value="1"
                                                            type="checkbox"> {{ $value['title'] }} <i class="input-helper"></i>
                                                        <a href="{{ $value['link'] }}" target="_blank" class="">
                                                            {{ __('view_setup') }}
                                                        </a>
                                                    </label>
                                                    <p class="text-muted text-wrap">{{ $value['description'] ?? '' }}</p>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-md-8 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <h4 class="card-title">
                                    {{ __('transaction') }}
                                </h4>
                            </div>
                            <div class="col-md-3">
                                {!! Form::selectRange('year', $start_year, date('Y'), date('Y'), [
                                    'class' => 'form-control form-control-sm year-filter',
                                ]) !!}
                            </div>
                        </div>

                        <div id="subscriptionTransactionChart">

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('trending_packages') }}
                        </h4>
                        @if (collect($package_graph)->filter()->isNotEmpty())
                            <div id="packageChart"> </div>
                        @else
                            <div class="text-center" style="padding-top: 40%;">
                                <span>{{ __('no_subscription_details_available') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Expiring Soon --}}
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">{{ __('expiring_soon') }}</h4>
                            <a href="{{ url('subscriptions/report') }}" class="btn btn-link btn-sm text-theme font-weight-bold">{{ __('view_all') }}</a>

                        </div>
                        
                        @if ($expiring_soon->isNotEmpty())
                            <div class="list-wrapper">
                                @foreach ($expiring_soon as $subscription)
                                    @php
                                        $remaining_days = Carbon\Carbon::now()->diffInDays(Carbon\Carbon::parse($subscription->getRawOriginal('end_date')), false) + 2;
                                        $badge_class = 'badge-light-secondary';
                                        $row_class = '';
                                        if ($remaining_days <= 7) {
                                            $badge_class = 'badge-danger';
                                            $row_class = 'bg-light-danger border-danger';
                                        } elseif ($remaining_days <= 15) {
                                            $badge_class = 'badge-warning';
                                            $row_class = 'bg-light-warning border-warning';
                                        }
                                    @endphp
                                    <div class="d-flex align-items-center p-2 mb-3 border-radius-10 {{ $row_class }}" style="border: 1px solid #f3f3f3;">
                                        <img src="{{ $subscription->school->logo }}" onerror="onErrorImage(event)" class="img-sm rounded-circle me-3" alt="logo">
                                        <div class="flex-grow-1 mx-2">
                                            <h6 class="mb-0 font-weight-bold">{{ $subscription->school->name }}</h6>
                                            <small class="text-muted">{{ $subscription->package->name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge {{ $badge_class }} mb-1">{{ $remaining_days }} {{ __('days_left') }}</span>
                                            {{-- @if($remaining_days <= 7)
                                                <br><a href="#" class="btn btn-link btn-xs p-0 text-theme">{{ __('send_reminder') }}</a>
                                            @endif --}}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <p class="text-muted mb-0">{{ __('no_records_found') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Schools --}}
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">{{ __('recent_schools') }}</h4>
                            <a href="{{ route('schools.index') }}" class="btn btn-link btn-sm text-theme font-weight-bold">{{ __('view_all') }}</a>
                        </div>
                        
                        @if ($recent_schools->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    {{-- <thead>
                                        <tr class="text-muted border-bottom">
                                            <th class="ps-0">{{ __('school_name') }}</th>
                                            <th class="text-center">{{ __('status') }}</th>
                                        </tr>
                                    </thead> --}}
                                    <tbody>
                                        @foreach ($recent_schools as $school)
                                            <tr class="border-bottom">
                                                <td class="ps-0 py-3">
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ $school->logo }}" onerror="onErrorImage(event)" class="img-sm rounded-circle me-3" alt="logo">
                                                        <div class="mx-2">
                                                            <h6 class="mb-0 font-weight-bold">{{ $school->name }}</h6>
                                                            <small class="text-muted">{{ __('joined') }} {{ Carbon\Carbon::parse($school->getRawOriginal('created_at'))->diffForHumans() }}</small>

                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center py-3">
                                                    @if ($school->user->email_verified_at)
                                                        <span class="badge badge-success">{{ __('verified') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('unverified') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <p class="text-muted mb-0">{{ __('no_school_added') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('addon') }}
                        </h4>
                        <div id="addonChart"> </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function () {
            $('.server-configuration-checkbox').change(function () {
                var checkbox = $(this);
                var value = checkbox.prop('checked') ? 1 : 0;
                var name = checkbox.attr('id');

                $.ajax({
                    url: "{{ route('server-configuration.update') }}",
                    type: 'POST',
                    data: {
                        name: name,
                        value: value,
                    },
                    success: function (response) {
                        showSuccessToast(response.message);
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });

            });
        });
    </script>

    <script>
        window.onload = setTimeout(() => {
            $('.year-filter').trigger('change');

            addon_graph(<?php echo json_encode($addon_graph[0]); ?>, <?php echo json_encode($addon_graph[1]); ?>);
            package_graph(<?php echo json_encode($package_graph[0]); ?>, <?php echo json_encode($package_graph[1]); ?>);
        }, 500);
    </script>
@endsection
