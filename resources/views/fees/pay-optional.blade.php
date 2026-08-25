@extends('layouts.master')

@section('title')
    {{ __('Pay Optional Fees') }}
@endsection

@section('css')
    <style>
        :root {
            --primary-color: var(--theme-color);
            --header-bg: var(--theme-color);
            --header-text: #ffffff;
            --bg-light: #f3f4f6;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
            --transition-speed: 0.3s;
            --danger-color: #ef4444;
            --danger-soft: #fef2f2;
        }

        
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="payment-card-container">
            <div class="payment-card">
                {{-- Distinct Header --}}
                <div class="payment-header">
                    <a href="{{ route('fees.paid.index') }}" class="back-btn" title="Back">
                        <i class="fa fa-times fa-lg"></i>
                    </a>
                    <h4>{{ $student->full_name }}</h4>
                    <p>{{ $student->student->class_section->full_name }} | {{ __('Student') }}</p>
                </div>

                <div class="payment-body">
                    <form class="create-form form-validation" method="post" action="{{ route('fees.optional.store') }}" novalidate="novalidate" data-success-function="formSuccessFunction">
                        {{-- Hidden Fields --}}
                        <input type="hidden" name="fees_id" id="optional-fees-id" value="{{$fees->id}}"/>
                        <input type="hidden" name="student_id" id="student-id" value="{{$student->id}}"/>
                        <input type="hidden" name="class_id" id="class-id" value="{{$student->student->class_section->class_id}}"/>

                        {{-- Date Field --}}
                        <div class="form-group mb-4">
                            <label class="form-section-title" for="payment-date">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input id="payment-date" type="text" name="date" class="datepicker-popup paid-date form-control-custom" placeholder="{{ __('Select Date') }}" autocomplete="off" required>
                        </div>

                        {{-- Fee Selection --}}
                        <div class="mb-4">
                            <label class="form-section-title">{{ __('Optional Fees') }}</label>
                            
                            <ul class="fee-list">
                                @foreach($optionalFeesData as $key =>$optionalFee)
                                    <li class="fee-item" onclick="toggleCheckbox('optional-{{ $optionalFee->id }}')">
                                         <div class="fee-info">
                                             {{-- Checkbox checks: Always render, but hide/disable if paid --}}
                                             @php
                                                 $isPaid = count($optionalFee->optional_fees_paid) > 0;
                                             @endphp
                                             
                                             <input type="checkbox" 
                                                    class="custom-checkbox optional-fee-payment {{ $isPaid ? 'd-none' : '' }}" 
                                                    id="optional-{{ $optionalFee->id }}" 
                                                    data-amount="{{ $optionalFee->amount }}" 
                                                    name="fees_class_type[{{ $key }}][id]" 
                                                    value="{{ $optionalFee->id }}" 
                                                    onclick="event.stopPropagation()"
                                                    {{ $isPaid ? 'disabled' : '' }}>

                                             @if($isPaid)
                                                 {{-- Removed 'text-danger' class to use custom 'remove-btn' style --}}
                                                 <span data-id="{{ $optionalFee->optional_fees_paid[0]['id']}}" class="remove-paid-optional-fees remove-btn" onclick="removeFee(event, this)">
                                                     <i class="fa fa-trash-o"></i> {{ __('Remove') }}
                                                 </span>
                                             @endif
                                             <label for="optional-{{ $optionalFee->id }}" class="fee-name mb-0" style="cursor: inherit;" onclick="event.stopPropagation()">{{$optionalFee->fees_type_name}}</label>
                                         </div>
                                         <div class="fee-price">
                                            {{ $currencySymbol }}
                                             {{$optionalFee->amount}}
                                             {!! Form::hidden('fees_class_type['.$key.'][amount]', $optionalFee->amount) !!}
                                         </div>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Empty State Message --}}
                            <div class="empty-state" id="no-fees-message">
                                <i class="fa fa-clipboard fa-2x mb-3"></i>
                                <p>{{ __('No optional fees available.') }}</p>
                            </div>
                        </div>

                        {{-- Total Amount (Hidden by default) --}}
                        <div id="optional-total-amount-to-pay" class="total-section" style="display: none">
                            <span class="total-label">{{__("Total Amount To Pay")}}</span>
                            <span class="total-amount" id="optional-total-amount"></span>
                            {!! Form::hidden('total_amount',null, ["id" => "form-total-optional-amount"]) !!}
                        </div>

                        <hr style="border-top: 1px dashed #e5e7eb; margin: 2rem 0;">

                        {{-- Payment Mode --}}
                        <div class="mb-4">
                             <label class="form-section-title">{{ __('Payment Mode') }} <span class="text-danger">*</span></label>
                             <div class="mode-selection">
                                 <div class="mode-tile active" id="mode-cash" onclick="selectMode('cash')">
                                     <input type="radio" name="mode" class="cash-compulsory-mode mode" value="1" checked id="radio-cash">
                                     <span>{{ __('Cash') }}</span>
                                 </div>
                                 <div class="mode-tile" id="mode-cheque" onclick="selectMode('cheque')">
                                     <input type="radio" name="mode" class="cheque-compulsory-mode mode" value="2" id="radio-cheque">
                                     <span>{{ __('Cheque') }}</span>
                                 </div>
                             </div>
                        </div>

                        {{-- Cheque Fields --}}
                        <div class="cheque-no-container mb-4" style="display: none">
                            <label class="form-section-title" for="cheque_no">{{ __('Cheque Number') }} <span class="text-danger">*</span></label>
                            <input type="number" id="cheque_no" name="cheque_no" placeholder="{{ __('Enter Cheque Number') }}" class="form-control-custom cheque-no" required/>
                        </div>

                        {{-- Submit Button --}}
                        <input class="btn-pay" type="submit" id="pay-button" disabled value="{{ __('Pay Now') }}" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Init Datepicker
        $('#payment-date').datepicker({
            format: "dd-mm-yyyy",
            rtl: isRTL()
        }).datepicker("setDate", 'now');

        // Check if there are fees on load
        checkEmptyState();

        // Logic for total calculation
        let totalAmount = 0;
        $('.optional-fee-payment').on('change', function () {
            updateTotal();
        });

        function toggleCheckbox(id) {
            let checkbox = $('#' + id);
            if (!checkbox.length || checkbox.is(':disabled')) return; // Don't toggle if disabled (paid)
            checkbox.prop('checked', !checkbox.prop('checked'));
            updateTotal();
        }

        function updateTotal() {
            totalAmount = 0;
            $('.optional-fee-payment').each(function() {
                if ($(this).is(':checked') && !$(this).is(':disabled')) {
                    totalAmount += parseFloat($(this).data("amount"));
                    $(this).closest('.fee-item').addClass('selected');
                } else {
                    $(this).closest('.fee-item').removeClass('selected');
                }
            });

            if (totalAmount > 0) {
                $('#pay-button').removeAttr('disabled');
                $('#optional-total-amount-to-pay').slideDown().css('display', 'flex'); 
                $('#optional-total-amount').html('{{ $currencySymbol }}' + totalAmount);
                $('#form-total-optional-amount').val(totalAmount);
            } else {
                $('#pay-button').attr('disabled', true);
                $('#optional-total-amount-to-pay').slideUp();
                $('#optional-total-amount').html('{{ $currencySymbol }}' + totalAmount);
                $('#form-total-optional-amount').val(totalAmount);
            }
        }

        // Remove Fee Functionality
        function removeFee(e, element) {
            e.stopPropagation(); // Prevent row click
            e.preventDefault();

            let id = $(element).data('id');
            let row = $(element).closest('.fee-item');
            let removeBtn = $(element);
            let checkbox = row.find('.optional-fee-payment');

            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('You want to remove this fees?') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: "{{ __('Yes, remove it!') }}"
            })
            .then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('fees.paid.remove.optional.fees', '') }}/" + id,
                        type: "DELETE",
                        success: function (response) {
                            if (!response.error) {
                                // Dynamic UI Reset instead of row removal
                                removeBtn.fadeOut(300, function() {
                                    $(this).remove(); // Remove button from DOM
                                    
                                    // Reset checkbox state
                                    checkbox.removeClass('d-none')
                                            .prop('disabled', false)
                                            .prop('checked', false)
                                            .hide()
                                            .fadeIn(300);
                                    
                                    // Recalculate total (shouldn't change, but ensuring consistency)
                                    updateTotal();
                                });

                                $.toast({
                                    text: response.message,
                                    showHideTransition: 'slide',
                                    icon: 'success',
                                    loaderBg: '#f96868',
                                    position: 'top-right'
                                });
                            } else {
                                $.toast({
                                    text: response.message,
                                    showHideTransition: 'slide',
                                    icon: 'error',
                                    loaderBg: '#f2a654',
                                    position: 'top-right'
                                });
                            }
                        },
                        error: function (xhr) {
                            $.toast({
                                text: "{{ __('Something went wrong') }}",
                                showHideTransition: 'slide',
                                icon: 'error',
                                loaderBg: '#f2a654',
                                position: 'top-right'
                            });
                        }
                    });
                }
            });
        }

        function checkEmptyState() {
            if ($('.fee-item').length === 0) {
                $('#no-fees-message').show();
            } else {
                $('#no-fees-message').hide();
            }
        }

        // Mode Selection Visuals
        function selectMode(mode) {
            $('.mode-tile').removeClass('active');
            $('#mode-' + mode).addClass('active');

            if(mode === 'cheque') {
                 $('#radio-cheque').prop('checked', true).trigger('change');
                 $('.cheque-no-container').slideDown();
            } else {
                 $('#radio-cash').prop('checked', true).trigger('change');
                 $('.cheque-no-container').slideUp();
            }
        }

        $('input[name="mode"]').on('change', function() {
            if ($(this).val() == 2) { 
                $('.cheque-no-container').show();
            } else {
                $('.cheque-no-container').hide();
            }
        });

        function formSuccessFunction() {
            setTimeout(function () {
                window.location.reload();
            }, 1000)
        }
    </script>
@endsection
