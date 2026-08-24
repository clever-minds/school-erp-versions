@extends('layouts.master')

@section('title')
    {{ __('Student Pickup Requests') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Student Pickup Requests') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('List Pickup Requests') }}
                        </h4>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('student-pickup.list') }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-show-export="true" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="student_name">{{ __('Student Name') }}</th>
                                <th scope="col" data-field="parent_name">{{ __('Parent Name') }}</th>
                                <th scope="col" data-field="pickup_person_name">{{ __('Pickup Person') }}</th>
                                <th scope="col" data-field="otp">{{ __('OTP') }}</th>
                                <th scope="col" data-field="status" data-formatter="pickupStatusFormatter">{{ __('Status') }}</th>
                                <th scope="col" data-field="verified_by">{{ __('Verified By') }}</th>
                                <th scope="col" data-field="verified_at" data-sortable="true">{{ __('Verified At') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function pickupStatusFormatter(value, row) {
            if (value == 0) {
                return '<span class="badge badge-warning">Pending</span>';
            } else if (value == 1) {
                return '<span class="badge badge-success">Verified</span>';
            } else if (value == 2) {
                return '<span class="badge badge-danger">Expired</span>';
            }
            return '-';
        }
    </script>
@endsection
