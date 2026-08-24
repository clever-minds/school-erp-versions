@extends('layouts.master')

@section('title')
    {{ __('Fees Report') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Fees Report') }}
            </h3>
        </div>
        <div class="row">
            {{-- Total Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold">{{ __('total_fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_statistics">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span
                                        class="total_compulsory_fees">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span
                                        class="total_optional_fees">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Total Collected Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold"> {{ __('collected') }} {{ __('Fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_collected">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span
                                        class="total_compulsory_fees_collected">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span
                                        class="total_optional_fees_collected">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Total Pending Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold"> {{ __('pending') }} {{ __('Fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_pending">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span
                                        class="total_compulsory_fees_pending">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span
                                        class="total_optional_fees_pending">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"></h4>
                        <div id="toolbar">
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="session_year_id"> {{ __('Session Years') }} </label>
                                    <select name="session_year_id" id="session_year_id" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        @foreach ($session_year_all as $session_year)
                                            <option value="{{ $session_year->id }}"
                                                {{ $session_year->default ? 'selected' : '' }}> {{ $session_year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="mode">{{ __('Payment Mode') }}</label>
                                    <select name="mode" id="mode" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        <option value="Online">{{ __('Online') }}</option>
                                        <option value="Cash">{{ __('Cash (Offline)') }}</option>
                                        <option value="Cheque">{{ __('Cheque (Offline)') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="start_date">{{ __('Start Date') }}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="end_date"> {{ __('To Date') }} </label>
                                    <input type="date" name="end_date" id="end_date" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="receipt_no">{{ __('Receipt No') }}</label>
                                    <input type="text" name="receipt_no" id="receipt_no" class="form-control" placeholder="e.g. C-327">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="gr_no"> {{ __('GR Number') }} </label>
                                    <select class="grno-search form-control" id="gr_no"><option value="">search</option></select>
                                    <input type="hidden" id="student_id" class="student_id" name="student_id">
                                </div>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('fees.report.list') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                            data-maintain-selected="true" data-export-data-type='all'
                            data-export-options='{ "fileName": "fees-report-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                            data-show-export="true"
                            data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="receipt_no" data-sortable="false">{{ __('Receipt No') }}</th>
                                    <th scope="col" data-field="admission_no" data-sortable="false">{{ __('GR Number') }}</th>
                                    <th scope="col" data-field="class_section" data-sortable="false">{{ __('Class Section') }}</th>
                                    <th scope="col" data-field="student_name" data-sortable="false">{{ __('Student Name') }}</th>
                                     <th scope="col" data-field="fees_name" data-sortable="false">{{ __('Fees Name') }}</th>
                                    <th scope="col" data-field="date" data-sortable="false">{{ __('Date') }}</th>
                                    <th scope="col" data-field="session_year" data-sortable="false">{{ __('Session Year') }}</th>
                                    <th scope="col" data-field="mode" data-sortable="false">{{ __('Payment Mode') }}</th>
                                    <th scope="col" data-field="total_amount" data-sortable="false">{{ __('Amount Paid') }}</th>
                                </tr>
                            </thead>
                        </table>
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
                session_year_id: $('#session_year_id').val(),
                mode: $('#mode').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                receipt_no: $('#receipt_no').val(),
                student_id: $('#student_id').val()
            };
        }

        $('#session_year_id, #mode, #start_date, #end_date, #receipt_no').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });

        $('#gr_no').on('change', function () {
            $('#student_id').val($(this).val());
            $('#table_list').bootstrapTable('refresh');
        });

        $('#table_list').on('load-success.bs.table', function (e, data) {
            let total = (data.total_compulsory_fees + data.total_optional_fees);
            let collected = (data.total_compulsory_fees_collected + data.total_optional_fees_collected);
            let pending = (data.total_compulsory_fees_pending + data.total_optional_fees_pending);

            $('.total_fees_statistics').text(total.toFixed(2));
            $('.total_compulsory_fees').text(data.total_compulsory_fees.toFixed(2));
            $('.total_optional_fees').text(data.total_optional_fees.toFixed(2));

            $('.total_fees_collected').text(collected.toFixed(2));
            $('.total_compulsory_fees_collected').text(data.total_compulsory_fees_collected.toFixed(2));
            $('.total_optional_fees_collected').text(data.total_optional_fees_collected.toFixed(2));

            $('.total_fees_pending').text(pending.toFixed(2));
            $('.total_compulsory_fees_pending').text(data.total_compulsory_fees_pending.toFixed(2));
            $('.total_optional_fees_pending').text(data.total_optional_fees_pending.toFixed(2));
        });
    </script>
@endsection
