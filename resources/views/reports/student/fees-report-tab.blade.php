{{-- Student Fees Report Tab --}}
<div class="svr-fees-panel">
    <div class="svr-fees-panel__header">
        <h6 class="svr-fees-panel__title">{{ __('fees_payment_history') }}</h6>
    </div>

    @if(isset($studentFees) && count($studentFees) > 0)
        <div class="table-responsive">
            <table class="svr-fees-table">
                <thead>
                    <tr>
                        <th>{{ __('fees_name') }}</th>
                        <th>{{ __('amount') }}</th>
                        <th>{{ __('due_date') }}</th>
                        <th>{{ __('paid_amount') }}</th>
                        <th>{{ __('status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($studentFees as $fee)
                        <tr>
                            <td>
                                <div class="svr-fee-name">{{ $fee->fees->name ?? '-' }}</div>
                                @if(isset($fee->fees->fees_class_type) && count($fee->fees->fees_class_type) > 0)
                                    <div class="svr-fee-type">
                                        ({{ implode(', ', $fee->fees->fees_class_type->pluck('fees_type_name')->toArray()) }})
                                    </div>
                                @endif
                            </td>
                            <td>{{ number_format($fee->amount ?? 0, 2) }}</td>
                            <td>{{ $fee->fees->due_date ?? '-' }}</td>
                            <td>
                                @php
                                    $paidAmount = 0;
                                    $dueCharge  = 0;
                                    if(isset($fee->compulsory_fee) && count($fee->compulsory_fee) > 0) {
                                        foreach($fee->compulsory_fee as $cf) {
                                            $paidAmount += $cf->amount ?? 0;
                                            $dueCharge  += $cf->due_charges ?? 0;
                                        }
                                    }
                                    // Also include optional fee amounts
                                    if(isset($fee->optional_fee) && count($fee->optional_fee) > 0) {
                                        foreach($fee->optional_fee as $of) {
                                            $paidAmount += $of->amount ?? 0;
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center">
                                    {{ number_format($paidAmount, 2) }}
                                    @if(isset($fee->optional_fee) && count($fee->optional_fee) > 0)
                                        <button type="button" class="btn btn-sm text-theme p-0 ml-2 border-0 bg-transparent" data-toggle="modal" data-target="#optionalFeesModal-{{ $fee->id }}" title="{{ __('optional_fees_details') }}">
                                            <i class="fa fa-info-circle"></i>
                                        </button>
                                    @endif
                                </div>
                                @if($dueCharge > 0)
                                    <div style="font-size:11px; color:#dc2626;">+({{ number_format($dueCharge, 2) }})</div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $status = $fee->status ?? 'unpaid';
                                    $badgeClass = match($status) {
                                        'paid'    => 'svr-fee-badge--paid',
                                        'partial' => 'svr-fee-badge--partial',
                                        'overdue' => 'svr-fee-badge--overdue',
                                        default   => 'svr-fee-badge--unpaid',
                                    };
                                @endphp
                                <span class="svr-fee-badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                            </td>
                        </tr>


                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Render Optional Fees Modals outside the primary .table-responsive to prevent overflow clipping --}}
        @foreach($studentFees as $fee)
            @if(isset($fee->optional_fee) && count($fee->optional_fee) > 0)
                <div class="modal fade" id="optionalFeesModal-{{ $fee->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content shadow-lg border-0">
                            {{-- Header with dark background and icon --}}
                            <div class="modal-header bg-dark text-white py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-2 mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa fa-ticket text-white"></i>
                                    </div>
                                    <h5 class="modal-title mb-0 font-weight-bold">{{ __('optional_fees_details') }}</h5>
                                </div>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0">#</th>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0">{{ __('date') }}</th>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0">{{ __('name') }}</th>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0">{{ __('payment_mode') }}</th>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0">{{ __('cheque_no') }}</th>
                                                <th class="text-uppercase small font-weight-bold text-muted border-0 text-right">{{ __('amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($fee->optional_fee as $index => $of)
                                                <tr>
                                                    <td class="align-middle text-muted">{{ $index + 1 }}</td>
                                                    <td class="align-middle text-muted">
                                                        <i class="fa fa-calendar-o mr-1"></i>
                                                        {{ $of->date ? $of->date : '-' }}
                                                    </td>
                                                    <td class="align-middle font-weight-bold text-dark">
                                                        {{ $of->fees_class_type->fees_type_name ?? '-' }}
                                                    </td>
                                                    <td class="align-middle">
                                                        @if(isset($of->mode))
                                                            <span class="badge badge-pill badge-success px-3 py-2" style="background-color: #dcfce7; color: #166534; font-weight: 500;">
                                                                {{ $of->mode }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="align-middle text-muted">{{ $of->cheque_no ?? '-' }}</td>
                                                    <td class="align-middle font-weight-bold text-dark text-right" style="font-size: 1.1rem;">
                                                        ₹{{ number_format($of->amount ?? 0, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Total Row stylized --}}
                                <div class="d-flex justify-content-end align-items-center p-4 border-top">
                                    <div class="text-uppercase small font-weight-bold text-muted mr-4">{{ __('total_amount') }}</div>
                                    <div class="h4 mb-0 font-weight-bold text-dark">
                                        ₹{{ number_format($fee->optional_fee->sum('amount'), 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer border-0 pb-4">
                                <button type="button" class="btn btn-dark px-4 font-weight-bold" data-dismiss="modal" style="background-color: #1a3b5d; border-color: #1a3b5d;">
                                    {{ __('close') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <div class="svr-no-data">
            <i class="fa fa-credit-card fa-2x mb-2 d-block"></i>
            {{ __('no_fees_records_found_for_this_student') }}
        </div>
    @endif
</div>
