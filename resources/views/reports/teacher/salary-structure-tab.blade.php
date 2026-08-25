{{-- ================================ --}}
{{-- SALARY STRUCTURE (Modern UI)   --}}
{{-- ================================ --}}

<div class="card shadow-sm border-0 tvr-card mb-4">
    <div class="card-body p-4">
        <h5 class="tvr-card-title mb-4">{{ __('salary_structure') }}</h5>

        {{-- SUMMARY GRID --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">{{ __('basic_salary') }}</span>
                    <span class="font-weight-bold text-dark">{{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($salary_structure['basic_salary'], 2) }}</span>
                </div>
                <hr class="my-2 tvr-divider">
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">{{ __('total_allowance') }}</span>
                    <span class="font-weight-bold text-dark">{{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($salary_structure['total_allowance'], 2) }}</span>
                </div>
                <hr class="my-2 tvr-divider">
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">{{ __('total_deduction') }}</span>
                    <span class="font-weight-bold text-dark">{{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($salary_structure['total_deduction'], 2) }}</span>
                </div>
                <hr class="my-2 tvr-divider">
            </div>
        </div>

        {{-- NET SALARY BAR --}}
        <div class="d-flex justify-content-between align-items-center rounded p-3 mb-2" style="background-color: #e8fdf0;">
            <span class="font-weight-bold" style="color: #10b981; font-size: 15px;">{{ __('net_salary') }}</span>
            <span class="font-weight-bold" style="color: #10b981; font-size: 18px;">{{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($salary_structure['net_salary'], 2) }}</span>
        </div>
    </div>
</div>

<div class="row">
    {{-- ========================== --}}
    {{-- ALLOWANCES LIST            --}}
    {{-- ========================== --}}
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 tvr-card h-100">
            <div class="card-body p-4">
                <h6 class="font-weight-bold text-dark mb-4">{{ __('allowance_details') }}</h6>

                @if(count($salary_structure['allowances']))
                    <div class="table-responsive">
                        <table class="table mb-0 tvr-table">
                            <thead>
                                <tr>
                                    <th>{{ __('allowance_name') }}</th>
                                    <th class="text-right">{{ __('amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salary_structure['allowances'] as $item)
                                    <tr>
                                        <td class="text-dark font-weight-medium">{{ $item['name'] }}</td>
                                        <td class="text-right font-weight-bold text-success">+ {{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($item['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <p class="mb-0" style="font-size: 13px;">{{ __('no_allowances') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================== --}}
    {{-- DEDUCTIONS LIST            --}}
    {{-- ========================== --}}
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 tvr-card h-100">
            <div class="card-body p-4">
                <h6 class="font-weight-bold text-dark mb-4">{{ __('deduction_details') }}</h6>

                @if(count($salary_structure['deductions']))
                    <div class="table-responsive">
                        <table class="table mb-0 tvr-table">
                            <thead>
                                <tr>
                                    <th>{{ __('deduction_name') }}</th>
                                    <th class="text-right">{{ __('amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salary_structure['deductions'] as $item)
                                    <tr>
                                        <td class="text-dark font-weight-medium">{{ $item['name'] }}</td>
                                        <td class="text-right font-weight-bold text-danger">- {{ env('CURRENCY_SYMBOL', '₹') }} {{ number_format($item['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <p class="mb-0" style="font-size: 13px;">{{ __('no_deductions') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>