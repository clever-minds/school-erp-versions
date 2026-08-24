@extends('layouts.master')

@section('title')
    {{ __('my_attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('my_attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div id="toolbar">
                            <div class="row">
                                <div class="col">
                                    <h4 class="card-title">{{ __('my_attendance_history') }}</h4>
                                </div>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('staff-attendance.list') }}" 
                               data-click-to-select="true" data-side-pagination="server" 
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200,All]" 
                               data-search="false" data-toolbar="#toolbar"
                               data-show-columns="true" data-show-refresh="true" 
                               data-trim-on-search="false" data-mobile-responsive="true" 
                               data-sort-name="id" data-sort-order="desc"
                               data-maintain-selected="true" data-export-data-type='all' 
                               data-show-export="true" data-query-params="queryParams" 
                               data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{__('id')}}</th>
                                <th scope="col" data-field="no">{{__('no.')}}</th>
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
@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                'staff_id': "{{ Auth::user()->id }}",
            };
        }

        function updateClock() {
            const now = new Date();
            $('#current-time').text(now.toLocaleTimeString());
        }
        setInterval(updateClock, 1000);
        updateClock();

        function handleAttendance(type) {
            if (!navigator.geolocation) {
                showErrorToast("Geolocation is not supported by your browser");
                return;
            }

            $('#location-status').text("Fetching location...");
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    $('#location-status').text(`Location acquired: ${lat.toFixed(4)}, ${lng.toFixed(4)}`);

                    $.ajax({
                        url: "{{ route('staff-attendance.check') }}",
                        type: "POST",
                        data: {
                            type: type,
                            latitude: lat,
                            longitude: lng,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (!response.error) {
                                showSuccessToast(response.message);
                                $('#table_list').bootstrapTable('refresh');
                            } else {
                                showErrorToast(response.message);
                            }
                        },
                        error: function() {
                            showErrorToast("Something went wrong");
                        }
                    });
                },
                (error) => {
                    $('#location-status').text("Failed to get location");
                    showErrorToast("Permission denied or location unavailable");
                }
            );
        }
    </script>
@endsection
