@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('fees') }} {{ __('paid') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('fees') }} {{ __('paid') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div id="toolbar" class="row">
                            <div class="col">
                                <label for="filter_class_id text-sm">
                                    {{ __('Fees') }}
                                </label>
                                <select name="filter_fees_id" id="filter_fees_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($fees as $key => $fee)
                                        <option value="{{ $fee->id }}" {{ $key == 0 ? 'selected' : "" }}>{{ $fee->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label for="filter_class_id text-sm">
                                    {{ __('Classes') }}
                                </label>
                                <select name="filter_class_id" id="filter_class_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($fees as $fee)
                                        @foreach ($fee->fees_class as $class)
                                            <option value="{{ $class->class->id }}" data-feesId="{{ $class->fees_id }}"> {{ $class->class->full_name }} </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label for="filter_session_year_id text-sm"> {{ __('Session Years') }} </label>
                                <select name="filter_session_year_id" id="filter_session_year_id" class="form-control">
                                    <option value="">{{ __('Current Session Year') }}</option>
                                    @foreach ($session_year_all as $session_year)
                                        <option value="{{ $session_year->id }}"> {{ $session_year->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table"data-url="{{ route('fees.paid.list', 1) }}" data-click-to-select="true"data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'data-export-options='{ "fileName": "{{ __('fees') }}-{{ __('paid') }}-{{ __('list') }}-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}' data-show-export="true"data-query-params="feesPaidListQueryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false" data-align="center">{{ __('id') }}</th>
                                    <th scope="col" data-field="student_id" data-sortable="false" data-visible="false" data-align="center">{{ __('Student Id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="false" data-align="center">{{ __('no.') }}</th>
                                    <th scope="col" data-field="student_name" data-sortable="false" data-align="center">{{ __('Student Name') }}</th>
                                    <th scope="col" data-field="class_name" data-sortable="false" data-align="center">{{ __('Class') }}</th>
                                    <th scope="col" data-field="total_fees" data-sortable="false" data-align="center">{{ __('Total Amount') }}</th>
                                    <th scope="col" data-field="fees_status" data-sortable="false" data-formatter="feesPaidStatusFormatter" data-align="center">{{ __('Fees Status')}}</th>
                                    <th scope="col" data-field="fees_paid_on" data-sortable="false" data-align="center">{{ __('Date') }}</th>
                                    <th scope="col" data-field="session_year_name" data-sortable="false" data-align="center">{{ __('Session Years') }}</th>
                                    <th scope="col" data-field="operate" data-sortable="false" data-events="feesPaidEvents" data-align="center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            {{--  Compulsory Fee Modal  --}}
            <div class="modal fade" id="compulsoryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-m" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="exampleModalLabel">
                                {{ __('Pay Compulsory Fees') }}
                            </h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 create-form form-validation" method="post" action="{{ route('fees.compulsory-paid.store') }}" novalidate="novalidate" data-success-function="formSuccessFunction">
                            <input type="hidden" name="fees_id" id="compulsory-fees-id" value="" />
                            <input type="hidden" name="student_id" id="student-id" value="" />
                            <input type="hidden" name="class_id" id="class-id" value="" />
                            <input type="hidden" name="installment_mode" id="installment-mode" value="0" />
                            <input type="hidden" name="total_amount" id="total-amount" value="" />
                            <input type="hidden" name="is_fully_paid" id="is-fully-paid" value="" />
                            <h4 class="ml-4">
                                <span class="student-name"></span>
                            </h4>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="date" class="datepicker-popup paid-date form-control"
                                        placeholder="{{ __('date') }}" autocomplete="off" required>
                                </div>
                                <div class="compulsory-div" style="display: none">
                                    <hr>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <div class="compulsory-fees-content"></div>
                                    </div>
                                    <hr>
                                </div>
                                <div class="row mode-container">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('mode') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="mode" class="cash-compulsory-mode  mode" value="1" checked>
                                                    {{ __('cash') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="mode" class="cheque-compulsory-mode mode" value="2">
                                                    {{ __('cheque') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group cheque-no-container" style="display: none">
                                    <label>{{ __('cheque_no') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="cheque_no" placeholder="{{ __('cheque_no') }}" class="form-control cheque-no" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme compulsory-fees-payment" type="submit" value={{ __('pay') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>



            {{--  Optional Fees Modal   --}}
            <div class="modal fade" id="optionalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-m" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="exampleModalLabel">
                                {{ __('pay_optional_fees')}}
                            </h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 create-form form-validation" method="post" action="{{ route('fees.optional-paid.store') }}" novalidate="novalidate" data-success-function="formSuccessFunction">
                            <input type="hidden" name="fees_id" id="optional_fees_id" value="" />
                            <input type="hidden" name="student_id" id="optional_student_id" value="" />
                            <input type="hidden" name="class_id" id="optional_class_id" value="" />
                            <h4 class="ml-4">
                                <span class="student_name"></span>
                            </h4>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="date" class="datepicker-popup form-control current-date"
                                        placeholder="{{ __('date') }}" autocomplete="off" required>
                                </div>
                                <div class="optional_div" style="display: none">
                                    <hr>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <div class="optional_fees_content"></div>
                                    </div>
                                    <hr>
                                </div>
                                <div class="row mode-container">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('mode') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="mode" class="mode cash_mode" value="1" checked>
                                                    {{ __('cash') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="mode" class="mode cheque_mode" value="2">
                                                    {{ __('cheque') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group cheque-no-container" style="display: none">
                                    <label>{{ __('cheque_no') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="cheque_no" placeholder="{{ __('cheque_no') }}" class="form-control cheque-no" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme optional_fees_payment" type="submit" value={{ __('pay') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $('#filter_fees_id').on('change',function(e){
            let $this = $(this)
            $("#filter_class_id").val("").removeAttr('disabled').show();
            $("#filter_class_id").find('option[data]').hide();
            if ($("#filter_class_id").find('option[data-feesId="' + $this.val() + '"]').length) {
                $("#filter_class_id").find('option[data-feesId="' + $this.val() + '"]').show().trigger('change');
            } else {
                $("#filter_class_id").val("").attr('disabled', true).show().trigger('change');
            }
        })



        const formSuccessFunction = (response) => {
            if (!response.error) {
                setTimeout(() => {
                    $('.modal').modal('hide')
                }, 1000);
            }
        }
    </script>
@endsection
