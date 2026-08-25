@extends('layouts.master')

@section('title')
    {{ __('Salary Structure') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/salary-structure.css') }}">
@endsection

@section('content')
<div class="content-wrapper">

    {{-- Page Header --}}
    <div class="ss-page-header">
        <div class="ss-page-header-left">
            @if($type == 'teacher')
                <a href="{{ url()->previous() }}" class="ss-back-link">
                    <i class="fa fa-arrow-left"></i> {{ __('back_to_teacher_list') }}
                </a>
            @else
                <a href="{{ url()->previous() }}" class="ss-back-link">
                    <i class="fa fa-arrow-left"></i> {{ __('back_to_staff_list') }}
                </a>
            @endif
            <h3>{{ __('salary_structure') }}</h3>
            <p>{{ __('configure_compensation_components_for_individual_staff_members') }}</p>
        </div>
        <div class="ss-page-header-actions">
            <button type="button" id="btn-ss-reset" class="btn btn-secondary btn-sm">
                {{ __('reset_changes') }}
            </button>
            <button type="submit" id="btn-save-structure" form="ss-payroll-form" class="btn-sm btn btn-theme">
                {{ __('save_structure') }}
            </button>
        </div>
    </div>

    {{-- Inline var for delete URL --}}
    <script>
        window.payrollDeleteUrl = "{{ url('staff/payroll-setting') }}";
    </script>

    <form
        id="ss-payroll-form"
        action="{{ url('staff/payroll-setting', $user->staff->id) }}"
        method="POST"
        class="edit-form-staff-payroll-setting edit-form ss-form"
        data-success-function="formSuccessFunction"
    >
        @csrf

        {{-- Hidden summary inputs (used by legacy controller) --}}
        <input type="hidden" id="ss-hidden-basic"    name="basic_salary"  value="{{ $user->staff->salary }}">
        <input type="hidden" id="ss-hidden-allowance" name="allowance_total" value="0">
        <input type="hidden" id="ss-hidden-deduction" name="deduction_total" value="0">
        <input type="hidden" id="ss-hidden-net"       name="net_salary"     value="0">
        <input type="hidden" id="ss-hidden-currency-symbol" name="currency_symbol" value="{{ $schoolSettings['currency_symbol'] ?? '$' }}">

        <div class="row">

            {{-- ── LEFT COLUMN: Profile + Summary ── --}}
            <div class="col-md-4 col-sm-12">

                {{-- Staff Profile Card --}}
                <div class="ss-profile-card">
                    <div class="ss-profile-banner"></div>
                    <div class="ss-profile-avatar-wrap">
                        @if($user->image)
                            <img src="{{ $user->image }}" alt="{{ $user->first_name }}" class="ss-profile-avatar">
                        @else
                            <div class="ss-profile-avatar-placeholder">
                                {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="ss-profile-body">
                        <div class="ss-profile-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                        <div class="ss-profile-role">
                            @foreach($user->roles as $role)
                                {{ $role->name }}@if(!$loop->last), @endif
                            @endforeach
                        </div>
                        <div class="ss-profile-meta">
                            @if($user->staff && $user->staff->joining_date)
                                <div class="ss-meta-item">
                                    <i class="fa fa-calendar"></i>
                                    <span>{{ __('Joined') }} {{ $user->staff->joining_date }}</span>
                                </div>
                            @endif
                            @if($user->mobile)
                                <div class="ss-meta-item">
                                    <i class="fa fa-phone"></i>
                                    <span>{{ $user->mobile }}</span>
                                </div>
                            @endif
                            @if($user->email)
                                <div class="ss-meta-item">
                                    <i class="fa fa-envelope"></i>
                                    <span style="word-break:break-all;">{{ $user->email }}</span>
                                </div>
                            @endif
                            @if($user->staff && $user->staff->id)
                                <div class="ss-meta-item">
                                    <i class="fa fa-id-badge"></i>
                                    <span>STF-{{ str_pad($user->staff->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Live Summary Card --}}
                <div class="ss-summary-card">
                    <div class="ss-summary-label text-uppercase">{{ __('live_summary') }}</div>

                    <div class="ss-summary-row">
                        <span class="ss-sr-title">{{ __('basic_salary') }}</span>
                        <span class="ss-sr-value" id="ss-live-basic">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                    </div>
                    <div class="ss-summary-row">
                        <span class="ss-sr-title">{{ __('total_allowances') }}</span>
                        <span class="ss-sr-value is-allowance" id="ss-live-allowances">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                    </div>
                    <div class="ss-summary-row">
                        <span class="ss-sr-title">{{ __('total_deductions') }}</span>
                        <span class="ss-sr-value is-deduction" id="ss-live-deductions">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                    </div>

                    <div class="ss-summary-net">
                        <span class="ss-net-title">{{ __('net_take_home') }}</span>
                        <span class="ss-net-value" id="ss-live-net">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                    </div>
                </div>

            </div>{{-- /LEFT COLUMN --}}

            {{-- ── RIGHT COLUMN: Compensation + Allowances/Deductions ── --}}
            <div class="col-md-8 col-sm-12">

                {{-- Core Compensation --}}
                <div class="ss-card">
                    <div class="ss-card-header">
                        <div class="ss-card-title">
                            <div class="ss-card-icon is-compensation">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            {{ __('core_compensation') }}
                        </div>
                    </div>
                    <label class="ss-comp-label">{{ __('base_basic_salary_monthly') }}</label>
                    <div class="ss-comp-input-wrap">
                        {{-- <span class="ss-comp-currency">{{ $schoolSettings['currency_symbol'] ?? '$' }}</span> --}}
                        {{-- <input type="number" id="ss-basic-salary" class="ss-comp-input" value="{{ $user->staff->salary }}" readonly tabindex="-1" > --}}

                        <input type="text" id="ss-basic-salary" class="ss-comp-input" value="{{ ($schoolSettings['currency_symbol'] ?? '$') . ' ' . $user->staff->salary }}" readonly tabindex="-1">
                    </div>
                </div>

                {{-- Allowances + Deductions (side by side) --}}
                <div class="row">

                    {{-- ── Allowances Card ── --}}
                    <div class="col-md-6 col-sm-12">
                        <div class="ss-card">
                            <div class="ss-card-header">
                                <div class="ss-card-title">
                                    <div class="ss-card-icon is-allowance">
                                        <i class="fa fa-plus-circle"></i>
                                    </div>
                                    {{ __('allowances') }}
                                </div>
                                <button type="button" id="btn-add-allowance" class="btn-ss-add is-allowance" title="{{ __('add_allowance') }}">+</button>
                            </div>

                            {{-- Existing Saved Allowances --}}
                            @foreach($user->staff->staffSalary as $rowItem)
                                @if($rowItem->payrollSetting->type === 'allowance')
                                    <div class="ss-item-row" data-state="display">
                                        {{-- Data holder for JS calculation --}}
                                        @if(!is_null($rowItem->amount))
                                            <span class="ss-existing-allowance-amount" data-value="{{ $rowItem->amount }}"></span>
                                        @else
                                            @php
                                                $basic   = $user->staff->salary ?? 0;
                                                $pctAmt  = ($basic * ($rowItem->percentage ?? 0)) / 100;
                                            @endphp
                                            <span class="ss-existing-allowance-amount" data-value="{{ $pctAmt }}"></span>
                                        @endif

                                        {{-- Display Mode --}}
                                        <div class="ss-row-display">
                                            <div style="flex:1">
                                                <div class="ss-item-name ss-display-name">{{ $rowItem->payrollSetting->name }}</div>
                                                @if(!is_null($rowItem->amount))
                                                    <span class="ss-item-amount-label ss-display-subtext">Fixed Amount</span>
                                                @else
                                                    <span class="ss-item-amount-label ss-display-subtext">{{ $rowItem->percentage }}% of Basic</span>
                                                @endif
                                            </div>

                                            <span class="ss-item-amount is-allowance ss-display-value">
                                                @if(!is_null($rowItem->amount))
                                                    {{ $schoolSettings['currency_symbol'] ?? '$' }} {{ number_format($rowItem->amount) }}
                                                @else
                                                    {{ $schoolSettings['currency_symbol'] ?? '$' }} {{ number_format(($user->staff->salary * $rowItem->percentage) / 100) }}
                                                @endif
                                            </span>

                                            <div class="ss-item-actions">
                                                <button type="button" class="btn-ss-edit" title="{{ __('Edit') }}">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button" class="btn-ss-delete ss-delete-existing" data-id="{{ $rowItem->id }}" title="{{ __('Remove') }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Edit Mode for Existing --}}
                                        <div class="ss-row-edit">
                                            <div class="form-group">
                                                <label>{{ __('Type') }}</label>
                                                <select name="allowance[{{ 100 + $loop->index }}][id]" class="form-control ss-new-row-select">
                                                    @foreach($allowances->where('deleted_at', null) as $allowance)
                                                        <option
                                                            value="{{ $allowance->id }}"
                                                            data-name="{{ $allowance->name }}"
                                                            data-value="{{ !is_null($allowance->amount) ? $allowance->amount : $allowance->percentage }}"
                                                            data-type="{{ !is_null($allowance->amount) ? 'amount' : 'percentage' }}"
                                                            {{ $rowItem->payroll_setting_id == $allowance->id ? 'selected' : '' }}
                                                        >{{ $allowance->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group ss-amount-wrap" style="{{ !is_null($rowItem->amount) ? '' : 'display:none' }}">
                                                <label>{{ __('Amount') }}</label>
                                                <input type="number" name="allowance[{{ 100 + $loop->index }}][amount]" class="form-control ss-amount-input" value="{{ $rowItem->amount }}" {{ !is_null($rowItem->amount) ? '' : 'disabled' }}>
                                            </div>
                                            <div class="form-group ss-pct-wrap" style="{{ !is_null($rowItem->percentage) ? '' : 'display:none' }}">
                                                <label>{{ __('Percentage') }}</label>
                                                <input type="number" name="allowance[{{ 100 + $loop->index }}][percentage]" class="form-control ss-pct-input" value="{{ $rowItem->percentage }}" {{ !is_null($rowItem->percentage) ? '' : 'disabled' }}>
                                            </div>
                                            <div class="ss-edit-actions">
                                                <button type="button" class="btn-ss-confirm" title="{{ __('Confirm') }}">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button type="button" class="btn-ss-cancel-existing" title="{{ __('Cancel') }}">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            {{-- New Allowance Rows Repeater --}}
                            <div id="allowance-repeater" data-counter="1">

                                {{-- Template row (hidden, cloned on "+" click) --}}
                                <div class="ss-item-row ss-new-row" data-template data-state="edit" style="display:none">
                                    
                                    {{-- Edit Mode --}}
                                    <div class="ss-row-edit">
                                        <div class="form-group">
                                            <label>{{ __('Type') }}</label>
                                            <select name="allowance[0][id]" class="form-control ss-new-row-select">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($allowances->where('deleted_at', null) as $allowance)
                                                    <option
                                                        value="{{ $allowance->id }}"
                                                        data-name="{{ $allowance->name }}"
                                                        data-value="{{ !is_null($allowance->amount) ? $allowance->amount : $allowance->percentage }}"
                                                        data-type="{{ !is_null($allowance->amount) ? 'amount' : 'percentage' }}"
                                                    >{{ $allowance->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group ss-amount-wrap" style="display:none">
                                            <label>{{ __('Amount') }}</label>
                                            <input type="number" name="allowance[0][amount]" class="form-control ss-amount-input" min="1" placeholder="{{ __('Amount') }}" disabled>
                                        </div>
                                        <div class="form-group ss-pct-wrap" style="display:none">
                                            <label>{{ __('Percentage') }}</label>
                                            <input type="number" name="allowance[0][percentage]" class="form-control ss-pct-input" min="0.1" max="100" placeholder="{{ __('%') }}" disabled>
                                        </div>
                                        <div class="ss-edit-actions">
                                            <button type="button" class="btn-ss-confirm" title="{{ __('Confirm') }}">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn-ss-cancel ss-remove-new-row" title="{{ __('Cancel') }}">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Display Mode (Hidden by default in new rows) --}}
                                    <div class="ss-row-display">
                                        <div style="flex:1">
                                            <div class="ss-item-name ss-display-name">---</div>
                                            <span class="ss-item-amount-label ss-display-subtext">---</span>
                                        </div>
                                        <span class="ss-item-amount is-allowance ss-display-value">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                                        <div class="ss-item-actions">
                                            <button type="button" class="btn-ss-edit" title="{{ __('Edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-ss-delete ss-remove-new-row" title="{{ __('Remove') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Add button --}}
                                <div class="ss-add-row">
                                    <button type="button" id="btn-add-allowance-inner" class="btn-ss-add-row is-allowance" style="display:none">
                                        <i class="fa fa-plus"></i> {{ __('Add Allowance') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Subtotal --}}
                            <div class="ss-subtotal">
                                <span class="ss-subtotal-label text-uppercase">{{ __('subtotal') }}</span>
                                <span class="ss-subtotal-value is-allowance" id="ss-allowance-subtotal">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                            </div>
                        </div>
                    </div>{{-- /Allowances --}}

                    {{-- ── Deductions Card ── --}}
                    <div class="col-md-6 col-sm-12">
                        <div class="ss-card">
                            <div class="ss-card-header">
                                <div class="ss-card-title">
                                    <div class="ss-card-icon is-deduction">
                                        <i class="fa fa-minus-circle"></i>
                                    </div>
                                    {{ __('deductions') }}
                                </div>
                                <button type="button" id="btn-add-deduction" class="btn-ss-add is-deduction" title="{{ __('add_deduction') }}">+</button>
                            </div>

                            {{-- Existing Saved Deductions --}}
                            @foreach($user->staff->staffSalary as $rowItem)
                                @if($rowItem->payrollSetting->type === 'deduction')
                                    <div class="ss-item-row" data-state="display">
                                        @if(!is_null($rowItem->amount))
                                            <span class="ss-existing-deduction-amount" data-value="{{ $rowItem->amount }}"></span>
                                        @else
                                            @php
                                                $basic  = $user->staff->salary ?? 0;
                                                $pctAmt = ($basic * ($rowItem->percentage ?? 0)) / 100;
                                            @endphp
                                            <span class="ss-existing-deduction-amount" data-value="{{ $pctAmt }}"></span>
                                        @endif

                                        {{-- Display Mode --}}
                                        <div class="ss-row-display">
                                            <div style="flex:1">
                                                <div class="ss-item-name ss-display-name">{{ $rowItem->payrollSetting->name }}</div>
                                                @if(!is_null($rowItem->amount))
                                                    <span class="ss-item-amount-label ss-display-subtext">Fixed Amount</span>
                                                @else
                                                    <span class="ss-item-amount-label ss-display-subtext">{{ $rowItem->percentage }}% of Basic</span>
                                                @endif
                                            </div>

                                            <span class="ss-item-amount is-deduction ss-display-value">
                                                @if(!is_null($rowItem->amount))
                                                    {{ $schoolSettings['currency_symbol'] ?? '$' }} {{ number_format($rowItem->amount) }}
                                                @else
                                                    {{ $schoolSettings['currency_symbol'] ?? '$' }} {{ number_format(($user->staff->salary * $rowItem->percentage) / 100) }}
                                                @endif
                                            </span>

                                            <div class="ss-item-actions">
                                                <button type="button" class="btn-ss-edit" title="{{ __('Edit') }}">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button" class="btn-ss-delete ss-delete-existing" data-id="{{ $rowItem->id }}" title="{{ __('Remove') }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Edit Mode for Existing --}}
                                        <div class="ss-row-edit">
                                            <div class="form-group">
                                                <label>{{ __('Type') }}</label>
                                                <select name="deduction[{{ 100 + $loop->index }}][id]" class="form-control ss-new-row-select">
                                                    @foreach($deductions->where('deleted_at', null) as $deduction)
                                                        @if($deduction->name !== 'Transportation Deduction')
                                                            <option
                                                                value="{{ $deduction->id }}"
                                                                data-name="{{ $deduction->name }}"
                                                                data-value="{{ !is_null($deduction->amount) ? $deduction->amount : $deduction->percentage }}"
                                                                data-type="{{ !is_null($deduction->amount) ? 'amount' : 'percentage' }}"
                                                                {{ $rowItem->payroll_setting_id == $deduction->id ? 'selected' : '' }}
                                                            >{{ $deduction->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group ss-amount-wrap" style="{{ !is_null($rowItem->amount) ? '' : 'display:none' }}">
                                                <label>{{ __('Amount') }}</label>
                                                <input type="number" name="deduction[{{ 100 + $loop->index }}][amount]" class="form-control ss-amount-input" value="{{ $rowItem->amount }}" {{ !is_null($rowItem->amount) ? '' : 'disabled' }}>
                                            </div>
                                            <div class="form-group ss-pct-wrap" style="{{ !is_null($rowItem->percentage) ? '' : 'display:none' }}">
                                                <label>{{ __('Percentage') }}</label>
                                                <input type="number" name="deduction[{{ 100 + $loop->index }}][percentage]" class="form-control ss-pct-input" value="{{ $rowItem->percentage }}" {{ !is_null($rowItem->percentage) ? '' : 'disabled' }}>
                                            </div>
                                            <div class="ss-edit-actions">
                                                <button type="button" class="btn-ss-confirm" title="{{ __('Confirm') }}">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button type="button" class="btn-ss-cancel-existing" title="{{ __('Cancel') }}">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            {{-- New Deduction Rows Repeater --}}
                            <div id="deduction-repeater" data-counter="1">

                                {{-- Template row --}}
                                <div class="ss-item-row ss-new-row" data-template data-state="edit" style="display:none">
                                    
                                    {{-- Edit Mode --}}
                                    <div class="ss-row-edit">
                                        <div class="form-group">
                                            <label>{{ __('Type') }}</label>
                                            <select name="deduction[0][id]" class="form-control ss-new-row-select">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($deductions->where('deleted_at', null) as $deduction)
                                                    @if($deduction->name !== 'Transportation Deduction')
                                                        <option
                                                            value="{{ $deduction->id }}"
                                                            data-name="{{ $deduction->name }}"
                                                            data-value="{{ !is_null($deduction->amount) ? $deduction->amount : $deduction->percentage }}"
                                                            data-type="{{ !is_null($deduction->amount) ? 'amount' : 'percentage' }}"
                                                        >{{ $deduction->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group ss-amount-wrap" style="display:none">
                                            <label>{{ __('Amount') }}</label>
                                            <input type="number" name="deduction[0][amount]" class="form-control ss-amount-input" min="1" placeholder="{{ __('Amount') }}" disabled>
                                        </div>
                                        <div class="form-group ss-pct-wrap" style="display:none">
                                            <label>{{ __('Percentage') }}</label>
                                            <input type="number" name="deduction[0][percentage]" class="form-control ss-pct-input" min="0.1" max="100" placeholder="{{ __('%') }}" disabled>
                                        </div>
                                        <div class="ss-edit-actions">
                                            <button type="button" class="btn-ss-confirm" title="{{ __('Confirm') }}">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn-ss-cancel ss-remove-new-row" title="{{ __('Cancel') }}">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Display Mode --}}
                                    <div class="ss-row-display">
                                        <div style="flex:1">
                                            <div class="ss-item-name ss-display-name">---</div>
                                            <span class="ss-item-amount-label ss-display-subtext">---</span>
                                        </div>
                                        <span class="ss-item-amount is-deduction ss-display-value">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                                        <div class="ss-item-actions">
                                            <button type="button" class="btn-ss-edit" title="{{ __('Edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-ss-delete ss-remove-new-row" title="{{ __('Remove') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Add button --}}
                                <div class="ss-add-row">
                                    <button type="button" id="btn-add-deduction-inner" class="btn-ss-add-row is-deduction" style="display:none">
                                        <i class="fa fa-plus"></i> {{ __('add_deduction') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Subtotal --}}
                            <div class="ss-subtotal">
                                <span class="ss-subtotal-label text-uppercase">{{ __('subtotal') }}</span>
                                <span class="ss-subtotal-value is-deduction" id="ss-deduction-subtotal">{{ $schoolSettings['currency_symbol'] ?? '$' }} 0</span>
                            </div>
                        </div>
                    </div>{{-- /Deductions --}}

                </div>{{-- /Allowances + Deductions row --}}

            </div>{{-- /RIGHT COLUMN --}}

        </div>{{-- /row --}}

    </form>

</div>
@endsection

@section('script')
    <script src="{{ asset('assets/js/salary-structure.js') }}"></script>
@endsection
