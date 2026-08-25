<div class="p-4">
    {{-- Trip Performance Header --}}
    <div class="trip-performance-header">
        <div>
            <h4 class="trip-performance-title">{{ __('trip_performance') }}</h4>
            <p class="trip-performance-subtitle">{{ __('comparison_of_scheduled_vs_actual_timings') }}</p>
        </div>
        <div class="trip-header-actions">
            <div class="rv-date-selector">
                <i class="fa fa-calendar text-muted"></i>
                <input type="text" name="pickup_date" id="pickup_date" placeholder="{{ __('select_date') }}" 
                       class="datepicker-popup border-0 bg-transparent p-0" 
                       style="font-size: 0.8125rem; font-weight: 600; color: #64748b; width: 110px; outline: none; cursor: pointer;"
                       value="{{ date('d-m-Y') }}">
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pickup Trip -->
        <div class="col-md-6 mb-4">
            <div class="trip-details-card card h-100 border-0 shadow-none">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="trip-card-icon">
                            <i class="fa fa-location-arrow"></i>
                        </div>
                        <span class="trip-type-label mx-2">{{ strtoupper(__('pickup_trip')) }}</span>
                    </div>
                    <div id="pickup_trip_status" class="trip-info-status badge-ongoing">—</div>
                </div>

                
                <div class="table-responsive trip-table-modern-container">
                    <table aria-describedby="pickup-trip-table" class="table trip-table-modern" id="pickup_table"
                        data-toggle="table" data-url="{{ route('route-vehicle.trip-details', [$id]) }}"
                        data-pagination="false" data-search="false" data-show-columns="false" data-show-refresh="false"
                        data-mobile-responsive="true" data-type="pickup" data-query-params="tripDetailsParamsPickup"
                        data-side-pagination="server" data-sort-order="asc">
                        <thead>
                            <tr>
                                <th scope="col" data-field="name">{{ __('stop_name') }}</th>
                                <th scope="col" class="text-center" data-field="scheduled_time">{{ __('sched') }}</th>
                                <th scope="col" class="text-center" data-field="actual_time">{{ __('actual') }}</th>
                                <th scope="col" class="text-center" data-field="delay" data-formatter="tripDelayFormatter">{{ __('delay') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                
            </div>
        </div>

        <!-- Drop Trip -->
        <div class="col-md-6 mb-4">
            <div class="trip-details-card card h-100 border-0 shadow-none">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="trip-card-icon">
                            <i class="fa fa-location-arrow" style="transform: rotate(180deg);"></i>
                        </div>
                        <span class="trip-type-label mx-2">{{ strtoupper(__('drop_trip')) }}</span>
                    </div>
                    <div id="drop_trip_status" class="trip-info-status badge-completed">—</div>
                </div>

                
                <div class="table-responsive trip-table-modern-container">
                    <table aria-describedby="drop-trip-table" class="table trip-table-modern" id="drop_table"
                        data-toggle="table" data-url="{{ route('route-vehicle.trip-details', [$id]) }}"
                        data-pagination="false" data-search="false" data-show-columns="false" data-show-refresh="false"
                        data-mobile-responsive="true" data-type="drop" data-query-params="tripDetailsParamsDrop"
                        data-side-pagination="server" data-sort-order="asc">
                        <thead>
                            <tr>
                                <th scope="col" data-field="name">{{ __('stop_name') }}</th>
                                <th scope="col" class="text-center" data-field="scheduled_time">{{ __('sched') }}</th>
                                <th scope="col" class="text-center" data-field="actual_time">{{ __('actual') }}</th>
                                <th scope="col" class="text-center" data-field="delay" data-formatter="tripDelayFormatter">{{ __('delay') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script>
    // ----- Pickup Table -----
    function tripDetailsParamsPickup(p) {
        return {
            route_vehicle_id: {{ $id }},
            date: $('#pickup_date').val(),
            type: 'pickup'
        };
    }

    $('#pickup_date').on('change', function () {
        $('#pickup_table').bootstrapTable('refresh');
        $('#drop_table').bootstrapTable('refresh');
    });

    $('#pickup_table').on('load-success.bs.table', function (e, data) {
        if (data && data.trip_info) {
            const status = data.trip_info.status || '—';
            const $badge = $('#pickup_trip_status');
            $badge.text(status);
            
            // Update class based on status
            $badge.removeClass('badge-ongoing badge-completed badge-pending');
            if (status.toLowerCase() === 'completed') {
                $badge.addClass('badge-completed');
            } else if (status.toLowerCase() === 'ongoing') {
                $badge.addClass('badge-ongoing');
            } else {
                $badge.addClass('badge-pending');
            }
        }
    });

    // ----- Drop Table -----
    function tripDetailsParamsDrop(p) {
        return {
            route_vehicle_id: {{ $id }},
            date: $('#pickup_date').val(),
            type: 'drop'
        };
    }

    $('#drop_table').on('load-success.bs.table', function (e, data) {
        if (data && data.trip_info) {
            const status = data.trip_info.status || '—';
            const $badge = $('#drop_trip_status');
            $badge.text(status);
            
            // Update class based on status
            $badge.removeClass('badge-ongoing badge-completed badge-pending');
            if (status.toLowerCase() === 'completed') {
                $badge.addClass('badge-completed');
            } else if (status.toLowerCase() === 'ongoing') {
                $badge.addClass('badge-ongoing');
            } else {
                $badge.addClass('badge-pending');
            }
        }
    });
</script>