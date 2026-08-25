@extends('layouts.master')

@section('title')
    {{ __('Pay Compulsory Fees') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Pay Compulsory Fees') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12">
                <form class="create-form form-validation" method="post" action="{{ route('fees.compulsory.store') }}"
                    data-success-function="successFunction" novalidate="novalidate">
                    <input type="hidden" name="fees_id" id="compulsory-fees-id" value="{{ $fees->id }}" />
                    <input type="hidden" name="student_id" id="student-id" value="{{ $student->id }}" />
                    <input type="hidden" name="parent_id" id="parent-id" value="{{ $student->student->guardian_id }}" />
                    <input type="hidden" name="total_amount" id="total-amount"
                        value="{{ $fees->total_compulsory_fees }}" />
                    <input type="hidden" id="remaining_amount" value="{{ $fees->remaining_amount }}">
                    <input type="hidden" name="due_charges_amount" value="{{ $due_charges }}">
                    <input type="hidden" name="oneInstallmentPaid" class="oneInstallmentPaid" value="{{ $oneInstallmentPaid }}">

                    {{-- Zone 1: Student & Fee Summary --}}
                    @php
                        // Calculate due charges based on installments or full payment

                        // $total_fee = $fees->total_compulsory_fees + $due_charges;
                        $total_fee = $fees->total_compulsory_fees;
                        $total_paid = 0;
                        $paid_due = 0;
                        if ($student->fees_paid && count($student->fees_paid->compulsory_fee)) {
                            $total_paid = $student->fees_paid->compulsory_fee->sum('amount');
                            $paid_due = $fees->complusory_details->sum('due_charges');
                        }
                        $percentage = $total_fee > 0 ? round(($total_paid / $total_fee) * 100) : 0;
                        
                        if ($fees->include_fee_installments) {
                            $global_balance = $fees->installments->sum('balance_due');
                        } else {
                            // Non-Installment Mode
                            // TotalPaid is Base Only (confirmed from Controller fees_paid->amount)
                            
                            $remaining_base = $total_fee - $total_paid;
                            
                            // Remaining Due Logic:
                            // The $due_charges variable passed from controller represents Total Due Charges if applicable.
                            $remaining_due = max(0, $due_charges - $paid_due);
                            
                            $global_balance = $remaining_base + $remaining_due;
                        }

                    @endphp

                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6 border-right">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-3 mr-3 text-center"
                                            style="width: 60px; height: 60px;">
                                            <i class="fa fa-user fa-2x"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 font-weight-bold">{{ $student->full_name }}</h4>
                                            <p class="text-muted mb-0">
                                                {{ $student->student->class_section->full_name }} | {{ date('d-m-Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 pl-md-4">
                                    <h5 class="text-muted mb-1"># {{ $fees->name }}</h5>
                                    <h2 class="font-weight-bold text-dark mb-2">
                                        {{ number_format($total_fee, 2) . ' ' . $currencySymbol }}</h2>
                                        {{-- @if ($due_charges)
                                            <small class="text-danger">+ {{ $due_charges }}</small>
                                        @endif --}}

                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span>{{ __('paid') }}:
                                            {{ number_format($total_paid, 2) . ' ' . $currencySymbol }}</span>
                                        <span>{{ $percentage }}%</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>

                                    {{-- Fee Breakdown Toggle --}}
                                    <a class="theme-color small font-weight-bold" data-toggle="collapse"
                                        href="#feeBreakdown" role="button" aria-expanded="false"
                                        aria-controls="feeBreakdown">
                                        {{ __('view_fees_breakdown') }} <i class="fa fa-chevron-down"></i>
                                    </a>
                                </div>
                            </div>

                            {{-- Collapsible Breakdown --}}
                            <div class="collapse mt-3" id="feeBreakdown">
                                <div class="card card-body bg-light border-0 p-3">
                                    <h6 class="font-weight-bold text-muted mb-3">{{ __('fee_details') }}</h6>
                                    <ul class="list-group list-group-flush bg-transparent">
                                        @foreach ($fees->compulsory_fees as $c_fee)
                                            <li
                                                class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                                                <span>{{ $c_fee->fees_type_name }}</span>
                                                <span
                                                    class="font-weight-bold">{{ number_format($c_fee->amount, 2) . ' ' . $currencySymbol }}</span>
                                            </li>
                                        @endforeach
                                        {{-- @if ($due_charges > 0)
                                            <li
                                                class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2 text-danger">
                                                <span>{{ __('due_charges') }}</span>
                                                <span
                                                    class="font-weight-bold">{{ number_format($due_charges, 2) . ' ' . $currencySymbol }}</span>
                                            </li>
                                        @endif --}}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Non-Installment Fee Summary Card --}}
                    @if (!$fees->include_fee_installments)
                        @php
                            $is_overdue =
                                $due_charges > 0 ||
                                (date('Y-m-d') > $fees->getRawOriginal('due_date') && $global_balance > 0);
                            $card_status_label = __('UNPAID');
                            $card_badge_class = 'badge-secondary';

                            if ($global_balance <= 0) {
                                $card_status_label = __('PAID');
                                $card_badge_class = 'badge-success';

                            } elseif ($total_paid > 0) {
                                $card_status_label = __('PARTIAL');
                                $card_badge_class = 'badge-warning';
                            }
                        @endphp
                        <div class="card mb-4 shadow-sm border-left-primary"
                            style="border-left: 5px solid var(--theme-color);">
                            <div class="card-body py-3">
                                <div class="row align-items-center">
                                    {{-- Left: Fee Name --}}
                                    <div class="col-md-4">
                                        <h5 class="mb-0 font-weight-bold theme-color">
                                            <i class="fa fa-file-text-o mr-2"></i> {{ $fees->name }}
                                        </h5>
                                    </div>

                                    {{-- Middle: Due Date & Status --}}
                                    <div class="col-md-4 text-center border-left border-right">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-2">
                                                <small class="text-muted font-weight-bold">{{ __('due_date') }}</small>
                                                <div class="h6 mb-0">
                                                    {{ $fees->due_date }}
                                                    @if ($is_overdue)
                                                        <span class="text-danger small font-weight-bold ml-1">({{ __('overdue') }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="badge {{ $card_badge_class }}" >
                                                {{ $card_status_label }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Right: Financial Breakdown --}}
                                    <div class="col-md-4">
                                        <div class="text-right">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted small">{{ __('Total Fee') }}:</span>
                                                <span class="font-weight-bold">{{ number_format($fees->total_compulsory_fees, 2) . ' ' . $currencySymbol }}</span>
                                            </div>
                                            
                                            @if ($due_charges > 0)
                                            <div class="d-flex justify-content-between mb-1 text-danger">
                                                <span class="small">{{ __('due_charges') }}:</span>
                                                <span class="font-weight-bold">+ {{ number_format($due_charges, 2) . ' ' . $currencySymbol }}</span>
                                            </div>
                                            @endif

                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted small">{{ __('paid_amount') }}:</span>
                                                <span class="font-weight-bold text-success"> {{ number_format($total_paid, 2) . ' ' . $currencySymbol }}</span>
                                            </div>
                                            
                                            <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                                <span class="h6 mb-0 font-weight-bold theme-color">{{ __('balance_due') }}:</span>
                                                <span class="h5 mb-0 font-weight-bold text-dark">{{ number_format($global_balance, 2) . ' ' . $currencySymbol }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        @php
                            $next_installment_found = false;
                        @endphp
                        @foreach ($fees->installments as $key => $installment)
                            @php
                                $status = $installment->status ?? 0;
                                $balance = $installment->balance_due ?? ($installment->total_expected_amount ?? $installment->total_amount);

                                $paid_amount = $installment->paid_amount ?? 0;
                                $total_amount = $installment->total_expected_amount ?? $installment->total_amount;
                                $due_date = date('d-m-Y', strtotime($installment->due_date));

                                // High Contrast Badge Styling
                                $badge_style =
                                    'padding: 5px 15px; font-size: 0.75rem; border-radius: 50px;';

                                if ($status == 1) {
                                    // Paid
                                    $card_theme = 'border-success';
                                    $bg_theme = 'bg-light-success';
                                    $text_theme = 'text-success';
                                    $icon = 'fa-check-circle';
                                    $status_label = __('PAID'); // Uppercase
                                    $opacity = '1';
                                    $badge_class = 'badge-success'; // Fallback or inline
                                    // Specific Colors
                                } elseif ($status == 2) {
                                    // Partial
                                    $card_theme = 'border-warning';
                                    $bg_theme = 'bg-light-warning';
                                    $text_theme = 'text-warning';
                                    $icon = 'fa-adjust';
                                    $status_label = __('PARTIAL');
                                    $opacity = '1';
                                    $badge_class = 'badge-info';
                                } else {
                                    // Unpaid
                                    $card_theme = 'border-light';
                                    $bg_theme = 'bg-white';
                                    $text_theme = 'text-secondary';
                                    $icon = 'fa-calendar';
                                    $status_label = __('UNPAID');
                                    $opacity = '1';
                                    $badge_class = 'badge-warning';
                                    
                                    // Check if Overdue
                                    $due_date_obj = \Carbon\Carbon::parse($installment->due_date);
                                    $is_overdue_item = $due_date_obj->isPast() && !$due_date_obj->isToday();

                                    if ($is_overdue_item) {
                                        $card_theme = 'border-danger shadow-sm';
                                        $text_theme = 'text-danger';
                                        $status_label = __('OVERDUE');
                                        $badge_class = 'badge-danger';
                                        $icon = 'fa-exclamation-circle';
                                    } elseif (!$next_installment_found) {
                                        $next_installment_found = true;
                                        $card_theme = 'border-primary shadow-sm'; // Use Primary/Blue for Next Due
                                        $text_theme = 'theme-color';
                                        $status_label = __('NEXT DUE');
                                        $badge_class = 'badge-primary';
                                    } else {
                                        // Standard Unpaid future
                                         // $opacity = '0.7';
                                    }
                                }
                            @endphp

                            <div class="col-md-12 mb-3">
                                <div class="card {{ $card_theme }} installment-card" data-id="{{ $installment->id }}" data-status="{{ $installment->status }}"
                                    style="border-left: 5px solid; opacity: {{ $opacity }};">
                                    <div class="card-body py-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <h5 class="mb-0 font-weight-bold {{ $text_theme }}">
                                                    <i class="fa {{ $icon }} mr-2"></i> {{ $installment->name }}
                                                </h5>
                                                <small class="text-muted">{{ __('due') }}: {{ $due_date }}</small>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <span class="badge {{ $badge_class }}" >{{ $status_label }}</span>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="text-right w-100">
                                                        <div class="small text-muted">
                                                            {{ __('total') }}: {{ $total_amount . ' ' . $currencySymbol }}
                                                        </div>
                                                        @if (isset($installment->due_charges_amount) && $installment->due_charges_amount > 0)
                                                            <div class="small text-danger">
                                                                {{ __('due_charges') }}: + {{ number_format($installment->due_charges_amount, 2) . ' ' . $currencySymbol }}
                                                            </div>
                                                        @endif
                                                        {{-- @foreach ($installment->transactions as $transaction)
                                                            @if ($transaction && $transaction->due_charges > 0)
                                                                <div class="small text-danger mt-2">
                                                                   + {{ $transaction->due_charges . ' ' . $currencySymbol }}
                                                                </div>
                                                            @endif
                                                        @endforeach --}}
                                                        <div class="font-weight-bold h5 mt-2 mb-0 installment-amount" data-amount="{{ $balance }}">
                                                            {{ __('due') }}: {{ $balance . ' ' . $currencySymbol }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Transaction History (Global/Full Payments) --}}
                    {{-- Only show if existing fees_paid transactions exist that are NOT linked to specific installments (or just generally) --}}
                    {{-- Since we handle installment payments inside cards, this section is for Global Full Payments --}}

                    @php
                        // Fetch global transactions (where installment_id is null or type is Full Payment)
                        // Ideally we should filter this in controller and pass it.
                        // But we can filter from student->compulsory_fees collection if available.
                        $global_transactions = $student->compulsory_fees->where('type', 1); // Type 1 = Full Payment
                    @endphp

                    @if ($global_transactions->count() > 0)
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom-0">
                                <h5 class="mb-0 font-weight-bold">{{ __('Transaction History') }} <small
                                        class="text-muted">({{ __('Full Payments') }})</small></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-top-0">{{ __('Date') }}</th>
                                                <th class="border-top-0">{{ __('Payment Mode') }}</th>
                                                <th class="border-top-0">{{ __('Transaction Type') }}</th>
                                                <th class="border-top-0 text-right">{{ __('Amount') }}</th>
                                                {{-- <th class="border-top-0 text-center">{{ __('Action') }}</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($global_transactions as $transaction)
                                                <tr>
                                                    <td>{{ date('d-m-Y', strtotime($transaction->date)) }}</td>
                                                    <td>
                                                        @if ($transaction->mode == 1)
                                                            {{ __('Cash') }}
                                                        @elseif($transaction->mode == 2)
                                                            {{ __('Cheque') }}
                                                        @elseif($transaction->mode == 3)
                                                            {{ __('Online') }}
                                                        @endif
                                                    </td>
                                                    <td><span class="badge badge-info-custom"
                                                            style="padding: 5px 10px; background-color: #cff4fc; color: #055160;">{{ __('Full Payment') }}</span>
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($transaction->amount, 2) . ' ' . $currencySymbol }}
                                                    </td>
                                                    {{-- <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-transaction-btn"
                                                            data-id="{{ $transaction->id }}">
                                                            <i class="fa fa-trash mr-1"></i> {{ __('Revert') }}
                                                        </button>
                                                    </td> --}}
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Zone 3: Sticky Payment Action Bar --}}
                    {{-- Logic: Visible if Global Balance > 0 --}}
                    @if ($global_balance > 0)
                        <div class="card fixed-bottom-action border-primary pt-2 pb-5 mb-4"
                            style="position: sticky; bottom: 20px; z-index: 1000; border-top: 5px solid #0d6efd;">
                            <div class="card-body p-3 bg-white">
                                <div class="row align-items-center">
                                    {{-- Payment Mode & Advance --}}
                                    <div class="col-md-12">
                                        {!! Form::hidden('include_fee_installments', $fees->include_fee_installments, ['id' => 'include_fee_installments']) !!}
                                        @if ($fees->include_fee_installments && !$oneInstallmentPaid)
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="font-weight-bold mr-3">{{ __('mode') }}:</span>
                                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                    <label class="btn btn-outline-primary active btn-sm">
                                                        <input type="radio" class="payment-mode-toggle"
                                                            name="installment_mode" value="1" checked>
                                                        {{ __('installment') }}
                                                    </label>
                                                    <label class="btn btn-outline-primary btn-sm">
                                                        <input type="radio" class="payment-mode-toggle"
                                                            name="installment_mode" value="0">
                                                        {{ __('full_amount') }}
                                                    </label>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-none align-items-center mb-2">
                                                <span class="font-weight-bold mr-3">{{ __('mode') }}:</span>
                                                <div class="btn-group btn-group-toggle" data-toggle="buttons">

                                                    <label class="btn active btn-outline-primary btn-sm">
                                                        <input type="radio" checked class="payment-mode-toggle"
                                                            name="installment_mode" value="0">
                                                        {{ __('full_amount') }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($oneInstallmentPaid)
                                            <div class="d-flex d-none align-items-center mb-2">
                                                <span class="font-weight-bold mr-3">{{ __('mode') }}:</span>
                                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                    <label class="btn btn-outline-primary active btn-sm">
                                                        <input type="radio" class="payment-mode-toggle"
                                                            name="installment_mode" value="1" checked>
                                                        {{ __('installment') }}
                                                    </label>
                                                    {{-- <label class="btn btn-outline-primary btn-sm">
                                                        <input type="radio" class="payment-mode-toggle"
                                                            name="installment_mode" value="0">
                                                        {{ __('full_amount') }}
                                                    </label> --}}
                                                </div>
                                            </div>
                                        @endif


                                        @if ($student_advance > 0)
                                            <div class="alert alert-success py-1 px-2 mb-0 d-inline-block small">
                                                <i class="fa fa-info-circle"></i> {{ __('advance') }}:
                                                <strong>{{ $student_advance . ' ' . $currencySymbol }}</strong>
                                                <input type="hidden" name="advance" value="{{ $student_advance }}">
                                            </div>
                                        @else
                                            <input type="hidden" name="advance" value="0">
                                        @endif

                                        
                                    </div>

                                    <div class="col-sm-12 col-md-2">
                                        <div class="mt-2 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label for="cash" class="form-check-label">
                                                    <input type="radio" name="mode" class="form-check-input"
                                                    value="1" checked id="cash"> {{ __('Cash') }}
                                                </label>
                                                
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label for="cheque" class="form-check-label">
                                                    <input type="radio" name="mode" class="form-check-input"
                                                    value="2" id="cheque"> {{ __('Cheque') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    

                                    {{-- Input & Action --}}
                                    <div class="col-md-12">
                                        <div class="row mr-3">
                                            <div class="col-sm-12 col-md-3">
                                                <label> {{ __('date') }}</label>
                                                {!! Form::text('date', null, [
                                                    'required',
                                                    'placeholder' => __('date'),
                                                    'class' => 'datepicker-popup form-control',
                                                    'autocomplete' => 'off',
                                                ]) !!}
                                            </div>

                                            @if (count($fees->installments))
                                                 <div class="col-sm-12 col-md-3 d-flex flex-column position-relative">
                                                    <label>{{ __('enter_amount') }}</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text bg-light font-weight-bold">{{ $currencySymbol }}</span>
                                                        </div>
                                                        <input type="number" name="amount" id="enter_amount" class="form-control theme-color" min="1" required placeholder="0.00">
                                                    </div>
                                                    <div class="invalid-feedback" style="display: none;"></div>
                                                    <small id="amount_feedback" class="mt-2 font-weight-bold text-danger order-3" style="display:none;"></small>
                                                </div>
                                            @else
                                                <div class="col-sm-12 col-md-3 d-flex flex-column position-relative">
                                                    <label>{{ __('enter_amount') }}</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text bg-light font-weight-bold">{{ $currencySymbol }}</span>
                                                        </div>
                                                        <input type="number" readonly name="amount" id="enter_amount" class="form-control theme-color" min="1" required placeholder="0.00">
                                                    </div>
                                                    <div class="invalid-feedback" style="display: none;"></div>
                                                    <small id="amount_feedback" class="mt-2 font-weight-bold text-danger order-3" style="display:none;"></small>
                                                </div>
                                            @endif
                                           

                                            <div class="col-sm-12 col-md-3 d-none" id="cheque_no_div">
                                                <label> {{ __('cheque_no') }}</label>
                                                {!! Form::text('cheque_no', null, [
                                                    'required',
                                                    'placeholder' => __('cheque_no'),
                                                    'class' => 'form-control',
                                                    'id' => 'cheque_no',
                                                ]) !!}
                                            </div>

                                            <div class="col-md-12 mt-auto">
                                                <button type="submit" id="collect_btn"
                                                    class="btn btn-theme float-right btn-lg px-5 shadow-sm">{{ __('collect_fee') }}</button>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- If full payment --}}
                    @if ($fees && count($fees->complusory_details) > 0)
                    
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">{{ __('payment_details') }}</h4>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-top-0">{{ __('date') }}</th>
                                                <th class="border-top-0">{{ __('payment_mode') }}</th>
                                                <th class="border-top-0">{{ __('transaction_type') }}</th>
                                                <th class="border-top-0 text-right">{{ __('amount') }}</th>
                                                <th class="border-top-0 text-right">{{ __('due_charges') }}</th>
                                                {{-- <th class="border-top-0 text-center">{{ __('action') }}</th> --}}
                                            </tr>
                                        </thead>
                                        @foreach ($fees->complusory_details as $data)
                                            <tbody>
                                                <tr>
                                                    <td>{{ $data->date }}</td>
                                                    <td>
                                                        {{ $data->mode }}
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info-custom" style="padding: 5px 10px; background-color: #cff4fc; color: #055160;">{{ $data->type }}</span>
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($data->amount, 2) . ' ' . $currencySymbol }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($data->due_charges, 2) . ' ' . $currencySymbol }}
                                                    </td>
                                                    {{-- <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger p-1 delete-transaction-btn"
                                                            data-id="{{ $data->id }}"> <i
                                                                class="fa fa-trash"></i>
                                                        </button>
                                                    </td> --}}
                                                </tr>
                                            </tbody>
                                        @endforeach
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-right font-weight-bold">{{ __('total') }}</td>
                                                <td class="text-right font-weight-bold">
                                                    {{ number_format($fees->complusory_details->sum('amount'), 2) . ' ' . $currencySymbol }}
                                                </td>
                                                <td class="text-right font-weight-bold">
                                                    {{ number_format($fees->complusory_details->sum('due_charges'), 2) . ' ' . $currencySymbol }}
                                                </td>
                                                {{-- <td>

                                                </td> --}}
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('.datepicker-popup').datepicker({
            format: "dd-mm-yyyy",
            rtl: isRTL()
        }).datepicker("setDate", 'now');

        function successFunction() {
            // window.location.href = "{{ route('fees.paid.index') }}";
            window.location.reload();
        }

        $(document).ready(function() {
            const currencySymbol = "{{ $currencySymbol }}";
            const studentAdvance = parseFloat($('input[name="advance"]').val()) || 0;
            // Use the PHP calculated global balance which considers Due Charges
            const remainingAmount = parseFloat("{{ $global_balance }}");
            const minPayableAmount = parseFloat("{{ $min_payable_amount ?? 0 }}");
            const totalPayableAmount = parseFloat("{{ $total_payable_amount ?? 0 }}");
            const overdueInstallmentIds = @json($overdue_installment_ids ?? []);

            // Collect Installments Data (Due Amounts)
            let installments = [];
            $('.installment-amount').each(function(index) {
                let amountText = $(this).data('amount');
                let amount = parseFloat(amountText);
                if (amount > 0) {
                    installments.push(amount);
                }
            });

            // on change of mode (Cash/Cheque)
            $('input[name="mode"]').on('change', function() {
                let mode = $(this).val();
                if (mode == 2) {
                    $('#cheque_no_div').removeClass('d-none');
                } else {
                    $('#cheque_no_div').addClass('d-none');
                }
            });


            // Payment Mode Toggle Logic (Installment vs Full)
            $('.payment-mode-toggle').on('change', function() {
                let mode = $(this).val(); // 1 = Installment, 0 = Full
                calculateAutoFill(mode);
            });

            function calculateAutoFill(mode) {
                let fillAmount = 0;
                let inputField = $('#enter_amount');
                let paymentMode = $('.payment-mode-toggle:checked').val();
                
                // Reset Functionality
                inputField.attr('readonly', false);
                $('.installment-card').removeClass('border-danger shadow-lg');
                $('.installment-card').removeClass('highlight-overdue'); // Custom class for styling if needed

                if (mode == 1) { // Installment Mode
                    if (minPayableAmount > 0) {
                        fillAmount = minPayableAmount;
                        
                        // Highlight Overdue Installments
                        if (overdueInstallmentIds.length > 0) {
                            overdueInstallmentIds.forEach(id => {
                                let card = $('.installment-card[data-id="' + id + '"]');
                                card.addClass('border-danger shadow-lg highlight-overdue');
                                card.find('.text-secondary').addClass('theme-color').removeClass('text-secondary');
                            });
                        } else {
                             // If no overdue, highlight next due (first unpaid)
                             let firstUnpaid = $('.installment-card[data-status="0"], .installment-card[data-status="2"]').first();
                             if (firstUnpaid.length) {
                                 firstUnpaid.addClass('border-danger shadow-lg');
                             }
                        }
                    } else {
                         // Fallback if Min is 0 (all paid or future)
                         if (installments.length > 0) {
                             fillAmount = installments[0];
                             let firstUnpaid = $('.installment-card[data-status="0"], .installment-card[data-status="2"]').first();
                             if (firstUnpaid.length) {
                                 firstUnpaid.addClass('border-danger shadow-lg');
                             }
                         }
                    }
                } else { // Full Amount Mode
                    fillAmount = totalPayableAmount;
                    
                    // Highlight ALL Unpaid/Partial
                    $('.installment-card').each(function() {
                        let status = $(this).data('status');
                        if (status == 0 || status == 2) {
                            $(this).addClass('border-danger shadow-lg');
                        }
                    });

                    // In Full Amount Mode, likely strict amount?
                    inputField.attr('readonly', true);
                }
                
                // Adjust for Advance (We need to ask User for (Target - Advance))
                let netPayable = Math.max(0, fillAmount - studentAdvance);
                
                inputField.val(netPayable.toFixed(2));
                
                // Set Max Attribute
                // Max is Total Remaining less Advance
                let effectiveMax = Math.max(0, remainingAmount - studentAdvance);
                
                if (paymentMode == 0) {
                    // Specific logic for Full Payment with Due Charges adjustment if handled separately?
                    // But usually remainingAmount covers it.
                    
                    // In Full Mode, we simply rely on the effectiveMax which is derived from totalPayableAmount (global_balance)
                    // or the overridden payFullFeesAmountDueCharges if provided.
                    
                    // Note: We removed the check `if (oneInstallmentPaid != 1)` to fallback to strict backend total if available.
                    // if (typeof {{ $payFullFeesAmountDueCharges ?? 'undefined' }} !== 'undefined') {
                    //      let forcedMax = {{ $payFullFeesAmountDueCharges ?? 0 }};
                    //      effectiveMax = Math.max(0, forcedMax - studentAdvance);
                    // }

                    if (typeof {{ $instalmentFeesFullPaid ?? 'undefined' }} !== 'undefined') {
                         let forcedMax = {{ $instalmentFeesFullPaid ?? 0 }};
                         effectiveMax = Math.max(0, forcedMax - studentAdvance);
                    }

                    inputField.val(effectiveMax.toFixed(2));
                    inputField.attr('min', effectiveMax.toFixed(2));
                } else {
                    // Set Min Attribute
                    // Min is MinPayable less Advance (Always enforce min payment of overdue)
                    let effectiveMin = Math.max(0, minPayableAmount - studentAdvance);
                    if (effectiveMin > 0) {
                        inputField.attr('min', effectiveMin.toFixed(2));
                    } else {
                        inputField.attr('min', 1); // Standard 1 for transaction
                    }
                }
                inputField.attr('max', effectiveMax.toFixed(2));

                
                


                

                // Trigger input event to validation
                inputField.trigger('input'); 
            }

            // Initial Setup
            let initialMode = $('.payment-mode-toggle:checked').val(); 
            if (typeof initialMode === 'undefined') initialMode = 1; // Default to Installment
            calculateAutoFill(initialMode);


            // Dynamic Validation on Input
            $('#enter_amount').on('input', function() {
                let inputAmount = parseFloat($(this).val());
                let feedback = $('#amount_feedback');
                let submitBtn = $('#collect_btn');
                let maxVal = parseFloat($(this).attr('max'));
                let minVal = parseFloat($(this).attr('min')) || 0;
                
                // Reset state
                feedback.hide();
                submitBtn.prop('disabled', false);

                if (isNaN(inputAmount) || inputAmount <= 0) {
                     submitBtn.prop('disabled', true);
                     return;
                }

                // 1. Check Min (Underpayment of Overdue)
                // Use a small epsilon for float comparison safety
                if (inputAmount < (minVal - 0.01)) {
                    feedback.text("{{ __('Minimum payment required is') }} " + currencySymbol + minVal.toFixed(2) + " {{ __('to cover overdue installments/charges') }}")
                            .removeClass('text-success text-warning').addClass('text-danger').show();
                     submitBtn.prop('disabled', true);
                     return;
                }

                // 2. Feedback Logic
                if (inputAmount > (maxVal + 0.01)) {
                    // Case: Input > Total Outstanding -> Advance
                    let advance = inputAmount - maxVal;
                    feedback.text("{{ __('Excess amount of') }} " + currencySymbol + advance.toFixed(2) + " {{ __('will be recorded as advance.') }}")
                            .removeClass('text-danger text-success').addClass('text-warning').show();
                } else if (inputAmount > (minVal + 0.01)) {
                    // Case: Min < Input <= Max
                    let extra = inputAmount - minVal;
                    
                    if (Math.abs(inputAmount - maxVal) < 1.00) {
                         // Close enough to Max
                         feedback.text("{{ __('Great! You are clearing all outstanding dues.') }}")
                                .removeClass('text-danger text-warning').addClass('text-success').show();
                    } else {
                        // Partial Future Coverage
                        feedback.text("{{ __('Payment covers overdue') }} + " + currencySymbol + extra.toFixed(2) + " {{ __('towards future installments.') }}")
                                .removeClass('text-danger text-warning').addClass('text-success').show();
                    }
                } else {
                    // Exact Minimum (or close to it)
                    feedback.hide();
                }
            });
        });     // Delete Transaction Logic
            $(document).on('click', '.delete-transaction-btn', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let url = "{{ route('fees.transaction.delete', ':id') }}";
                url = url.replace(':id', id);

                showDeletePopupModal(url, {
                    successCallBack: function() {
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                });
            });
        // });
    </script>
@endsection