@extends('layouts.master')

@section('title')
    {{ __('leave') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('leave') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('list') . ' ' . __('leave') }}</h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="" class="filter-menu">{{ __('session_year') }}</label>
                                {!! Form::select('session_year_id', $sessionYear, $current_session_year->id, ['class' => 'form-control', 'id' => 'session_year_id']) !!}
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table"
                            data-url="{{ route('leave.request.show') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                            data-maintain-selected="true" data-export-types='["txt","excel"]'
                            data-query-params="leaveQueryParams" data-toolbar="#toolbar"
                            data-export-options='{ "fileName": "payroll-list-<?= date('d-m-y') ?>"
                            ,"ignoreColumn":["operate"]}' data-show-export="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="user.full_name">{{ __('name') }}</th>
                                    <th scope="col" data-field="from_date">{{ __('from_date') }}</th>
                                    <th scope="col" data-field="to_date">{{ __('to_date') }}</th>
                                    <th scope="col" data-field="reason">{{ __('reason') }}</th>
                                    <th scope="col" data-formatter="leaveStatusFormatter" data-field="status">
                                        {{ __('status') }}</th>
                                    <th data-events="leaveEvents" scope="col" data-field="operate">{{ __('action') }}
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"> {{ __('edit') . ' ' . __('leave') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fa fa-close"></i></span>
                        </button>
                    </div>
                    <form id="formdata" class="edit-form" action="{{ url('leave/status/update') }}" novalidate="novalidate">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="id" id="id">
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('reason', null, ['required',
                                        'placeholder' => __('reason'),
                                        'class' => 'form-control',
                                        'id' => 'edit_reason',
                                        'readonly'
                                    ]) !!}
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('from_date', null, [ 'required', 'class' => 'form-control datepicker-popup datepicker-popup-no-past', 'placeholder' => __('from_date'), 'id' => 'edit_from_date', 'readonly']) !!}
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('to_date', null, [ 'required', 'class' => 'form-control datepicker-popup datepicker-popup-no-past', 'placeholder' => __('to_date'), 'id' => 'edit_to_date', 'readonly']) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                          <input type="radio" class="form-check-input leave-status" name="status" id="optionsRadios2" value="0" checked=""> {{ __('pending') }} <i class="input-helper"></i></label>
                                      </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                          <input type="radio" class="form-check-input leave-status" name="status" id="optionsRadios2" value="1"> {{ __('approved') }} <i class="input-helper"></i></label>
                                      </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                          <input type="radio" class="form-check-input leave-status" name="status" id="optionsRadios2" value="2"> {{ __('rejected') }} <i class="input-helper"></i></label>
                                      </div>
                                </div>
                            </div>
                            
                            
                        </div>
                        <div class="modal-footer">
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                            <button type="button" class="btn btn-light"
                                data-dismiss="modal">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
