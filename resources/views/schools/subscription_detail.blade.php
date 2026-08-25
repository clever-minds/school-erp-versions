@extends('layouts.master')

@section('title')
    {{ __('subscription') }} {{ __('details') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/subscription-module.css') }}" />
@endsection

@section('content')
    <div class="content-wrapper">

        {{-- Page Header --}}
        <div class="page-header d-flex align-items-center mb-4">
            <a href="{{ url('subscriptions/report') }}" class="back-btn mr-3" style="font-size:1.2rem;color:#5144f8;">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h3 class="page-title mb-0">
                {{ $school->name }} &mdash; {{ __('subscription') }}
            </h3>
        </div>

        {{-- Hidden field for currency symbol (read by JS formatter) --}}
        <input type="hidden" id="currency-symbol" value="{{ $settings['currency_symbol'] ?? '' }}">

        <div class="row">

            {{-- ── Left Column: School Summary ── --}}
            <div class="col-md-4 col-lg-3">

                {{-- School Card --}}
                <div class="school-summary-card card card-body mb-4">
                    <div class="sub-avatar-large mb-3">
                        @if($school->logo)
                            <img src="{{ $school->logo }}" alt="{{ $school->name }}"
                                 style="width:100%;height:100%;object-fit:contain;border-radius:12px;">
                        @else
                            {{ substr($school->name, 0, 1) }}
                        @endif
                    </div>

                    <h5 class="mb-1 font-weight-bold">{{ $school->name }}</h5>
                    <p class="text-muted small mb-4">{{ $school->support_email }}</p>

                    {{-- Stats Row --}}
                    <div class="row mb-3">
                        <div class="col-6 text-center border-right">
                            <label class="d-block text-muted small mb-1">{{ __('status') }}</label>
                            @if($activeSubscription)
                                <span class="sub-badge badge sub-badge-success">{{ __('active') }}</span>
                            @else
                                <span class="sub-badge badge sub-badge-secondary">{{ __('inactive') }}</span>
                            @endif
                        </div>
                        <div class="col-6 text-center">
                            <label class="d-block text-muted small mb-1">{{ __('total_spent') }}</label>
                            <span class="font-weight-bold">
                                {{ $settings['currency_symbol'] }}{{ number_format($totalSpent, 2) }}
                            </span>
                        </div>
                    </div>

                    {{-- Plan badge row (Prepaid / Postpaid) --}}
                    @if($activeSubscription)
                        <div class="mb-3">
                            @if($activeSubscription->package_type == 0)
                                <span class="sub-badge badge sub-badge-plan">
                                    <i class="fa fa-bolt mr-1"></i>{{ __('prepaid') }}
                                </span>
                            @else
                                <span class="sub-badge badge sub-badge-plan">
                                    <i class="fa fa-clock mr-1"></i>{{ __('postpaid') }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if($activeSubscription)
                        <button class="action-button-full"
                                data-toggle="modal"
                                data-target="#update-current-plan"
                                onclick="$('#current_plan_id').val({{ $activeSubscription->id }});
                                         $('#update_package_id').val({{ $activeSubscription->package_id }});">
                            <i class="fa fa-arrow-up mr-2"></i>
                            {{ __('upgrade') }} / {{ __('update_plan') }}
                        </button>
                    @else
                        <button class="action-button-full" data-toggle="modal" data-target="#assign-plan-modal">
                            <i class="fa fa-plus-circle mr-2"></i>
                            {{ __('assign_plan') }}
                        </button>
                    @endif
                </div>

                {{-- School Details --}}
                <div class="sub-module-card card card-body mt-3">
                    <h6 class="mb-3 font-weight-bold">
                        <i class="fa fa-building mr-2"></i>{{ __('school_details') }}
                    </h6>
                    <ul class="school-details-list">
                        <li>
                            <label>{{ __('contact_person') }}</label>
                            <strong>
                                {{ $school->user
                                    ? $school->user->first_name . ' ' . $school->user->last_name
                                    : '-' }}
                            </strong>
                        </li>
                        <li>
                            <label>{{ __('phone_number') }}</label>
                            <strong>{{ $school->support_phone ?? '-' }}</strong>
                        </li>
                        <li>
                            <label>{{ __('address') }}</label>
                            <strong>{{ $school->address ?? '-' }}</strong>
                        </li>
                    </ul>
                </div>

            </div>{{-- /col left --}}

            {{-- ── Right Column: Active Plan + History ── --}}
            <div class="col-md-8 col-lg-9">

                {{-- Current Active Plan Card --}}
                @if($activeSubscription)
                    <div class="sub-gradient-primary sub-module-card d-flex justify-content-between align-items-start flex-wrap mb-4">
                        <div>
                            {{-- Label + Plan-type badge --}}
                            <div class="d-flex align-items-center mb-1" style="gap:8px;">
                                <p class="sub-metric-title mb-0" style="text-transform:uppercase;font-size:.75rem;opacity:.9;">
                                    {{ __('current_active_plan') }}
                                </p>
                                @if($activeSubscription->package_type == 0)
                                    <span class="sub-badge badge sub-badge-prepaid">{{ __('prepaid') }}</span>
                                @else
                                    <span class="sub-badge badge sub-badge-postpaid">{{ __('postpaid') }}</span>
                                @endif
                            </div>

                            <h2 class="sub-metric-value mb-2" style="color:#fff;">
                                {{ $activeSubscription->name }}
                            </h2>

                            <div class="d-flex flex-wrap" style="font-size:.85rem; gap:12px; opacity:.9;">
                                <div>
                                    <i class="fa fa-calendar rounded p-1 mr-1"></i>
                                    {{ __('next_bill') }}:
                                    <strong>{{ $activeSubscription->bill_date }}</strong>
                                </div>
                                {{-- <div>
                                    <i class="fa fa-repeat border rounded p-1 mr-1"></i>
                                    @php
                                        $cycle = $activeSubscription->billing_cycle ?? 0;
                                        $cycleLabel = $cycle >= 360 ? __('yearly_billing')
                                                    : ($cycle >= 28 ? __('monthly_billing')
                                                    : $cycle . ' ' . __('days'));
                                    @endphp
                                    {{ $cycleLabel }}
                                </div> --}}
                                @if($activeSubscription->subscription_bill)
                                    <div>
                                        <i class="fa fa-clock border rounded p-1 mr-1"></i>
                                        {{ __('last_paid') }}:
                                        <strong>{{ format_date($activeSubscription->subscription_bill->created_at) }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Billing Amount --}}
                        <div class="text-right mt-3 mt-md-0" style="position:relative;z-index:2;">
                            <div class="d-inline-block px-4 py-2"
                                 style="background:rgba(255,255,255,.15);border-radius:12px;backdrop-filter:blur(10px);">
                                <p class="mb-1 small" style="opacity:.8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">
                                    {{ __('billing_amount') }}
                                </p>
                                <h3 class="mb-0" style="color:#fff;">
                                    {{ $settings['currency_symbol'] }}{{ $activeSubscription->subscription_bill
                                        ? number_format($activeSubscription->subscription_bill->amount, 2)
                                        : '0.00' }}
                                </h3>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Subscription History Table --}}
                <div class="sub-module-card sub-table-wrapper card card-body">
                    <div class="history-header">
                        <h5 class="mb-0 font-weight-bold">
                            <i class="fa fa-history mr-2"></i>{{ __('subscription_history') }}
                        </h5>
                    </div>

                    <div class="table-responsive">
                        <table aria-describedby="subscription-history-desc" class="table mt-2" id="table_list" data-toggle="table" data-url="{{ url('subscriptions/report/school/' . $school->id . '/show') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20]" data-search="false" data-show-columns="false" data-show-refresh="false" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="start_date" data-sort-order="desc" data-maintain-selected="true" data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="invoice_id">{{ __('invoice_id') }}</th>
                                    <th scope="col" data-field="date">{{ __('date') }}</th>
                                    <th scope="col" data-field="plan">{{ __('plan') }}</th>
                                    <th scope="col" data-field="amount" data-formatter="amountFormatter">{{ __('amount') }}</th>
                                    <th scope="col" data-field="payment_method">{{ __('method') }}</th>
                                    <th scope="col" data-field="status" data-formatter="invoiceStatusFormatter">{{ __('status') }}</th>
                                    <th scope="col" data-field="operate" data-escape="false" data-formatter="operateFormatter" class="text-right">{{ __('actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>{{-- /col right --}}
        </div>{{-- /row --}}

        {{-- ═══════════ Modals ═══════════ --}}

        {{-- View Subscription Details Modal --}}
        <div class="modal fade" id="view-subscription-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content border-0" style="border-radius: 16px;">
                    {{-- Header --}}
                    <div class="modal-header d-flex align-items-center">
                        <div class="vs-header-icon">
                            <i class="fa fa-sliders"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold">{{ __('subscription_details') }}</h4>
                            <div class="vs-subtitle">{{ __('plan_management_configuration') ?? 'PLAN MANAGEMENT & CONFIGURATION' }}</div>
                        </div>
                        <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="{{ __('close') }}">
                            <span aria-hidden="true">
                                <i class="fa fa-close"></i>
                            </span>
                        </button>
                    </div>

                    <div class="modal-body p-0 position-relative">
                        {{-- Loader --}}
                        <div id="view-subscription-loader" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">{{ __('loading') }}</span>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div id="view-subscription-content" class="d-none">
                            {{-- Info Block --}}
                            <div class="vs-info-block">
                                <div class="row">
                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                        <div class="vs-info-label">{{ __('plan_name') }}</div>
                                        <div class="vs-info-value text-overflow-any">
                                            <span id="vs-plan-name" class="text-theme">-</span>
                                            <span class="vs-badge-active ml-1">{{ __('active') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                        <div class="vs-info-label">{{ __('billing_type') }}</div>
                                        <div class="vs-info-value">
                                            <i class="fa fa-credit-card text-muted mr-2 d-none d-sm-inline"></i>
                                            <span id="vs-billing-type">-</span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                        <div class="vs-info-label">{{ __('status') }}</div>
                                        <div class="vs-info-value">
                                            <span id="vs-status" class="badge badge-success">-</span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                        <div class="vs-info-label">{{ __('duration') }}</div>
                                        <div class="vs-info-value" style="font-size: 0.8rem;">
                                            <i class="fa fa-calendar-o text-muted mr-2 d-none d-sm-inline"></i>
                                            <div>
                                                <span id="vs-start-date">-</span> <span class="text-muted">|</span> <span id="vs-end-date">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Nav Tabs --}}
                            <ul class="nav nav-tabs vs-nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="features-tab" data-toggle="tab" href="#features" role="tab">
                                        {{ __('included_features') }} (<span id="included-features-count">0</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="addons-tab" data-toggle="tab" href="#addons" role="tab">
                                        {{ __('addons') }} (<span id="addons-count">0</span>)
                                    </a>
                                </li>
                            </ul>

                            {{-- Tab Panes --}}
                            <div class="tab-content vs-tab-content">
                                {{-- Features Tab --}}
                                <div class="tab-pane fade show active" id="features" role="tabpanel">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
                                        <div class="mb-3 mb-sm-0">
                                            <h5 class="vs-section-title">{{ __('core_plan_features') ?? 'Core Plan Features' }}</h5>
                                            <div class="vs-section-subtitle">{{ __('manage_active_features_subscription') ?? 'Manage the active features for this school\'s subscription.' }}</div>
                                        </div>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-theme" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-plus mr-1"></i> {{ __('add_feature') }}</button>
                                            <div class="dropdown-menu dropdown-menu-right vs-dropdown-menu">
                                                <div class="vs-dropdown-header">
                                                    <span class="vs-dropdown-title">{{ __('select_feature_to_add') }}</span>
                                                    <span class="text-muted" style="cursor:pointer;" onclick="$(this).closest('.dropdown-menu').removeClass('show');"><i class="fa fa-close"></i></span>
                                                </div>
                                                <div class="vs-search-wrapper">
                                                    <i class="fa fa-search"></i>
                                                    <input type="text" class="vs-search-input" id="search-feature-input" placeholder="{{ __('search_available_features') }}">
                                                </div>
                                                <div class="vs-dropdown-list" id="available-features-list">
                                                    <!-- Populated via JS -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- vs-feature-grid --}}
                                    <div class="" id="vs-features-grid">
                                        <!-- Features will be populated here -->
                                    </div>
                                </div>

                                {{-- Addons Tab --}}
                                <div class="tab-pane fade" id="addons" role="tabpanel">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
                                        <div class="mb-3 mb-sm-0">
                                            <h5 class="vs-section-title">{{ __('subscription_addons') }}</h5>
                                            <div class="vs-section-subtitle">{{ __('extra_modules_purchased') }}</div>
                                        </div>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-theme" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-plus mr-1"></i> {{ __('add_addon') }}</button>
                                            <div class="dropdown-menu dropdown-menu-right vs-dropdown-menu">
                                                <div class="vs-dropdown-header">
                                                    <span class="vs-dropdown-title">{{ __('select_addon_to_add') }}</span>
                                                    <span class="text-muted" style="cursor:pointer;" onclick="$(this).closest('.dropdown-menu').removeClass('show');"><i class="fa fa-close"></i></span>
                                                </div>
                                                <div class="vs-search-wrapper">
                                                    <i class="fa fa-search"></i>
                                                    <input type="text" class="vs-search-input" id="search-addon-input" placeholder="Search available addons...">
                                                </div>
                                                <div class="vs-dropdown-list" id="available-addons-list">
                                                    <!-- Populated via JS -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="" id="vs-addons-grid">
                                        <!-- Addons will be populated here -->
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div class="vs-footer-text">
                            <i class="fa fa-sliders mr-2"></i> {{ __('super_admin_console') }}
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Update Current Plan Modal --}}
        <div class="modal fade" id="update-current-plan" tabindex="-1" role="dialog"
             aria-labelledby="updateCurrentPlanLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateCurrentPlanLabel">{{ __('update_current_plan') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3 edit-form" method="post"
                          action="{{ route('subscription.update-current-plan') }}"
                          novalidate="novalidate">
                        <div class="modal-body">
                            <input type="hidden" name="id" id="current_plan_id" value="" />
                            <div class="form-group">
                                <label>{{ __('package') }} <span class="text-danger">*</span></label>
                                {!! Form::select('package_id', $packages, null, ['class' => 'form-control', 'id' => 'update_package_id']) !!}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ __('close') }}
                            </button>
                            <input class="btn btn-theme" type="submit" value="{{ __('submit') }}" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Assign New Plan Modal --}}
        <div class="modal fade" id="assign-plan-modal" tabindex="-1" role="dialog"
             aria-labelledby="assignPlanModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignPlanModalLabel">{{ __('assign_plan') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3 create-form" method="post" action="{{ route('subscriptions.assign-plan') }}" novalidate="novalidate">
                        <div class="modal-body">
                            <input type="hidden" name="school_id" value="{{ $school->id }}" />
                            <div class="form-group">
                                <label>{{ __('package') }} <span class="text-danger">*</span></label>
                                {!! Form::select('package_id', $packages, null, ['class' => 'form-control', 'required' => 'required']) !!}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ __('close') }}
                            </button>
                            <input class="btn btn-theme" type="submit" value="{{ __('submit') }}" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- /content-wrapper --}}
@endsection

@section('js')
    <script src="{{ asset('assets/js/subscription-detail.js') }}"></script>
@endsection
