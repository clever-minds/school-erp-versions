@extends('layouts.master')

@section('title')
{{ __('holiday') }}
@endsection

@section('content')
<div class="content-wrapper">

    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('holiday') }}
        </h3>
    </div>

    <div class="row">

        {{-- CREATE HOLIDAY --}}
        @if (Auth::user()->can('holiday-create'))
        <div class="col-lg-12 grid-margin stretch-card">

            <div class="card">
                <div class="card-body">

                    <h4 class="card-title">
                        {{ __('create') . ' ' . __('holiday') }}
                    </h4>

                    <form class="create-form pt-3" id="create-form" action="{{route('holiday.store')}}" method="POST">
                        @csrf

                        <div class="row">

                            <div class="form-group col-md-6">
                                <label>Holiday Type <span class="text-danger">*</span></label>

                                <select name="type" id="holiday-type" class="form-control" required>
                                    <option value="holiday">Holiday</option>
                                    <option value="saturday_all">All Saturday Off</option>
                                    <option value="saturday_1_3">1st & 3rd Saturday Off</option>
                                    <option value="saturday_2_4">2nd & 4th Saturday Off</option>
                                </select>
                            </div>

                            <div class="form-group col-md-6 date-box">
                                <label>{{ __('date') }} <span class="text-danger">*</span></label>

                                {!! Form::text('date', null, [
                                'placeholder' => __('date'),
                                'class' => 'form-control',
                                'autocomplete' => 'off',
                                'id' => 'date-field'
                                ]) !!}
                            </div>

                        </div>


                        <div class="row">

                            <div class="form-group col-md-6">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>

                                {!! Form::text('title', null, [
                                'required',
                                'placeholder' => __('title'),
                                'class' => 'form-control'
                                ]) !!}
                            </div>

                            <div class="form-group col-md-6 class-box">

                                <label>Select Classes</label>

                                <select name="class_ids[]" id="class_ids" class="form-control select2" multiple>

                                    @foreach($classes as $class)
                                    <option value="{{ $class->id }}">
                                        {{ $class->name }}
                                    </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        <div class="row">

                            <div class="form-group col-md-12">

                                <label>{{ __('description') }}</label>

                                {!! Form::textarea('description', null, [
                                'rows' => '2',
                                'placeholder' => __('description'),
                                'class' => 'form-control'
                                ]) !!}

                            </div>

                        </div>


                        <button class="btn btn-theme float-right ml-3" type="submit">
                            {{ __('submit') }}
                        </button>

                        <button class="btn btn-secondary float-right" type="reset">
                            {{ __('reset') }}
                        </button>

                    </form>

                </div>
            </div>

        </div>
        @endif



        {{-- HOLIDAY LIST --}}
        @if (Auth::user()->can('holiday-list'))

        <div class="col-lg-12 grid-margin stretch-card">

            <div class="card">

                <div class="card-body">

                    <h4 class="card-title">
                        {{ __('list') . ' ' . __('holiday') }}
                    </h4>

                    {{-- FILTER --}}
                    <div class="row" id="toolbar">

                        <div class="form-group col-md-3">

                            <label>{{__("session_year")}}</label>

                            <select name="filter_session_year_id" id="filter_session_year_id" class="form-control">

                                @foreach ($sessionYears as $sessionYear)

                                <option value="{{ $sessionYear->id }}" {{$sessionYear->default == 1 ? "selected" : ""}}>
                                    {{ $sessionYear->name }}
                                </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="form-group col-md-3">

                            <label>{{__("month")}}</label>

                            {!! Form::select('month', ['0' => 'All'] + $months, null, [
                            'class' => 'form-control',
                            'id' => 'filter_month'
                            ]) !!}

                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="row">
                        <div class="col-12">

                            <table
                                class="table"
                                id="table_list"
                                data-toggle="table"
                                data-url="{{ route('holiday.show', 1) }}"
                                data-pagination="true"
                                data-search="true"
                                data-side-pagination="server"
                                data-page-list="[5,10,20,50,100]"
                                data-toolbar="#toolbar"
                                data-show-refresh="true"
                                data-show-columns="true"
                                data-mobile-responsive="true"
                                data-sort-name="id"
                                data-sort-order="desc"
                                data-query-params="holidayQueryParams">

                                <thead>

                                    <tr>

                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>

                                        <th data-field="no">{{ __('no.') }}</th>

                                        <th data-field="date" data-width="150">{{ __('date') }}</th>

                                        <th data-field="title">{{ __('title') }}</th>
                                        <th data-field="holiday_type">{{ __('Holiday Type') }}</th>
                                          <th data-field="class">{{ __('Class') }}</th>
                                        <th data-field="description"
                                            data-formatter="descriptionFormatter"
                                            data-events="tableDescriptionEvents">
                                            {{ __('description') }}
                                        </th>

                                        @if (Auth::user()->can('holiday-edit') || Auth::user()->can('holiday-delete'))

                                        <th data-field="operate"
                                            data-events="holidayEvents"
                                            data-width="150">
                                            {{ __('action') }}
                                        </th>

                                        @endif

                                    </tr>

                                </thead>

                            </table>

                        </div>
                    </div>

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

                <h5 class="modal-title">Edit Holiday</h5>

                <button type="button" class="close" data-dismiss="modal">
                    <i class="fa fa-close"></i>
                </button>

            </div>


         <form id="formdata" class="edit-form" action="{{url('holiday')}}" novalidate="novalidate">

                @csrf

                <div class="modal-body">

                    <input type="hidden" name="id" id="edit-id">


                    <div class="row">

                        <div class="form-group col-md-6">

                            <label>Holiday Type</label>

                            <select name="type" id="edit-type" class="form-control">

                                <option value="holiday"> Holiday</option>
                                <option value="saturday_all">All Saturday Off</option>
                                <option value="saturday_1_3">1st & 3rd Saturday Off</option>
                                <option value="saturday_2_4">2nd & 4th Saturday Off</option>

                            </select>

                        </div>


                        <div class="form-group col-md-6 edit-date-box">

                            <label>Date</label>

                            <input type="text"
                                name="date"
                                id="edit-date"
                                class="datepicker-popup form-control">

                        </div>

                    </div>


                    <div class="form-group edit-class-box">

                        <label>Select Classes</label>

                        <select name="class_ids[]" id="edit-class-ids" class="form-control select2" multiple>

                            @foreach($classes as $class)

                            <option value="{{ $class->id }}">
                                {{ $class->name }}
                            </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Title</label>

                        <input type="text"
                            name="title"
                            id="edit-title"
                            class="form-control">

                    </div>


                    <div class="form-group">

                        <label>Description</label>

                        <textarea name="description"
                            id="edit-description"
                            class="form-control"
                            rows="2"></textarea>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-theme">
                        Submit
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection



@section('js')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>

$(document).ready(function () {

    $('.select2').select2({
        placeholder: "Select Classes",
        allowClear: true
    });


    // DATE RANGE PICKER
    const sessionStart = moment("{{ $current_sessionYear->start_date }}");
    const sessionEnd = moment("{{ $current_sessionYear->end_date }}");

    $('#date-field').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        },
        minDate: sessionStart,
        maxDate: sessionEnd,
        autoUpdateInput: false
    });

    $('#date-field').on('apply.daterangepicker', function(ev, picker) {
        if (picker.startDate.format('YYYY-MM-DD') === picker.endDate.format('YYYY-MM-DD')) {
            $(this).val(picker.startDate.format('YYYY-MM-DD'));
        } else {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        }
    });

    $('#date-field').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // EDIT DATEPICKER (Date Range)
    $('#edit-date').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        },
        minDate: sessionStart,
        maxDate: sessionEnd,
        autoUpdateInput: false
    });

    $('#edit-date').on('apply.daterangepicker', function(ev, picker) {
        if (picker.startDate.format('YYYY-MM-DD') === picker.endDate.format('YYYY-MM-DD')) {
            $(this).val(picker.startDate.format('YYYY-MM-DD'));
        } else {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        }
    });

    $('#edit-date').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });


    // CREATE FORM TOGGLE
    function toggleCreateFields() {

        let type = $('#holiday-type').val();

        if(type === 'holiday') {

            $('.date-box').show();
            $('#date-field').attr('required', true);
            $('#class_ids').removeAttr('required');

        } else {

            $('.date-box').hide();
            $('#date-field').val('').removeAttr('required');
            $('#class_ids').attr('required', true);

        }
    }

    toggleCreateFields();

    $('#holiday-type').change(function () {
        toggleCreateFields();
    });



    // EDIT FORM TOGGLE
    $('#edit-type').change(function(){

        let type = $(this).val();

        if(type === 'holiday'){

            $('.edit-date-box').show();
            $('.edit-class-box').hide();

        }else{

            $('.edit-date-box').hide();
            $('.edit-class-box').show();

        }

    });

});

</script>

@endsection