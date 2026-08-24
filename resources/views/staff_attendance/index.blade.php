@extends('layouts.master')

@section('title')
    {{ __('staff_attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage').' '.__('staff_attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('view').' '.__('staff_attendance') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label>{{ __('date') }}</label>
                                {!! Form::text('date', date('d-m-Y'), ['required', 'placeholder' => __('date'), 'class' => 'datepicker-popup form-control','id'=>'date','autocomplete'=>'off']) !!}
                            </div>
                        </div>

                        <div class="show_staff_attendance_list">
                            <table aria-describedby="mydesc" class='table' id='table_list'
                                   data-toggle="table" data-url="{{ route('staff-attendance.list') }}" data-click-to-select="true"
                                   data-side-pagination="server" data-pagination="true"
                                   data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                                   data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                   data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                   data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                   data-query-params="queryParams" data-escape="true">
                                <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{__('id')}}</th>
                                    <th scope="col" data-field="no">{{__('no.')}}</th>
                                    <th scope="col" data-field="name">{{__('name')}}</th>
                                    <th scope="col" data-field="date">{{__('date')}}</th>
                                    <th scope="col" data-field="check_in">{{__('check_in')}}</th>
                                    <th scope="col" data-field="check_out">{{__('check_out')}}</th>
                                    <th scope="col" data-field="check_in_location">{{__('check_in_location')}}</th>
                                    <th scope="col" data-field="check_out_location">{{__('check_out_location')}}</th>
                                    <th scope="col" data-field="status">{{__('status')}}</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                'date': $('#date').val(),
            };
        }
    </script>

    <script>
        $('#date').on('input change', function () {
            $('#table_list').bootstrapTable('refresh');
        });
    </script>
@endsection
