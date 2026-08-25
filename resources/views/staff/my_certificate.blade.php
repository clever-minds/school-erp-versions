@extends('layouts.master')

@section('title')
    {{ __('certificate') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/certificate-assign.css') }}">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-theme text-white mr-2">
                <i class="fa fa-certificate"></i>
            </span>
            {{ __('certificate') }}
        </h3>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    {{-- ================================================================ --}}
                    {{--  TAB: HISTORY                                                    --}}
                    {{-- ================================================================ --}}
                    <div class="ca-tab-panel">
                        {{-- <div class="ca-filter-bar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label>{{ __('filter_by_template') }}</label>
                                <select id="history_cert_template_id" class="form-control">
                                    <option value="">{{ __('all_templates') }}</option>
                                    @if(count($staffTemplates) > 0)
                                        @foreach($staffTemplates as $id => $name)
                                            <option value="{{ $id }}" data-type="Staff">{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div> --}}

                        {{-- <div class="ca-assign-bar">
                            <span class="history-selection-info">0 {{ __('record_selected') }}</span>
                            <button id="btn-generate-history" class="btn btn-theme btn-sm">
                                <i class="fa fa-print"></i> {{ __('generate_certificate') }}
                            </button>
                        </div> --}}

                        <div class="table-responsive">
                            <div id="toolbar">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('filter_by_template') }}</label>
                                    <select id="history_cert_template_id" class="form-control">
                                        <option value="">{{ __('all_templates') }}</option>
                                        @if(count($staffTemplates) > 0)
                                            @foreach($staffTemplates as $id => $name)
                                                <option value="{{ $id }}" data-type="Staff">{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <table id="history-table" class="table" data-toggle="table" data-url="{{ route('staff.my-certificate.show') }}" data-side-pagination="server" data-pagination="true" data-page-list="[10, 25, 50, 100]" data-search="false" data-query-params="historyQueryParams" data-sort-order="desc" data-show-refresh="true" data-escape="false" data-click-to-select="true" data-maintain-selected="true">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="user.full_name" data-formatter="userNameFormatter">{{ __('name') }}</th>
                                        <th data-field="template_name">{{ __('template') }}</th>
                                        <th data-field="issued_at">{{ __('issued_on') }}</th>
                                        <th data-field="operate">{{ __('action') }}</th>
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
@endsection

@section('script')
{{-- Inject PHP config into JS safely --}}
<script>
window.CA_CONFIG = {
    csrfToken:     "{{ csrf_token() }}",
    assignUrl:     "{{ route('certificate-assign.assign') }}",
    revokeUrl:     "{{ route('certificate-assign.revoke') }}",
    defaultAvatar: "{{ asset('assets/dummy_logo.jpg') }}",
    generateStudentUrl: "{{ url('certificate') }}",
    generateStaffUrl: "{{ url('certificate/staff-certificate') }}"
};
</script>
<script src="{{ asset('assets/js/certificate-assign.js') }}"></script>
@endsection
