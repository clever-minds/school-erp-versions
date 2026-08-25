@extends('layouts.master')

@section('title')
    {{ __('certificate') }} {{ __('assign') }}
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
            {{ __('certificate_assign') }}
        </h3>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    {{-- ---- Tab Switcher ---- --}}
                    <div class="ca-tabs">
                        <button class="ca-tab-btn active" data-tab="student">
                            <i class="fa fa-user-graduate mr-1"></i> {{ __('student_certificate') }}
                        </button>
                        <button class="ca-tab-btn" data-tab="staff">
                            <i class="fa fa-user-tie mr-1"></i> {{ __('staff_certificate') }}
                        </button>
                        <button class="ca-tab-btn" data-tab="history">
                            <i class="fa fa-history mr-1"></i> {{ __('assignment_history') }}
                        </button>
                    </div>

                    {{-- ================================================================ --}}
                    {{--  TAB: STUDENT                                                    --}}
                    {{-- ================================================================ --}}
                    <div class="ca-tab-panel" id="tab-student">
                        <div class="ca-filter-bar">
                            <div class="form-group">
                                <label>{{ __('certificate_template') }} <span class="text-danger">*</span></label>
                                <select id="cert_template_id" class="form-control">
                                    <option value="">{{ __('select_template') }}</option>
                                    @foreach($studentTemplates as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('class_section') }}</label>
                                <select id="filter_class_section" class="form-control">
                                    <option value="">{{ __('all_sections') }}</option>
                                    @foreach($classSections as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('exam') }} ({{ __('optional') }})</label>
                                <select id="filter_exam" class="form-control">
                                    <option value="">{{ __('select_exam') }}</option>
                                    @foreach($exams as $exam)
                                        <option value="{{ $exam->id }}">{{ $exam->prefix_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('issue_date') }}</label>
                                <input type="text" id="issued_at" class="form-control datepicker-popup">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="student-table" class="table" data-toggle="table" data-url="{{ route('certificate-assign.students') }}" data-side-pagination="server" data-pagination="true" data-page-list="[10, 25, 50, 100]" data-search="true" data-click-to-select="true" data-trim-on-search="false" data-maintain-selected="true" data-query-params="studentQueryParams" data-sort-name="id" data-sort-order="asc" data-show-refresh="true" data-show-columns="false" data-escape="false">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="user_id" data-visible="false">{{ __('user_id') }}</th>
                                        <th data-field="full_name" data-formatter="StudentNameFormatter"  data-sortable="false">{{ __('student') }}</th>
                                        <th data-field="class_section.full_name">{{ __('class_section') }}</th>
                                        <th data-field="roll_number">{{ __('roll_no') }}</th>
                                        <th data-field="already_assigned" data-formatter="caStatusFormatter">{{ __('status') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        {{-- Assign Action Bar (shown on selection) --}}
                        <div class="ca-assign-bar">
                            <span class="ca-selection-info">0 {{ __('user_selected') }}</span>
                            <button id="btn-assign-submit" class="btn btn-assign-submit btn-theme btn-sm">
                                <i class="fa fa-check"></i> {{ __('assign_certificate') }}
                            </button>
                        </div>
                    </div>

                    {{-- ================================================================ --}}
                    {{--  TAB: STAFF                                                      --}}
                    {{-- ================================================================ --}}
                    <div class="ca-tab-panel" id="tab-staff" style="display:none;">
                        <div class="ca-filter-bar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label>{{ __('certificate_template') }} <span class="text-danger">*</span></label>
                                <select id="cert_template_id_staff" class="form-control">
                                    <option value="">{{ __('select_template') }}</option>
                                    @foreach($staffTemplates as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="staff-table" class="table" data-toggle="table" data-url="{{ route('certificate-assign.staff') }}" data-side-pagination="server" data-pagination="true" data-page-list="[10, 25, 50, 100]" data-search="true" data-click-to-select="true" data-trim-on-search="false" data-maintain-selected="true" data-query-params="staffQueryParams" data-sort-name="id" data-sort-order="asc" data-show-refresh="true" data-escape="false">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="user_id" data-visible="false">{{ __('user_id') }}</th>
                                        <th data-field="full_name" data-formatter="StaffNameFormatter" data-sortable="false">{{ __('name') }}</th>
                                        <th data-field="roles" data-formatter="roleFormatter">{{ __('role') }}</th>
                                        <th data-field="email">{{ __('email') }}</th>
                                        <th data-field="mobile">{{ __('mobile') }}</th>
                                        <th data-field="already_assigned" data-formatter="caStatusFormatter">{{ __('status') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="ca-assign-bar">
                            <span class="ca-selection-info">0 {{ __('user_selected') }}</span>
                            <button id="btn-assign-submit" class="btn btn-assign-submit btn-theme btn-sm">
                                <i class="fa fa-check"></i> {{ __('assign_certificate') }}
                            </button>
                        </div>
                    </div>

                    {{-- ================================================================ --}}
                    {{--  TAB: HISTORY                                                    --}}
                    {{-- ================================================================ --}}
                    <div class="ca-tab-panel" id="tab-history" style="display:none;">
                        <div class="ca-filter-bar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label>{{ __('filter_by_template') }}</label>
                                <select id="history_cert_template_id" class="form-control">
                                    <option value="">{{ __('all_templates') }}</option>
                                    @if(count($studentTemplates) > 0)
                                        <optgroup label="{{ __('student_templates') }}">
                                            @foreach($studentTemplates as $id => $name)
                                                <option value="{{ $id }}" data-type="Student">{{ $name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if(count($staffTemplates) > 0)
                                        <optgroup label="{{ __('staff_templates') }}">
                                            @foreach($staffTemplates as $id => $name)
                                                <option value="{{ $id }}" data-type="Staff">{{ $name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="ca-assign-bar" id="history-action-bar" style="display:none;">
                            <span class="history-selection-info">0 {{ __('record_selected') }}</span>
                            <button id="btn-generate-history" class="btn btn-theme btn-sm">
                                <i class="fa fa-print"></i> {{ __('generate_certificate') }}
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table id="history-table" class="table" data-toggle="table" data-url="{{ route('certificate-assign.history') }}" data-side-pagination="server" data-pagination="true" data-page-list="[10, 25, 50, 100]" data-search="true" data-query-params="historyQueryParams" data-sort-order="desc" data-show-refresh="true" data-escape="false" data-click-to-select="true" data-maintain-selected="true">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="user.full_name" data-formatter="userNameFormatter">{{ __('name') }}</th>
                                        <th data-field="user_type">{{ __('type') }}</th>
                                        <th data-field="template_name">{{ __('template') }}</th>
                                        <th data-field="class_section">{{ __('class') }}</th>
                                        <th data-field="roll_no">{{ __('roll_no') }}</th>
                                        <th data-field="issued_at">{{ __('issued_on') }}</th>
                                        <th data-field="operate" data-formatter="historyRevokeFormatter">{{ __('action') }}</th>
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
