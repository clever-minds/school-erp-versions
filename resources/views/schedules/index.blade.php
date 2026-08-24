@extends('layouts.master')

@section('title')
    {{ __('schedule') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_schedule') }}
            </h3>
        </div>

        <div class="row">
            @if (Auth::user()->can('schedule-create'))
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('create_schedule') }}
                            </h4>
                            <form class="create-form pt-3" id="create-form" action="{{ route('schedules.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('date', null, ['placeholder' => __('date'), 'class' => 'datepicker-popup form-control', 'autocomplete' => 'off', 'required']) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('title', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label>{{ __('description') }}</label>
                                        {!! Form::textarea('description', null, ['rows' => '2', 'placeholder' => __('description'), 'class' => 'form-control']) !!}
                                    </div>
                                </div>
                                <button class="btn btn-theme float-right ml-3" type="submit">{{ __('submit') }}</button>
                                <button class="btn btn-secondary float-right" type="reset">{{ __('reset') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if (Auth::user()->can('schedule-list'))
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list_schedule') }}
                            </h4>
                            <table class="table" id="table_list" data-toggle="table" data-url="{{ route('schedules.list') }}"
                                   data-pagination="true" data-search="true" data-side-pagination="server" data-show-refresh="true"
                                   data-show-columns="true" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="no">{{ __('no.') }}</th>
                                        <th data-field="date">{{ __('date') }}</th>
                                        <th data-field="title">{{ __('title') }}</th>
                                        <th data-field="description">{{ __('description') }}</th>
                                        @if (Auth::user()->can('schedule-edit') || Auth::user()->can('schedule-delete'))
                                            <th data-field="operate">{{ __('action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal fade" id="editModal" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('edit_schedule') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
                </div>
                <form id="formdata" class="edit-form" action="{{ url('schedules') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                {!! Form::text('date', null, ['placeholder' => __('date'), 'class' => 'datepicker-popup form-control', 'id' => 'edit-date', 'required']) !!}
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                {!! Form::text('title', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control', 'id' => 'edit-title']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('description') }}</label>
                            {!! Form::textarea('description', null, ['rows' => '2', 'placeholder' => __('description'), 'class' => 'form-control', 'id' => 'edit-description']) !!}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                        <button type="submit" class="btn btn-theme">{{ __('submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.actionEvents = {
            'click .edit-data': function (e, value, row, index) {
                $('#edit-id').val(row.id);
                $('#edit-date').val(row.date);
                $('#edit-title').val(row.title);
                $('#edit-description').val(row.description);
            }
        };

        const sessionStart = "{{ $current_sessionYear->start_date }}";
        const sessionEnd = "{{ $current_sessionYear->end_date }}";
        $('.datepicker-popup').datepicker({
            format: 'yyyy-mm-dd',
            startDate: sessionStart,
            endDate: sessionEnd,
            autoclose: true,
            todayHighlight: true
        });
    </script>
@endsection
