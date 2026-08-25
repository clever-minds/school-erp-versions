@extends('layouts.master')

@section('title')
    {{ __('subscription') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/subscription-page.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('subscription') }}
            </h3>
        </div>

        @php
            $total_addon_charges = $active_package ? $active_package->addons->sum('price') : 0;
            $total_user_charges = 0;
            if ($active_package) {
                if ($active_package->package_type == 1) {
                    $total_user_charges = ($data['students'] * $active_package->student_charge) + ($data['staffs'] * $active_package->staff_charge);
                } else {
                    $total_user_charges = $active_package->charges;
                }
            }
            $monthly_bill = $total_user_charges + $total_addon_charges;
            
            $renewal_days = 0;
            $total_days = 0;
            $renewal_percentage = 0;
            if ($active_package) {
                $start_date = \Carbon\Carbon::parse($active_package->getRawOriginal('start_date'));
                $end_date = \Carbon\Carbon::parse($active_package->getRawOriginal('end_date'));
                $renewal_days = now()->startOfDay()->diffInDays($end_date, false) + 1;
                $total_days = $start_date->diffInDays($end_date);
                if ($total_days > 0) {
                    $renewal_percentage = max(0, min(100, ($renewal_days / $total_days) * 100));
                }
            }

            $student_percentage = 0;
            $staff_percentage = 0;
            if ($active_package && $active_package->package_type == 0) {
                $student_percentage = $active_package->no_of_students > 0 ? ($data['students'] / $active_package->no_of_students) * 100 : 0;
                $staff_percentage = $active_package->no_of_staffs > 0 ? ($data['staffs'] / $active_package->no_of_staffs) * 100 : 0;
                
                $student_percentage = min(100, $student_percentage);
                $staff_percentage = min(100, $staff_percentage);
            }
        @endphp

        {{-- Summary Cards --}}
        <div class="row mb-4">
            {{-- Monthly Bill --}}
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card h-100">
                    <div class="sub-card-body metric-card">
                        <div class="metric-header">
                            <div class="metric-info">
                                <h6>{{ __('bill_amount') }}</h6>
                                <div class="d-flex align-items-baseline">
                                    <h3>{{ $system_settings['currency_symbol'] }}{{ number_format($monthly_bill, 2) }}</h3>
                                </div>
                                <span class="subtext">
                                    {{ __('plan_expires') }}: {{ $active_package ? format_date($active_package->end_date) : 'N/A' }}
                                </span>
                            </div>
                            <div class="metric-icon icon-primary">
                                <i class="fa fa-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Students --}}
            @php
                $student_status = $student_percentage < 70 ? 'status-normal' : ($student_percentage < 90 ? 'status-warning' : 'status-critical');
            @endphp
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card h-100">
                    <div class="sub-card-body metric-card">
                        <div class="metric-header">
                            <div class="metric-info">
                                <h6>{{ __('students') }}</h6>
                                <div class="d-flex align-items-baseline">
                                    <h3>{{ $data['students'] }}</h3>
                                    <span class="ml-2 text-muted small" style="font-size: 0.7rem;">{{ __('billed_units') }}</span>
                                </div>
                                <span class="subtext">
                                    @if($active_package && $active_package->package_type == 1)
                                        {{ __('rate') }}: +{{ $system_settings['currency_symbol'] }}{{ $active_package->student_charge }} / {{ __('student') }}
                                    @else
                                        {{ number_format($student_percentage, 1) }}% {{ __('of_limit_used') }}
                                    @endif
                                </span>
                            </div>
                            @if($active_package && $active_package->package_type == 0)
                                <div class="circular-progress-wrapper {{ $student_status }}" style="--percentage: {{ $student_percentage }}">
                                    <div class="metric-icon">
                                        <div class="icon-inner-bg icon-green-bg">
                                            <i class="fa fa-graduation-cap"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="metric-icon icon-green">
                                    <i class="fa fa-graduation-cap"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Staff --}}
            @php
                $staff_status = $staff_percentage < 70 ? 'status-normal' : ($staff_percentage < 90 ? 'status-warning' : 'status-critical');
            @endphp
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card h-100">
                    <div class="sub-card-body metric-card">
                        <div class="metric-header">
                            <div class="metric-info">
                                <h6>{{ __('Staff') }}</h6>
                                <div class="d-flex align-items-baseline">
                                    <h3>{{ $data['staffs'] }}</h3>
                                    <span class="ml-2 text-muted small" style="font-size: 0.7rem;">{{ __('billed_units') }}</span>
                                </div>
                                <span class="subtext">
                                    @if($active_package && $active_package->package_type == 1)
                                        {{ __('rate') }}: +{{ $system_settings['currency_symbol'] }}{{ $active_package->staff_charge }} / {{ __('staff') }}
                                    @else
                                        {{ number_format($staff_percentage, 1) }}% {{ __('of_limit_used') }}
                                    @endif
                                </span>
                            </div>
                            @if($active_package && $active_package->package_type == 0)
                                <div class="circular-progress-wrapper {{ $staff_status }}" style="--percentage: {{ $staff_percentage }}">
                                    <div class="metric-icon">
                                        <div class="icon-inner-bg icon-blue-bg">
                                            <i class="fa fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="metric-icon icon-blue">
                                    <i class="fa fa-users"></i>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            {{-- Plan Renewal --}}
            @php
                $renewal_status = $renewal_percentage > 50 ? 'status-normal' : ($renewal_percentage > 20 ? 'status-warning' : 'status-critical');
            @endphp
            <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                <div class="card h-100">
                    <div class="sub-card-body metric-card">
                        <div class="metric-header">
                            <div class="metric-info">
                                <h6>{{ __('plan_renewal') }}</h6>
                                <div class="d-flex align-items-baseline">
                                    <h3>{{ max(0, $renewal_days) }} <small style="font-size: 0.9rem;">{{ __('days') }}</small></h3>
                                </div>
                                <span class="subtext">{{ __('exp') }}: {{ $active_package ? format_date($active_package->end_date) : 'N/A' }}</span>
                            </div>
                            @if($active_package)
                                <div class="circular-progress-wrapper {{ $renewal_status }}" style="--percentage: {{ $renewal_percentage }}">
                                    <div class="metric-icon">
                                        <div class="icon-inner-bg icon-orange-bg">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="metric-icon icon-orange">
                                    <i class="fa fa-calendar"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Main Column --}}
            <div class="col-lg-8 mb-4">
                {{-- Active Plan Card --}}
                <div class="card h-100">
                    <div class="sub-card-body">
                        @if ($active_package)
                            <div class="plan-header">
                                <div class="plan-title-wrapper">
                                    <div class="plan-shield-icon icon-primary">
                                        <i class="fa fa-shield"></i>
                                    </div>
                                    <div class="plan-title">
                                        <h4>{{ __('active_plan') }}: {{ $active_package->name }}</h4>
                                        <p>
                                            {{ __('your_subscription_is_active_and_billing') }} 
                                            @if($active_package->package_type == 1) {{ __('postpaid') }} @else {{ __('prepaid') }} @endif
                                        </p>
                                    </div>
                                </div>
                                @if (!empty($features))
                                    <a href="{{ route('subscriptions.index') }}" class="btn btn-theme btn-sm">
                                        {{ __('upgrade_plan') }} <i class="fa fa-external-link ml-1"></i>
                                    </a>
                                @endif
                            </div>

                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">
                                    {{ __('included_modules') }} ({{ $active_package->subscription_feature->count() }})
                                </h6>
                                <div class="module-grid">
                                    @foreach ($active_package->subscription_feature as $feature)
                                        <div class="module-item">
                                            <i class="fa fa-check-circle"></i>
                                            {{ __($feature->feature->name) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($active_package->addons->isNotEmpty())
                                <hr class="my-4">
                                <div>
                                    <h6 class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">
                                        {{ __('active_addons') }} ({{ $active_package->addons->count() }})
                                    </h6>
                                    <div class="addon-badges">
                                        @foreach ($active_package->addons as $addon)
                                            <div class="addon-badge">
                                                <i class="fa fa-plus-circle"></i>
                                                {{ __($addon->feature->name) }}
                                                @if ($addon->status)
                                                    <i class="fa fa-times text-danger ml-2 discontinue_addon cursor-pointer"
                                                       data-id="{{ $addon->id }}"
                                                       title="{{ __('discontinue_upcoming_plan') }}"></i>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <div class="metric-icon icon-primary mb-3 mx-auto" style="width: 64px; height: 64px; font-size: 2rem;">
                                    <i class="fa fa-info-circle"></i>
                                </div>
                                <h5>{{ __('no_active_plan_found') }}</h5>
                                <p class="text-muted">{{ __('please_subscribe_to_a_plan_to_get_started') }}.</p>
                                <a href="{{ route('subscriptions.index') }}" class="btn btn-theme">{{ __('browse_plans') }}</a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Sidebar Column --}}
            <div class="col-lg-4 mb-4">
                {{-- Upcoming Changes Card --}}
                <div class="card upcoming-card h-100">
                    @if ($upcoming_package && ($school_settings['auto_renewal_plan'] ?? 0))
                        <span class="planned-badge">{{ __('planned') }}</span>
                        <div class="sub-card-body d-flex flex-column h-100">
                            <h5 class="mb-1">{{ __('upcoming_changes') }}</h5>
                            <p class="text-muted small mb-3">{{ __('effective_from_the_next_billing_cycle') }}.</p>

                            <div class="new-plan-block">
                                <span>{{ __('new_plan') }}</span>
                                <strong>{{ $upcoming_package->name }}</strong>
                            </div>

                            <div class="mt-auto">
                                <p class="text-danger small mb-4">
                                    <i class="fa fa-info-circle mr-1"></i>
                                    {{ __('note_certain_additional_features_will_not_be_part_of_the_next_billing_period_as_they_have_already_been_integrated_into_your_upcoming_subscription_package') }}.
                                </p>
                            </div>

                            <div>
                                @if (!($upcoming_package->subscription_bill && $upcoming_package->subscription_bill->transaction && $upcoming_package->subscription_bill->transaction->payment_status == "succeed" && $upcoming_package->id != $active_package->id))
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="{{ route('subscriptions.index') }}" class="btn btn-dark w-100">
                                                {{ __('update_plan') }}
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-outline-danger w-100 cancel-upcoming-plan" @if ($active_package->id == $upcoming_package->id) data-id="" @else data-id="{{ $upcoming_package->id }}" @endif >
                                                {{ __('cancel_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="badge badge-success text-uppercase p-2 w-100">{{ __('paid_and_confirmed') }}</div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="sub-card-body text-center py-5 d-flex flex-column justify-content-center h-100">
                            <div class="metric-icon icon-orange mb-3 mx-auto">
                                <i class="fa fa-refresh"></i>
                            </div>
                            <h5>{{ __('upcoming_changes') }}</h5>
                            <p class="text-muted small">{{ __('no_planned_changes_for_the_next_cycle') }}.</p>
                            <hr class="my-4">
                            <p class="small text-muted mb-0">{{ __('want_to_switch_plans') }}</p>
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-link btn-sm font-weight-bold text-primary">{{ __('browse_options') }}</a>
                        </div>
                    @endif
                </div>

                {{-- Help/Support (Optional as per design but user said ignore, I'll keep it simple or remove if strictly ignoring) --}}
                {{-- User said "Ignore Support/Help section", so I will skip it --}}
            </div>
        </div>

        {{-- Full Width Billing Table --}}
        <div class="row">
            <div class="col-12">
                {{-- Billing Breakdown Card --}}
                <div class="card mb-4">
                    <div class="sub-card-body">
                        <h5 class="card-title mb-1">{{ __('active_subscription_billing_details') }}</h5>
                        <p class="text-muted small mb-4">{{ __('a_detailed_view_of_your_current_plan') }}.</p>
                        
                        @if ($active_package)
                            <div class="table-responsive">
                                <table class="table sub-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Item') }}</th>
                                            <th class="text-center">@if($active_package->package_type == 1) {{ __('Usage') }} @else {{ __('Usage / Limit') }} @endif</th>
                                            <th class="text-right">{{ __('Unit Price') }}</th>
                                            <th class="text-right">{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($active_package->package_type == 1)
                                            {{-- Postpaid Student Licenses --}}
                                            <tr>
                                                <td>
                                                    <strong>{{ __('student') }}</strong><br>
                                                    <small class="text-muted">{{ __('based_on_active_enrollments') }}</small>
                                                </td>
                                                <td class="text-center">{{ $data['students'] }} {{ __('students') }}</td>
                                                <td class="text-right">{{ $system_settings['currency_symbol'] }}{{ number_format($active_package->student_charge, 2) }}</td>
                                                <td class="text-right font-weight-bold">{{ $system_settings['currency_symbol'] }}{{ number_format($data['students'] * $active_package->student_charge, 2) }}</td>
                                            </tr>
                                            {{-- Postpaid Staff Licenses --}}
                                            <tr>
                                                <td>
                                                    <strong>{{ __('staff') }}</strong><br>
                                                    <small class="text-muted">{{ __('teachers_and_staffs') }}</small>
                                                </td>
                                                <td class="text-center">{{ $data['staffs'] }} {{ __('staff') }}</td>
                                                <td class="text-right">{{ $system_settings['currency_symbol'] }}{{ number_format($active_package->staff_charge, 2) }}</td>
                                                <td class="text-right font-weight-bold">{{ $system_settings['currency_symbol'] }}{{ number_format($data['staffs'] * $active_package->staff_charge, 2) }}</td>
                                            </tr>
                                        @else
                                            {{-- Prepaid Plan Charges --}}
                                            <tr>
                                                <td>
                                                    <strong>{{ __('plan_subscription') }}: {{ $active_package->name }}</strong><br>
                                                    <small class="text-muted">{{ __('plan_subscription_features') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="mb-1">{{ __('students') }}: {{ $data['students'] }} / {{ $active_package->no_of_students }}</div>
                                                    <div>{{ __('staff') }}: {{ $data['staffs'] }} / {{ $active_package->no_of_staffs }}</div>
                                                </td>
                                                <td class="text-right">{{ $system_settings['currency_symbol'] }}{{ number_format($active_package->charges, 2) }}</td>
                                                <td class="text-right font-weight-bold">{{ $system_settings['currency_symbol'] }}{{ number_format($active_package->charges, 2) }}</td>
                                            </tr>
                                        @endif

                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right font-weight-bold">
                                                @if($active_package->package_type == 1) {{ __('total_user_charges') }} : @else {{ __('package_amount') }} : @endif
                                            </td>
                                            <td class="text-right font-weight-bold">
                                                {{ $system_settings['currency_symbol'] }}{{ number_format($total_user_charges, 2) }}
                                            </td>
                                        </tr>

                                        {{-- Add-ons Section --}}
                                        @if($active_package->addons->isNotEmpty())
                                            <tr>
                                                <th colspan="4" class="text-center py-3">
                                                    <h6 class="mb-0 text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">{{ __('addon_charges') }}</h6>
                                                </th>
                                            </tr>
                                            @foreach ($active_package->addons as $addon)
                                                <tr>
                                                    <td>
                                                        <strong>{{ __('addon') }}: {{ __($addon->feature->name) }}</strong>
                                                    </td>
                                                    <td class="text-center">1 {{ __('instance') }}</td>
                                                    <td class="text-right">{{ $system_settings['currency_symbol'] }}{{ number_format($addon->price, 2) }}</td>
                                                    <td class="text-right font-weight-bold">{{ $system_settings['currency_symbol'] }}{{ number_format($addon->price, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td colspan="3" class="text-right font-weight-bold">{{ __('estimated_total_amount') }}:</td>
                                            <td class="text-right total-amount font-weight-bold">
                                                {{ $system_settings['currency_symbol'] }}{{ number_format($monthly_bill, 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <span class="text-muted">{{ __('no_active_subscription_billing_details_found') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing History --}}
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="sub-card-body">
                        <h4 class="card-title">{{ __('History') }}</h4>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('subscriptions.show', 1) }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="false" data-fixed-number="2"
                               data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-export-data-type='all' data-query-params="subscriptionQueryParams"
                               data-toolbar="#toolbar"
                               data-export-options='{ "fileName": "subscription-list-<?= date('d-m-y') ?>"
                            ,"ignoreColumn":["operate"]}' data-show-export="true" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="date"  data-sortable="false">{{ __('bill_generate_date') }}</th>
                                <th scope="col" data-field="due_date"  data-sortable="false">{{ __('due_date') }}</th>
                                <th scope="col" data-field="name" data-sortable="false">{{ __('name') }}</th>
                                <th scope="col" data-field="subscription.package_type" data-formatter="packageTypeFormatter" data-sortable="false">{{ __('type') }}</th>
                                <th scope="col" data-width="500" data-field="description" data-visible="false" data-sortable="false">{{ __('description') }}</th>
                                <th scope="col" data-field="transaction_id">{{ __('transaction_id') }}</th>
                                <th scope="col" data-field="total_student" data-sortable="false">{{ __('total_students') }}</th>
                                <th scope="col" data-field="total_staff"> {{ __('total_staffs') }}</th>
                                <th scope="col" data-field="amount">{{ __('Amount') }}</th>
                                <th scope="col" data-field="payment_status" data-formatter="transactionPaymentStatus">{{ __('status') }}</th>
                                <th scope="col" data-field="operate" data-events="subscriptionEvents" data-escape="false">{{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bill details Modal (Maintained for functionality) --}}
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-1 text-white" id="exampleModalLabel">{{ __('Subscription Bill Details') }}</h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="billing_cycle badge badge-outline-dark text-white"> </span> 
                                <span class="package-type badge badge-primary text-white"></span>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                                <i class="fa fa-close"></i>
                            </span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table modal-table mb-0">
                                {{-- Postpaid Section --}}
                                <tbody class="postpaid-package-info">
                                    <tr class="bg-light">
                                        <th colspan="4" class="py-3 px-4">{{ __('user_wise_billing_breakdown') }}</th>
                                    </tr>
                                    <tr>
                                        <th class="pl-4">{{ __('item') }}</th>
                                        <th class="text-right">{{ __('unit_rate') }}</th>
                                        <th class="text-center">{{ __('usage') }}</th>
                                        <th class="text-right pr-4">{{ __('total') }}</th>
                                    </tr>
                                    <tr>
                                        <td class="pl-4">
                                            <strong>{{ __('student') }}</strong><br>
                                            <small class="text-muted">{{ __('plan') }}: <span class="plan-name"></span></small>
                                        </td>
                                        <td class="text-right">{{ $system_settings['currency_symbol'] }}<span class="student-charge"></span></td>
                                        <td class="text-center"><span class="total-student"></span> {{ __('students') }}</td>
                                        <td class="text-right pr-4 font-weight-bold">{{ $system_settings['currency_symbol'] }}<span class="total-student-charge"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="pl-4">
                                            <strong>{{ __('staff') }}</strong><br>
                                            <small class="text-muted">{{ __('teachers_and_staffs') }}</small>
                                        </td>
                                        <td class="text-right">{{ $system_settings['currency_symbol'] }}<span class="staff-charge"></span></td>
                                        <td class="text-center"><span class="total-staff"></span> {{ __('staff') }}</td>
                                        <td class="text-right pr-4 font-weight-bold">{{ $system_settings['currency_symbol'] }}<span class="total-staff-charge"></span></td>
                                    </tr>
                                    <tr class="bg-light-primary">
                                        <th colspan="3" class="text-right pl-4 py-3">{{ __('total_user_charges') }} :</th>
                                        <th class="text-right pr-4 py-3 font-weight-bold" style="font-size: 1.1rem;">
                                            {{ $system_settings['currency_symbol'] }}<span class="total-user-charges"></span>
                                        </th>
                                    </tr>
                                </tbody>

                                {{-- Prepaid Section --}}
                                <tbody class="prepaid-package-info">
                                    <tr class="bg-light">
                                        <th colspan="4" class="py-3 px-4">{{ __('plan_usage_statistics') }}</th>
                                    </tr>
                                    <tr>
                                        <th class="pl-4">{{ __('resource') }}</th>
                                        <th class="text-center" colspan="2">{{ __('allocation (used / limit)') }}</th>
                                        <th class="text-right pr-4">{{ __('status') }}</th>
                                    </tr>
                                    <tr>
                                        <td class="pl-4"><strong>{{ __('student_capacity') }}</strong></td>
                                        <td class="text-center" colspan="2"><span class="prepaid-student-usage"></span></td>
                                        <td class="text-right pr-4"><span class="badge badge-success-outline">{{ __('included') }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="pl-4"><strong>{{ __('staff_capacity') }}</strong></td>
                                        <td class="text-center" colspan="2"><span class="prepaid-staff-usage"></span></td>
                                        <td class="text-right pr-4"><span class="badge badge-success-outline">{{ __('included') }}</span></td>
                                    </tr>
                                    <tr class="bg-light-primary">
                                        <th colspan="3" class="text-right pl-4 py-3">{{ __('package_amount') }} :</th>
                                        <th class="text-right pr-4 py-3 font-weight-bold" style="font-size: 1.1rem;">
                                            {{ $system_settings['currency_symbol'] }}<span class="package_amount"></span>
                                        </th>
                                    </tr>
                                </tbody>
                                
                                {{-- Addons Section --}}
                                <tbody class="addon-header">
                                    <tr class="bg-light">
                                        <th colspan="4" class="py-3 px-4">{{ __('addon_charges') }}</th>
                                    </tr>
                                    <tr class="postpaid-table">
                                        <th class="pl-4" colspan="3">{{ __('addon_name') }}</th>
                                        <th class="text-right pr-4">{{ __('amount') }} ({{ $system_settings['currency_symbol'] }})</th>
                                    </tr>
                                    <tr class="prepaid-table">
                                        <th class="pl-4">{{ __('addon_name') }}</th>
                                        <th class="text-center">{{ __('order_id') }}</th>
                                        <th class="text-center">{{ __('status') }}</th>
                                        <th class="text-right pr-4">{{ __('amount') }} ({{ $system_settings['currency_symbol'] }})</th>
                                    </tr>
                                </tbody>
                                <tbody class="postpaid-table postpaid-addon-charges"></tbody>
                                <tbody class="prepaid-table prepaid-addon-charges"></tbody>

                                {{-- Summary Totals --}}
                                <tbody>
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-right pl-4 py-3">{{ __('total_addon_charges') }} :</th>
                                        <th class="text-right pr-4 py-3 font-weight-bold">
                                            {{ $system_settings['currency_symbol'] }}<span class="total-addon-charges">0.00</span>
                                        </th>
                                    </tr>
                                    <tr class="bg-dark text-white">
                                        <th colspan="3" class="text-right pl-4 py-3 text-white" style="font-size: 1rem; border-bottom-left-radius: 8px;">{{ __('grand_total_users_addons') }} :</th>
                                        <th class="text-right pr-4 py-3 font-weight-bold text-white" style="font-size: 1.2rem; border-bottom-right-radius: 8px;">
                                            {{ $system_settings['currency_symbol'] }}<span class="grand-total">0.00</span>
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-light d-flex gap-2 justify-content-end py-3 text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                                @if ($paymentConfiguration && $paymentConfiguration->payment_method == 'Razorpay')
                                    <div class="payment-status">
                                        <form action="{{ url('subscriptions/razorpay') }}" class="razorpay-form" method="POST">
                                            @csrf
                                            <input type="hidden" name="package_id" class="package_id" value="">
                                            <input type="hidden" name="amount" class="bill_amount" value="">

                                            <input type="hidden" name="type" class="type" value="package">
                                            <input type="hidden" name="package_type" class="package_type" value="bill">

                                            <input type="hidden" name="razorpay_payment_id" class="razorpay_payment_id" value="">
                                            <input type="hidden" name="razorpay_signature" class="razorpay_signature" value="">
                                            <input type="hidden" name="razorpay_order_id" class="razorpay_order_id" value="">

                                            <input type="hidden" name="paymentTransactionId" class="paymentTransactionId" value="">

                                            <input type="hidden" name="subscription_id" class="subscription_id" value="">

                                            <button class="btn btn-theme" type="submit" id="razorpay-button">{{ __('Pay_with_Razorpay') }}</button>
                                        </form>
                                    </div>
                                @elseif ($paymentConfiguration && $paymentConfiguration->payment_method == 'Stripe')
                                    <form class="" action="{{ route('subscriptions.store') }}" novalidate="novalidate" data-stripe-publishable-key="{{ $settings['stripe_publishable_key'] ?? null }}" data-success-function="formSuccessFunction" method="post">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="stripe">
                                        <input type="hidden" name="id" id="edit_id">
                                        <input type="hidden" name="package_id" class="package_id" value="">
                                        <input class="btn btn-theme payment-status" type="submit" value={{ __('Pay_with_Stripe') }} />
                                    </form>
                                @elseif ($paymentConfiguration && $paymentConfiguration->payment_method == 'Paystack')
                                    <form class="" action="{{ route('subscriptions.store') }}" novalidate="novalidate" data-paystack-publishable-key="{{ $paymentConfiguration->api_key ?? null }}" data-success-function="formSuccessFunction" method="post">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="paystack">
                                        <input type="hidden" name="id" id="edit_id">
                                        <input type="hidden" name="package_id" class="package_id" value="">
                                        <input class="btn btn-theme payment-status" type="submit" value={{ __('Pay_with_Paystack') }} />
                                    </form>
                                @elseif ($paymentConfiguration && $paymentConfiguration->payment_method == 'Flutterwave')
                                    <form class="" action="{{ route('subscriptions.store') }}" novalidate="novalidate" data-flutterwave-publishable-key="{{ $paymentConfiguration->api_key ?? null }}" data-success-function="formSuccessFunction" method="post">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="flutterwave">
                                        <input type="hidden" name="id" id="edit_id">
                                        <input type="hidden" name="package_id" class="package_id" value="">
                                        <input class="btn btn-theme payment-status" type="submit" value={{ __('Pay_with_Flutterwave') }} />
                                    </form>
                                @endif
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
@endsection

@section('js')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="{{ asset('assets/js/subscription-page.js') }}"></script>
@endsection