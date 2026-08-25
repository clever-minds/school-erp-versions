

<div class="tlog-table-wrapper">
    <div class="tlog-table-header">
        <h6 class="tlog-table-header__title">
            <i class="fa fa-history"></i>
            {{ __('transportation_log_history') }}
        </h6>
    </div>

    {{-- Bootstrap Table View --}}
    <div class="p-3">
        <table class='table' id='table_list' data-toggle="table" data-url="{{ route('reports.transportation-logs.show', $id) }}" data-click-to-select="true" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-fixed-columns="false" data-mobile-responsive="true" data-maintain-selected="true" data-show-export="true" data-export-options='{"fileName": "transportation-logs-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}' data-query-params="queryParams" data-escape="false">
            <thead>
                <tr>
                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                    <th scope="col" data-field="vehicle_route" data-sortable="false">{{ __('vehicle_route') }}</th>
                    <th scope="col" data-field="pickup_point" data-sortable="false">{{ __('pickup_point') }}</th>
                    <th scope="col" data-field="shift" data-sortable="false">{{ __('shift') }}</th>
                    <th scope="col" data-field="duration" data-sortable="false">{{ __('duration') }}</th>
                    <th scope="col" data-field="amount" data-sortable="false">{{ __('amount') }}</th>
                    <th scope="col" data-field="payment_mode" class="text-center" data-sortable="false">{{ __('payment_mode') }}</th>
                    <th scope="col" data-field="period" data-sortable="false">{{ __('period') }}</th>
                    <th scope="col" data-field="operate" data-sortable="false" class="text-center">{{ __('action') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
