@extends('layouts.master')
@section('title')
    {{ __('dashboard') }}
@endsection
@section('content')

    <style>
        .truncateTitle {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-theme text-white mr-2">
                    <i class="fa fa-home"></i>
                </span> {{ __('dashboard') }}
            </h3>
        </div>
        {{-- School Dashboard --}}
        <div class="row">
            {{-- License expire message --}}
            @if (Auth::user()->hasRole('School Admin'))
                @if ($license_expire <= ($settings['current_plan_expiry_warning_days'] ?? 7) && $subscription)
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm license-alert-card">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    {{-- Left Content --}}
                                    <div class="col-lg-8 col-md-12 mb-4 mb-lg-0">
                                        <div class="d-flex flex-column flex-sm-row align-items-center">
                                            {{-- Warning Icon --}}
                                            <div class="mb-3 mb-sm-0 mr-sm-4 flex-shrink-0 license-alert-icon-box">
                                                <i class="fa fa-exclamation-triangle license-alert-icon"></i>
                                            </div>
                                            
                                            {{-- Text Content --}}
                                            <div>
                                                <div class="d-flex align-items-center mb-2 flex-wrap">
                                                    <h4 class="font-weight-bold mb-0 mr-2 license-alert-title">
                                                        {{ __('license_expiring_soon') }}
                                                    </h4>
                                                    <span class="badge badge-pill mt-1 mt-sm-0 badge badge-warning">
                                                        {{ $license_expire }} {{ strtoupper(__('days_left')) }}
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-4 license-alert-text">
                                                    {{ __('your_current_license_expires_on') }} <strong class="license-alert-text-strong">{{ date('F d, Y', strtotime($subscription->end_date)) }} - 11:59 PM</strong>. 
                                                    {{ __('to_ensure_uninterrupted_service_and_accurate_billing_for_your_next_plan_please_complete_the_following_steps') }}
                                                </p>
                                                
                                                <div class="row position-relative">
                                                    {{-- Step 1 --}}
                                                    <div class="col-md-6 mb-3 mb-md-0">
                                                        <a href="{{ url('users/status') }}" class="text-decoration-none d-block p-2 rounded license-alert-step-link">
                                                            <div class="d-flex align-items-start">
                                                                <i class="fa fa-users mt-1 mr-3 license-alert-step-icon-users"></i>
                                                                <div>
                                                                    <h6 class="font-weight-bold mb-1 license-alert-step-title">1. {{ __('review_user_counts') }}</h6>
                                                                    <p class="text-muted mb-1 license-alert-step-desc">{{ __('activate_deactivate_users_update_plan') }}</p>
                                                                    <span class="font-weight-bold license-alert-step-action-users">{{ __('click_to_manage_users') }} <i class="fa fa-arrow-right ml-1"></i></span>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                    
                                                    {{-- Vertical Divider (Only shows on md+ screens and if Step 2 exists) --}}
                                                    @if ($prepiad_upcoming_plan && $prepiad_upcoming_plan->package->type == 0 && !$check_payment)
                                                        <div class="d-none d-md-block license-alert-divider"></div>
                                                    @endif

                                                    {{-- Step 2 (Conditional) --}}
                                                    @if ($prepiad_upcoming_plan && $prepiad_upcoming_plan->package->type == 0 && !$check_payment)
                                                        <div class="col-md-6">
                                                            @if ($paymentConfiguration && $paymentConfiguration->payment_method == 'Stripe')
                                                                <a href="{{ url('subscriptions/pay-prepaid-upcoming-plan', $prepiad_upcoming_plan->package_id) . '/type/' . $prepiad_upcoming_plan_type . '/subscription/' . $prepiad_upcoming_plan->id }}" class="text-decoration-none d-block p-2 rounded license-alert-step-link">
                                                            @else
                                                                <a href="javascript:void(0)" onclick="document.getElementById('razorpay-button').click();" class="text-decoration-none d-block p-2 rounded license-alert-step-link">
                                                            @endif
                                                                <div class="d-flex align-items-start">
                                                                    <i class="fa fa-credit-card mt-1 mr-3 license-alert-step-icon-payment"></i>
                                                                    <div>
                                                                        <h6 class="font-weight-bold mb-1 license-alert-step-title">2. {{ __('settle_payment') }}</h6>
                                                                        <p class="text-muted mb-1 license-alert-step-desc">{{ __('secure_upcoming_prepaid_plan_prevent_interruption') }}</p>
                                                                        <span class="font-weight-bold license-alert-step-action-payment">{{ __('click_to_process_payment') }} <i class="fa fa-arrow-right ml-1"></i></span>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- <div class="d-none d-md-block license-alert-divider"></div> --}}
                                    
                                    {{-- Right Action Buttons --}}
                                    <div class="col-lg-4 col-md-12 mt-3 mt-lg-0 d-flex flex-column justify-content-center">
                                        <hr class="d-lg-none border-light mb-4">
                                        <div class="px-lg-4 w-100 mx-auto license-alert-btn-container">
                                            <a href="{{ url('users/status') }}" class="btn btn-block mb-3 font-weight-bold d-flex align-items-center justify-content-center shadow-sm license-alert-btn-secondary">
                                                <i class="fa fa-users mr-2"></i>{{ __('adjust_active_users') }}
                                            </a>
                                            
                                            @if ($prepiad_upcoming_plan && $prepiad_upcoming_plan->package->type == 0 && !$check_payment)
                                                @if ($paymentConfiguration && $paymentConfiguration->payment_method == 'Stripe')
                                                    <a href="{{ url('subscriptions/pay-prepaid-upcoming-plan', $prepiad_upcoming_plan->package_id) . '/type/' . $prepiad_upcoming_plan_type . '/subscription/' . $prepiad_upcoming_plan->id }}" class="btn btn-primary btn-block font-weight-bold shadow-sm d-flex align-items-center justify-content-center">
                                                        <i class="fa fa-credit-card mr-2"></i>{{ __('pay_and_renew_now') }}
                                                    </a>
                                                @else
                                                    <form action="{{ url('subscriptions/razorpay') }}" class="razorpay-form w-100" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="package_id" class="package_id" value="{{ $prepiad_upcoming_plan->package_id }}">
                                                        <input type="hidden" name="amount" class="bill_amount" value="{{ $prepiad_upcoming_plan->charges }}">
                                                        <input type="hidden" name="type" class="type" value="package">
                                                        <input type="hidden" name="package_type" class="package_type" value="upcoming">
                                                        <input type="hidden" name="razorpay_payment_id" class="razorpay_payment_id" value="">
                                                        <input type="hidden" name="razorpay_signature" class="razorpay_signature" value="">
                                                        <input type="hidden" name="razorpay_order_id" class="razorpay_order_id" value="">
                                                        <input type="hidden" name="paymentTransactionId" class="paymentTransactionId" value="">
                                                        <input type="hidden" name="subscription_id" class="subscription_id" value="{{ $prepiad_upcoming_plan->id }}">
                                                        <input type="hidden" name="upcoming_plan_type" value="{{ $prepiad_upcoming_plan_type }}">
                                                        
                                                        <button type="button" id="razorpay-button" class="btn btn-primary btn-block font-weight-bold shadow-sm d-flex align-items-center justify-content-center">
                                                            <i class="fa fa-credit-card mr-2"></i>{{ __('pay_and_renew_now') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-sm-12 col-md-12">
                    @foreach ($previous_subscriptions as $prev_subscription)
                        @if ($prev_subscription->status == 'Over Due')
                            <div class="alert alert-danger shadow-sm" style="border-radius: 8px; border-left: 4px solid #e74a3b;" role="alert">
                                <i class="fa fa-exclamation-circle mr-2"></i>
                                {{ __('Please make the necessary payment as your license has expired on') }} <strong class="package-expire-date">{{ date('F d, Y', strtotime($prev_subscription->end_date)) }}</strong>.
                            </div>
                            @break
                        @endif
                        @if ($prev_subscription->status == 'Failed' || $prev_subscription->status == 'Pending')
                            <div class="alert alert-danger shadow-sm" style="border-radius: 8px; border-left: 4px solid #e74a3b;" role="alert">
                                <i class="fa fa-times-circle mr-2"></i>
                                {{ __('We apologize for inconvenience but your payment was not successful Please try to process the payment again') }}.
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        @if (Auth::user()->hasRole('School Admin'))
            <div class="row">
                {{-- Teachers --}}
                <div class="col-md-2-4 stretch-card grid-margin">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="d-flex flex-row flex-wrap">
                                <div class="ms-3">
                                    {{ __('total_teachers') }}
                                    <p class="text-muted">
                                    <h3>{{ $teacher }}</h3>
                                    </p>
                                    <p class="mt-2 text-success font-weight-bold"> </p>
                                </div>
                                <img class="ml-auto" src="{{ url('images/teachers.svg') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Students --}}
                <div class="col-md-2-4 stretch-card grid-margin">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="d-flex flex-row flex-wrap">
                                <div class="ms-3">
                                    {{ __('total_students') }}
                                    <p class="text-muted">
                                    <h3>{{ $student }}</h3>
                                    </p>
                                    <p class="mt-2 text-success font-weight-bold"> </p>
                                </div>
                                <img class="ml-auto" src="{{ url('images/students.svg') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Guardians --}}
                <div class="col-md-2-4 stretch-card grid-margin">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="d-flex flex-row flex-wrap">
                                <div class="ms-3">
                                    {{ __('Total Guardians') }}
                                    <p class="text-muted">
                                    <h3>{{ $parent }}</h3>
                                    </p>
                                    <p class="mt-2 text-success font-weight-bold"> </p>
                                </div>
                                <img class="ml-auto" src="{{ url('images/guardians.svg') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Class --}}
                <div class="col-md-2-4 stretch-card grid-margin">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="d-flex flex-row flex-wrap">
                                <div class="ms-3">
                                    {{ __('total_classes') }}
                                    <p class="text-muted">
                                    <h3>{{ $classes_counter }}</h3>
                                    </p>
                                    <p class="mt-2 text-success font-weight-bold"> </p>
                                </div>
                                <img class="ml-auto" src="{{ url('images/classes.svg') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Stream --}}
                <div class="col-md-2-4 stretch-card grid-margin">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="d-flex flex-row flex-wrap">
                                <div class="ms-3">
                                    {{ __('total_streams') }}
                                    <p class="text-muted">
                                    <h3>{{ $streams }}</h3>
                                    </p>
                                    <p class="mt-2 text-success font-weight-bold"> </p>
                                </div>
                                <img class="ml-auto" src="{{ url('images/stream.svg') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- End Counter --}}

        <div class="row">

            {{-- Expense Graph --}}
            @if (Auth::user()->canany(['expense-create', 'expense-list']))
                <div class="col-md-8 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <h4 class="card-title">{{ __('expense') }}</h4>
                                </div>
                                <div class="col-md-3 text-right">
                                    <select name="session_year_id" id="filter_expense_session_year_id"
                                        class="form-control form-control-sm">
                                        @foreach ($sessionYears as $session)
                                            @if ($session->default == 1)
                                                <option value="{{ $session->id }}" selected>{{ $session->name }}</option>
                                            @else
                                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                                            @endif

                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @hasNotFeature('Expense Management')
                            <div class="align-items-center d-flex flex-column justify-content-center v-scroll text-small">
                                {{ __('Purchase') . ' ' . __('Expense Management') . ' ' . __('to Continue using this functionality') }}
                            </div>
                            @endHasNotFeature

                            @hasFeature('Expense Management')
                            <div class="chartjs-wrapper mt-2" style="height: 330px">
                                <div id="expenseChart" style="direction: ltr;"> </div>
                            </div>
                            @endHasFeature

                        </div>
                    </div>
                </div>
            @endif

            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="card-title">{{ __('leaves') }}</h3>
                            </div>
                            <div class="col-md-6 dropdown text-right">
                                {!! Form::select(
                                    'leave_filter',
                                    ['Today' => __('today'), 'Tomorrow' => __('tomorrow'), 'Upcoming' => __('upcoming')],
                                    'today',
                                    ['class' => 'form-control form-control-sm filter_leaves'],
                                ) !!}
                            </div>
                        </div>

                        <div class="v-scroll mt-2">
                            <table class="table custom-table">
                                @hasNotFeature('Staff Leave Management')
                                <tbody class="leave-list">
                                    <tr>
                                        <td colspan="2" class="text-center text-small">
                                            {{ __('Purchase') . ' ' . __('Staff Leave Management') . ' ' . __('to Continue using this functionality') }}
                                        </td>
                                    </tr>
                                </tbody>
                                @endHasNotFeature

                                @hasFeature('Staff Leave Management')
                                <tbody class="leave-list">

                                </tbody>
                                @endHasFeature


                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <h3 class="card-title">{{ __('attendance') }}</h3>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                {!! Form::select('class_id', count($class_names) > 0 ? $class_names : ['' => 'Not Data Available'], null, ['class' => 'form-control form-control-sm class-section-attendance',]) !!}
                            </div>
                        </div>
                        <div id="attendanChart">

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">{{ __('announcement') }}</h4>
                        <div class="table-responsive v-scroll">
                            <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                data-url="{{ route('announcement.show', 1) }}" data-side-pagination="server"
                                data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                data-sort-order="desc" data-maintain-selected="true" data-escape="true">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no">{{ __('no.') }}</th>
                                        <th scope="col" data-field="title" class="truncateTitle">{{ __('title') }}</th>
                                        <th scope="col" data-events="tableDescriptionEvents"
                                            data-formatter="descriptionFormatter" data-field="description">
                                            {{ __('description') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <h3 class="card-title">
                                    {{ __('exam_result') }}
                                </h3>
                            </div>
                            <div class="col-md-3">
                                <select name="session_year_id" id="exam_result_session_year_id"
                                    class="form-control form-control-sm">
                                    @foreach ($sessionYears as $session)
                                        @if ($session->default == 1)
                                            <option value="{{ $session->id }}" selected>{{ $session->name }}</option>
                                        @else
                                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="exam_name" id="exam_reuslt_exam_name" class="form-control form-control-sm">
                                    <option value="">{{ __('select') . ' ' . __('exam') }}</option>
                                    @foreach ($exams as $exam)
                                        <option value="{{ $exam->name }}" data-session-year="{{ $exam->session_year_id }}">
                                            {{ $exam->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-1 mb-3 v-scroll">
                            <div class="exam-report" id="class-progress-report">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <h3 class="card-title">
                                    {{ __('fees_over_due') }} <span class="ml-2"><i class="fa fa-exclamation-circle"
                                            title="{{ __('inactivate_student_by_submitting_records') }}"></i></span>
                                </h3>
                            </div>
                            <div class="col-sm-12 col-md-5">
                                {!! Form::select('class_section_id', $class_section_names, null, [
                                    'class' => 'form-control form-control-sm fees-over-due-class',
                                    'id' => 'fees-over-due-class-section'
                                ]) !!}
                            </div>
                        </div>
                        <form method="POST" id="fees-overdue-form" class="fees-overdue-form"
                            action="{{ route('deactivate-student-account') }}">
                            <div class="mt-1 mb-3 v-scroll">
                                <table class="table custom-table">
                                    @hasNotFeature('Fees Management')
                                    <tbody class="leave-list">
                                        <tr>
                                            <td colspan="2" class="text-center text-small">
                                                {{ __('Purchase') . ' ' . __('Fees Management') . ' ' . __('to Continue using this functionality') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    @endHasNotFeature

                                    @hasFeature('Fees Management')
                                    <tbody class="fees-over-due-list">
                                    </tbody>
                                    @endHasNotFeature
                                </table>
                            </div>
                            @hasFeature('Fees Management')
                            <input type="submit" class="btn btn-success float-right fees-overdue-btn" name="submit"
                                value="{{ __('submit') }}">
                            @endHasFeature
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h3 class="card-title">
                            {{ __('fees_details') }}
                        </h3>

                        <div id="fees_details_chart">

                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="card-title">{{ __('birthday') }}</h3>
                            </div>
                            <div class="col-md-6 text-right">
                                {!! Form::select(
                                    'birthday_filter',
                                    ['today' => __('today'), 'this_month' => __('this_month'), 'next_month' => __('next_month')],
                                    'today',
                                    ['class' => 'form-control form-control-sm filter_birthday'],
                                ) !!}
                            </div>
                        </div>
                        <div class="v-scroll mt-2">
                            <table class="table custom-table">
                                <tbody class="birthday-list">

                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>

            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">{{ __('holiday') }}</h4>
                        <div class="v-scroll dashboard-description">
                            <table class="table custom-table">
                                @hasNotFeature('Holiday Management')
                                <tbody class="leave-list">
                                    <tr>
                                        <td colspan="2" class="text-center text-small">
                                            {{ __('Purchase') . ' ' . __('Holiday Management') . ' ' . __('to Continue using this functionality') }}
                                        </td>
                                    </tr>
                                </tbody>
                                @endHasNotFeature

                                @hasFeature('Holiday Management')
                                <tbody>
                                    @forelse ($holiday as $holiday)
                                        <tr>
                                            <td>{{ $holiday->title }}</td>
                                            <td><span
                                                    class="float-right text-muted">{{ \Carbon\Carbon::createFromFormat($originalDateFormat, $holiday->date)->format('d - M') }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-small">
                                                {{ __('No Data Available') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @endHasNotFeature
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">{{ __('student_gender') }}</h4>
                        @if (($boys === 0 && $girls === 0 && $total_students === 0) || ($boys === null && $girls === null && $total_students === null))
                            <div class="col-md-12 text-center bg-light p-2 mb-2">
                                <span>{{ __('no_student_found') }}.</span>
                            </div>
                        @else
                            <div id="gender-ratio-chart"></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        window.onload = setTimeout(() => {
            $('#filter_expense_session_year_id').trigger('change');
            $('.filter_birthday').trigger('change');
            $('.filter_leaves').trigger('change');
            $('#exam_result_session_year_id').trigger('change');

            const selectElement = document.getElementById('exam_reuslt_exam_name');
            if (selectElement) {
                var selectedIndex = selectElement.selectedIndex || 0;
                var options = selectElement.options;

                // Iterate through options starting from the next index
                for (var i = selectedIndex + 1; i < options.length; i++) {
                    if (options[i].style.display !== "none") {
                        // Set the next visible option as selected
                        selectElement.selectedIndex = i;
                        break;
                    }
                }
            }


            $('#exam_reuslt_exam_name').trigger('change');
            fees_details(<?php echo json_encode($fees_detail); ?>);

            $('.class-section-attendance').trigger('change');
            $('#fees-over-due-class-section').trigger('change');

        }, 500);
    </script>

    @if ($boys || $girls)
        <script>
            gender_ratio(<?php echo $boys; ?>, <?php echo $girls; ?>, <?php echo $total_students; ?>);
        </script>
    @endif
@endsection
