@extends('layouts.master')

@section('title')
    {{ __('Manage Fees')}}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Fees')}}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Create Fees')}}
                        </h4>
                        <form id="create-form" class="pt-3 create-form common-validation-rules" action="{{ route('fees.store') }}" data-success-function="formSuccessFunction" method="POST" novalidate="novalidate">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('name', null, ['required', 'placeholder' => __('name'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                    <label>{{ __('due_date')}} <span class="text-danger">*</span></label>
                                    {{ Form::text('due_date', null, ['class' => 'datepicker-popup form-control', 'placeholder' => __('due_date'), 'required']) }}
                                </div>
                                <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                    <label>{{ __('due_charges')}} <span class="text-danger">*</span> <span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                    {{ Form::number('due_charges', null, ['class' => 'form-control', 'placeholder' => __('due_charges'), 'required', 'min' => 1]) }}
                                </div>
                            </div>

                            <hr>
                            {{-- ---------------------------------------------------------------------------------------------------------------------------------------------------------- --}}
                            {{-- Installment Data --}}
                            <h4 class="card-title">
                                {{ __('Fees Installment') }}
                            </h4>
                            <div class="row mb-4 mt-4">
                                <div class="form-inline col-md-4">
                                    <label>{{__('include_fees_installment')}}</label> <span class="ml-1 text-danger">*</span>
                                    <div class="ml-4 d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="fees_installment" class="fees-installment-toggle" value="1">
                                                {{ __('Enable') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="fees_installment" class="fees-installment-toggle" value="0" checked>
                                                {{ __('Disable') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="fees-installment-content" style="display: none">
                                <div data-repeater-list="installment_data">
                                    <div class="row" data-repeater-item>
                                        {!! Form::hidden('', '', ["id" => "installment-id"]) !!}
                                        <div class="form-group col-lg-12 col-xl-4">
                                            <label>{{ __('installment_name') }} <span class="text-danger">*</span></label>
                                            {{ Form::text('name', null, ['class' => 'form-control installment-name', 'placeholder' => __('installment') . ' ' . __('name'), 'required']) }}
                                        </div>
                                        <div class="form-group col-lg-12 col-xl-4">
                                            <label>{{ __('due_date') }} <span class="text-danger">*</span></label>
                                            {{ Form::text('due_date', null, ['class' => 'datepicker-popup form-control installment-due-date', 'placeholder' => __('due_date'), 'required']) }}
                                        </div>
                                        <div class="form-group col-lg-12 col-xl-3">
                                            <label>{{ __('due_charges') }} <span class="text-danger">*</span><span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                            {!! Form::text("due_charges",null, ["class" => "installment-due-charges form-control" , "placeholder" => trans('due_charges') , "required" => true , "data-convert" => "number"]) !!}
                                        </div>
                                        <div class="form-group col-lg-12 col-xl-1 mt-4">
                                            <button type="button" class="btn btn-inverse-danger btn-icon remove-installment-fee" data-repeater-delete>
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 pl-0 mb-4 mt-4">
                                    <button class="btn btn-dark btn-sm" type="button" data-repeater-create>
                                        <i class="fa fa-plus-circle fa-3x mr-2" aria-hidden="true"></i>
                                        {{__('Add New Data')}}
                                    </button>
                                </div>
                            </div>

                            {{-- ---------------------------------------------------------------------------------------------------------------------------------------------------------- --}}
                            <hr>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
             <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('List Fees')}}
                        </h4>
                        <div class="row">
                            <div class="col-12 text-right">
                                <b><a href="#" class="table-list-type active mr-2" data-value="All">{{__('all')}}</a></b> | <a href="#" class="ml-2 table-list-type" data-value="Trashed">{{__("Trashed")}}</a>
                            </div>
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table" data-url="{{ route('fees.show',1) }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{__('id')}}</th>
                                        <th scope="col" data-field="no">{{__('no.')}}</th>
                                        <th scope="col" data-field="name" data-sortable="true">{{__('name')}}</th>
                                        <th scope="col" data-field="due_date" data-sortable="true">{{__('due_date')}}</th>
                                        <th scope="col" data-field="due_charges_view" data-align="center">{{__('due_charges')}}</th>
                                        <th scope="col" data-field="include_fee_installments" data-align="center" data-sortable="true" data-formatter="yesAndNoStatusFormatter">{{__('include_fee_installments')}}</th>
                                        <th scope="col" data-events="feesEvents" data-field="operate">{{__('action')}}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Model --}}
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{__('Edit Fees')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="edit-form" class="pt-3 edit-form" action="{{ url('fees-type') }}">
                    <input type="hidden" name="edit_id" id="edit-id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                {!! Form::text('edit_name', null, ['required', 'placeholder' => __('name'), 'id' => 'edit-name', 'class' => 'form-control']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                <label>{{ __('due_date')}} <span class="text-danger">*</span></label>
                                {{ Form::text('edit_due_date', null, ['class' => 'datepicker-popup form-control', 'id' => 'edit-due-date', 'placeholder' => __('due_date'), 'required']) }}
                            </div>
                            <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                <label>{{ __('due_charges')}} <span class="text-danger">*</span> <span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                {{ Form::number('edit_due_charges', null, ['class' => 'form-control', 'id' => 'edit-due-charges', 'placeholder' => __('due_charges'), 'required', 'min' => 1]) }}
                            </div>
                        </div>
                        <div class="edit-fees-installment-content" style="display: none">
                            <hr>
                            <h4 class="card-title">
                                {{ __('fees').' '.__('installment') }}
                            </h4>
                            <div data-repeater-list="edit_installment_data" class="mb-4 mt-4">
                                <div class="row" data-repeater-item>
                                    {!! Form::hidden('id', null,['class' => 'installment-id']) !!}
                                    <div class="form-group col-lg-12 col-xl-4">
                                        <label>{{ __('installment_name') }} <span class="text-danger">*</span></label>
                                        {{ Form::text('name', null, ['class' => 'form-control edit-installment-name', 'placeholder' => __('installment') . ' ' . __('name'), 'required']) }}
                                    </div>
                                    <div class="form-group col-lg-12 col-xl-4">
                                        <label>{{ __('due_date') }} <span class="text-danger">*</span></label>
                                        {{ Form::text('due_date', null, ['class' => 'datepicker-popup form-control edit-installment-due-date', 'placeholder' => __('due_date'), 'required']) }}
                                    </div>
                                    <div class="form-group col-lg-12 col-xl-3">
                                        <label>{{ __('due_charges') }} <span class="text-danger">*</span><span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                        {!! Form::text("due_charges",null, ["class" => "edit-installment-due-charges form-control" , "placeholder" => trans('due_charges') , "required" => true , "data-convert" => "number"]) !!}
                                    </div>
                                    <div class="form-group col-lg-12 col-xl-1 mt-4">
                                        <button type="button" class="btn btn-inverse-danger btn-icon remove-edit-installment-fee" data-repeater-delete>
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 pl-0 mb-4 mt-4">
                                <button class="btn btn-dark btn-sm" type="button" data-repeater-create>
                                    <i class="fa fa-plus-circle fa-3x mr-2" aria-hidden="true"></i>
                                    {{__('add_new_data')}}
                                </button>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection
@section('script')
    <script>
        const formSuccessFunction = (response) => {
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    </script>
@endsection
