@extends('layouts.master')

@section('title')
    {{ __('Promote Students') }}
@endsection

@section('content')
<style>
    /* Custom CSS for Promotion UI */
    .promotion-card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .progress-summary-card { border-radius: 12px; border: 1px solid #f0f0f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .session-config-box { border-radius: 10px; border: 1px solid #e8eaf6; background-color: #fcfcfd; transition: all 0.3s; }
    .session-config-box.disabled { background-color: #f5f5f5; opacity: 0.7; }
    .metric-value { font-size: 1.5rem; font-weight: 700; }
    .avatar-initials { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #5c6bc0; background-color: #e8eaf6; }
    .custom-badge { padding: 6px 14px !important; border-radius: 20px; font-weight: 500; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; }
    .badge-success-light { background-color: #e8f5e9; color: #2e7d32; }
    .badge-warning-light { background-color: #fff8e1; color: #f57f17; }
    .badge-primary-light { background-color: #e8eaf6; color: #3f51b5; }
    .badge-secondary-light { background-color: #f5f5f5; color: #757575; border: 1px solid #e0e0e0; }
    
    /* Toggle Buttons */
    .btn-toggle-group .btn { padding: 6px 16px; font-weight: 500; font-size: 0.85rem; border: 1px solid #e0e0e0; background-color: #fff; color: #757575; transition: all 0.2s; box-shadow: none !important;}
    .btn-toggle-group .btn:hover { background-color: #fafafa; }
    .btn-toggle-group .btn.active-success { background-color: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; z-index: 1; pointer-events: none;}
    .btn-toggle-group .btn.active-secondary { background-color: #f5f5f5; color: #616161; border-color: #eeeeee; z-index: 1; pointer-events: none;}
    .btn-toggle-group .btn.active-primary { background-color: #e8eaf6; color: #3f51b5; border-color: #9fa8da; z-index: 1; pointer-events: none;}
    .btn-toggle-group .btn.active-warning { background-color: #fff3e0; color: #ef6c00; border-color: #ffcc80; z-index: 1; pointer-events: none;}
    .btn-toggle-group .btn.active-danger { background-color: #fce4e4; color: #d32f2f; border-color: #ef9a9a; z-index: 1; pointer-events: none; font-weight: 600;}
    .btn-toggle-group .btn:first-child + label { border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
    .btn-toggle-group .btn:last-child { border-top-right-radius: 6px; border-bottom-right-radius: 6px; }

    /* Table styling */
    .table-custom th { text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #757575; padding: 15px 10px; border-bottom: 2px solid #f0f0f0 !important; border-top: none !important; }
    .table-custom td { padding: 15px 10px; vertical-align: middle; border-bottom: 1px solid #f5f5f5; background: #fff;}
    .table-custom tbody tr.selected td { background-color: #f8f9ff; }
    .table-custom tbody tr:hover td { background-color: #fafafa; }
    
    .final-year-checkbox { width: 16px; height: 16px; cursor: pointer; }
    #create-btn {
        margin-bottom: unset !important;
    }
</style>

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Promote Students')}}
            </h3>
        </div>

        @can('promote-student-create')
            <!-- Progress Summary Section -->
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card progress-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light text-primary rounded p-2 mr-3" style="background-color: #e8eaf6; color: #3f51b5; border-radius: 8px;">
                                        <i class="fa fa-users"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" style="font-weight: 600;">{{ __('Class Section Progress') }}</h5>
                                        <small class="text-muted" id="progress-class-name">{{ __('Select Class') }}</small>
                                    </div>
                                </div>
                                <h3 class="text-primary mb-0 font-weight-bold" id="progress-percentage">0%</h3>
                            </div>
                            <div class="progress mb-3" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-primary" id="progress-bar-el" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between text-center px-4">
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('TOTAL') }}</small>
                                    <h5 class="mt-1 font-weight-bold" id="progress-total">0</h5>
                                </div>
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('PROMOTED') }}</small>
                                    <h5 class="mt-1 font-weight-bold text-success" id="progress-promoted">0</h5>
                                </div>
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('PENDING') }}</small>
                                    <h5 class="mt-1 font-weight-bold text-warning" id="progress-pending">0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card progress-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box text-info rounded p-2 mr-3" style="background-color: #e3f2fd; color: #1976d2; border-radius: 8px;">
                                        <i class="fa fa-building"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0" style="font-weight: 600;">{{ __('School Overall Progress') }}</h5>
                                        <small class="text-muted">{{ __('All Branches') }}</small>
                                    </div>
                                </div>
                                <h3 class="text-info mb-0 font-weight-bold" id="school-progress-percentage">0%</h3>
                            </div>
                            <div class="progress mb-3" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-info" id="school-progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between text-center px-4">
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('TOTAL') }}</small>
                                    <h5 class="mt-1 font-weight-bold" id="school-total">0</h5>
                                </div>
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('PROMOTED') }}</small>
                                    <h5 class="mt-1 font-weight-bold text-success" id="school-promoted">0</h5>
                                </div>
                                <div>
                                    <small class="text-muted font-weight-bold">{{ __('PENDING') }}</small>
                                    <h5 class="mt-1 font-weight-bold text-warning" id="school-pending">0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card promotion-card">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 d-flex align-items-center" style="font-weight: 600;">
                                <i class="fa fa-filter text-primary mr-2" style="font-size: 1.2rem;"></i> {{ __('Promotion Session Configuration') }}
                            </h5>
                            
                            <form action="{{ route('promote-student.store') }}" data-success-function="formSuccessFunction" class="create-form" id="formdata">
                                @csrf
                                <div class="row align-items-center">
                                    <!-- From Current Session -->
                                    <div class="col-md-5 mb-4 mb-md-0">
                                        <div class="session-config-box p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-3 d-flex align-items-center" style="font-weight: 600;">
                                                    <span style="color: #9e9e9e; font-size: 0.6rem; margin-right: 8px;">●</span> {{ __('From Current Session') }}
                                                </h6>

                                                <div class="form-check m-0">
                                                    <label class="form-check-label text-muted" style="font-size: 0.75rem; font-weight: 600; cursor: pointer; letter-spacing: 0.5px;">
                                                        <input type="checkbox" class="form-check-input final-year-checkbox" id="final_year_checkbox" name="is_final_year" value="1" style="cursor: pointer;">
                                                        {{ __('FINAL STANDARD') }}
                                                    <i class="input-helper"></i></label>
                                                </div>
                                            </div>

                                            
                                            <div class="row">
                                                <div class="form-group col-md-6 mb-3 mb-md-0">
                                                    <label class="text-muted" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">{{ __('SESSION YEAR') }} <span class="text-danger">*</span></label>
                                                    <select required name="from_session_year_id" id="from_session_year_id" class="form-control select2" style="width:100%;">
                                                        <option value="">{{ __('Select Session Year') }}</option>
                                                        @foreach ($sessionYears as $years)
                                                            <option value="{{ $years->id }}" data-start-date="{{ $years->getRawOriginal('start_date') }}">
                                                                {{ $years->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6 mb-0">
                                                    <label class="text-muted" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">{{ __('CLASS SECTION') }} <span class="text-danger">*</span></label>
                                                    <select required name="class_section_id" id="student_class_section" class="form-control select2" style="width:100%;">
                                                        <option value="">{{ __('Select Class') }}</option>
                                                        @foreach ($classSections as $section)
                                                            <option value="{{ $section->id }}" data-class="{{ $section->class->id }}">
                                                                {{ $section->full_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Arrow Icon -->
                                    <div class="col-md-2 text-center d-none d-md-block">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #f5f6fa;">
                                            <i class="fa fa-arrow-right text-muted"></i>
                                        </div>
                                    </div>

                                    <!-- Promote To Next Session -->
                                    <div class="col-md-5">
                                        <div class="session-config-box p-3" id="target-session-box">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0 d-flex align-items-center" style="font-weight: 600; color: #3f51b5;">
                                                    <span style="color: #3f51b5; font-size: 0.6rem; margin-right: 8px;">●</span> {{ __('Promote To Next Session') }}
                                                </h6>
                                                {{-- <div class="form-check m-0">
                                                    <label class="form-check-label text-muted" style="font-size: 0.75rem; font-weight: 600; cursor: pointer; letter-spacing: 0.5px;">
                                                        <input type="checkbox" class="form-check-input final-year-checkbox" id="final_year_checkbox" name="is_final_year" value="1" style="cursor: pointer;">
                                                        {{ __('FINAL STANDARD') }}
                                                    <i class="input-helper"></i></label>
                                                </div> --}}
                                            </div>
                                            <div class="row mt-4">
                                                <div class="form-group col-md-6 mb-3 mb-md-0">
                                                    <label class="text-muted" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">{{ __('TARGET SESSION') }} <span class="text-danger target-required">*</span></label>
                                                    <select required id="session_year_id" class="form-control select2" style="width:100%;" disabled>
                                                        <option value="">{{ __('Select Session Year') }}</option>
                                                        @foreach ($sessionYears as $years)
                                                            <option value="{{ $years->id }}" data-start-date="{{ $years->getRawOriginal('start_date') }}">
                                                                {{ $years->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="session_year_id" id="session_year_id_hidden">
                                                </div>
                                                <div class="form-group col-md-6 mb-0">
                                                    <label class="text-muted" style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">{{ __('TARGET CLASS') }} <span class="text-danger target-required">*</span></label>
                                                    <select required name="new_class_section_id" id="new_student_class_section" class="form-control select2" style="width:100%;">
                                                        <option value="">{{ __('Select Class') }}</option>
                                                        @foreach ($classSections as $section)
                                                            <option value="{{ $section->id }}" data-class="{{ $section->class->id }}">
                                                                {{ $section->full_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="mt-4 mb-4" style="border-top: 1px dashed #e0e0e0;">
                                
                                <!-- Toolbar & Table Section -->
                                <div id="toolbar" class="d-flex align-items-center flex-wrap mb-3" style="gap: 15px;">
                                    <select name="promotion_status" id="promotion_status" class="form-control shadow-sm mb-0" style="height: 42px !important;">
                                        <option value="">{{ __('Filter By Status: All') }}</option>
                                        <option value="1">{{ __('Promoted') }}</option>
                                        <option value="0">{{ __('Pending') }}</option>
                                    </select>
                                    
                                    <div class="d-flex align-items-center bg-light rounded text-muted font-weight-bold shadow-sm" style="height: 42px !important; padding: 0 15px; font-size: 0.85rem; border: 1px solid #eee;">
                                        {{ __('Selected:') }} <span id="selected-count" class="badge badge-primary badge-pill ml-2">0</span>
                                    </div>
                                    
                                    <button class="btn btn-theme btn_promote font-weight-bold shadow-sm align-items-center justify-content-center m-0" id="create-btn" type="submit" style="height: 42px !important; padding: 0 20px; border: none; white-space: nowrap;">
                                        <i class="fa fa-save mr-2"></i> {{ __('Save Changes') }}
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table aria-describedby="mydesc" class='table table-custom promote_student_table' id='promote_student_table_list'
                                           data-toggle="table" data-url="{{ route('promote-student.show', [1]) }}"
                                           data-click-to-select="true" data-side-pagination="server" data-pagination="false"
                                           data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                           data-show-columns="false" data-show-refresh="false" data-fixed-columns="false"
                                           data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                           data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                           data-maintain-selected="true" data-export-data-type='all' data-show-export="false"
                                           data-query-params="promoteStudentQueryParams" data-escape="true">
                                        <thead>
                                        <tr>
                                            <th data-field="promote_check" data-checkbox="true" data-width="50"></th>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-visible="false">{{ __('no.') }}</th>
                                            <th scope="col" data-field="student_id" data-visible="false">{{ __('Student Id') }}</th>
                                            <th scope="col" data-field="student_details" data-formatter="StudentNameFormatter" data-width="350">{{ __('STUDENT DETAILS') }}</th>
                                            <th scope="col" data-field="roll_number" data-formatter="rollNoFormatter" data-width="100" class="text-center">{{ __('ROLL NO') }}</th>
                                            <th scope="col" data-field="result" data-formatter="customResultFormatter" data-width="150" class="text-center">{{ __('RESULT STATUS') }}</th>
                                            <th scope="col" data-field="status" data-formatter="customActionStatusFormatter" data-width="150" class="text-center">{{ __('ACTION STATUS') }}</th>
                                            <th scope="col" data-field="promoted_to" data-formatter="promotedToFormatter" data-width="200" class="text-center">{{ __('PROMOTED TO SECTION') }}</th>
                                            <th scope="col" data-field="current_state" data-formatter="customStateFormatter" data-width="150" class="text-center">{{ __('CURRENT STATE') }}</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection

@section('script')
    <script>
        // Custom formatters
        function getInitials(firstName, lastName) {
            let f = firstName ? firstName.charAt(0).toUpperCase() : '';
            let l = lastName ? lastName.charAt(0).toUpperCase() : '';
            return f + l;
        }

        function promotedToFormatter(value, row) {
            value = value || '-';
            return '<span class="text-muted font-weight-bold" style="font-size: 0.85rem;">' + value + '</span>';
        }

        function customStudentDetailsFormatter(value, row) {
            let user = row.user || {};
            let firstName = user.first_name || '';
            let lastName = user.last_name || '';
            let fullName = firstName + ' ' + lastName;
            let admissionNo = '-';
            
            if (user.student && user.student.admission_no) {
                admissionNo = user.student.admission_no;
            } else if (row.student_id) {
                admissionNo = 'STU-20240' + row.student_id;
            } else if (row.id) {
                admissionNo = 'STU-20240' + row.id;
            }

            return `
            <div class="d-flex align-items-center">
                <div class="avatar-initials mr-3 shadow-sm" style="font-size: 1rem;">
                    ${getInitials(firstName, lastName)}
                </div>
                <div class="text-left">
                    <h6 class="mb-0 text-dark" style="font-weight: 600; font-size: 0.95rem;">${fullName}</h6>
                    <small class="text-muted" style="font-size: 0.8rem; font-weight: 500;">ID: ${admissionNo}</small>
                </div>
            </div>`;
        }

        function rollNoFormatter(value, row) {
            let num = '-';
            if (row.user && row.user.student && row.user.student.roll_number) {
                 num = row.user.student.roll_number;
            } else if (row.roll_number) {
                 num = row.roll_number;
            }
            return `<div class="font-weight-bold text-dark" style="font-size: 0.95rem;">${num}</div>`;
        }

        function customResultFormatter(value, row) {
            let result = (row.result !== null && row.result !== undefined && row.result !== "") ? row.result : 1; 
            let isPass = (result == 1);
            let checkPass = isPass ? 'checked' : '';
            let checkFail = !isPass ? 'checked' : '';
            
            return `
            <div class="btn-toggle-group d-inline-flex m-auto" role="group">
                <input type="hidden" name="promote_data[${row.no}][student_id]" value="${row.user_id || row.student_id || row.id}">
                
                <input type="radio" class="d-none result-radio" name="promote_data[${row.no}][result]" id="pass_${row.id}" value="1" ${checkPass} onchange="updateRowState(${row.id}, this)">
                <label class="btn btn-sm m-0 ${isPass ? 'active-success' : ''}" for="pass_${row.id}">${window.trans["pass"] || 'Pass'}</label>

                <input type="radio" class="d-none result-radio" name="promote_data[${row.no}][result]" id="fail_${row.id}" value="0" ${checkFail} onchange="updateRowState(${row.id}, this)">
                <label class="btn btn-sm m-0 ${!isPass ? 'active-danger' : ''}" for="fail_${row.id}">${window.trans["fail"] || 'Fail'}</label>
            </div>`;
        }

        function customActionStatusFormatter(value, row) {
            let status = (row.status !== null && row.status !== undefined && row.status !== "") ? row.status : 1;
            let isContinue = (status == 1);
            let checkCont = isContinue ? 'checked' : '';
            let checkLeave = !isContinue ? 'checked' : '';
            
            return `
            <div class="btn-toggle-group d-inline-flex m-auto" role="group">
                <input type="radio" class="d-none status-radio" name="promote_data[${row.no}][status]" id="continue_${row.id}" value="1" ${checkCont} onchange="updateRowState(${row.id}, this)">
                <label class="btn btn-sm m-0 ${isContinue ? 'active-primary' : ''}" for="continue_${row.id}">${window.trans["continue"] || 'Continue'}</label>

                <input type="radio" class="d-none status-radio" name="promote_data[${row.no}][status]" id="leave_${row.id}" value="0" ${checkLeave} onchange="updateRowState(${row.id}, this)">
                <label class="btn btn-sm m-0 ${!isContinue ? 'active-warning' : ''}" for="leave_${row.id}">${window.trans["leave"] || 'Leave'}</label>
            </div>`;
        }

        function customStateFormatter(value, row) {
            let isFinalYear = $('#final_year_checkbox').is(':checked');
            if (isFinalYear) {
                return `<span class="custom-badge badge-success-light" id="state_msg_${row.id}"><i class="fa fa-graduation-cap mr-1"></i> Graduating / Completed</span>`;
            }
            if (row.promotion_status == 1) {
                return `<span class="custom-badge badge-success-light" id="state_msg_${row.id}"><i class="fa fa-check-circle mr-1"></i> Promoted</span>`;
            }
            
            // Check selections natively by class since formatter is called before onCheck fires internally sometimes
            // To be safe we just show Pending initially except if they are selected.
            return `<span class="custom-badge badge-secondary-light" id="state_msg_${row.id}">Pending</span>`;
        }

        window.updateRowState = function(rowId, el, forceFinalYear = null) {
            let $row = $(el).closest('tr');
            let isFinalYear = forceFinalYear !== null ? forceFinalYear : $('#final_year_checkbox').is(':checked');
            
            let val = $(el).val();
            let isResult = $(el).hasClass('result-radio');
            
            let $resultPass = $row.find('.result-radio[value="1"]');
            let $resultFail = $row.find('.result-radio[value="0"]');
            let $statusCont = $row.find('.status-radio[value="1"]');
            let $statusLeave = $row.find('.status-radio[value="0"]');

            let resultVal = $row.find('.result-radio:checked').val();
            let statusVal = $row.find('.status-radio:checked').val();

            if (isFinalYear) {
                if (resultVal == 1) {
                    $statusCont.prop('disabled', true);
                    $statusLeave.prop('checked', true);
                    statusVal = '0'; // force leave
                } else if (resultVal == 0) {
                    $statusCont.prop('disabled', false); // Can change to Continue or Leave
                }
            } else {
                $statusCont.prop('disabled', false);
            }

            // Sync visual states for Result
            $row.find('.result-radio').next('label').removeClass('active-success active-secondary active-danger text-muted');
            if (resultVal == 1) {
                $resultPass.next('label').addClass('active-success');
            } else {
                $resultFail.next('label').addClass('active-danger');
            }

            // Sync visual states for Status
            $row.find('.status-radio').next('label').removeClass('active-primary active-secondary active-warning text-muted').css('opacity', '1').css('cursor', 'pointer');
            if (statusVal == 1) {
                $statusCont.next('label').addClass('active-primary');
            } else {
                $statusLeave.next('label').addClass('active-warning');
            }

            if ($statusCont.prop('disabled')) {
                 $statusCont.next('label').css('opacity', '0.4').css('cursor', 'not-allowed').addClass('text-muted');
            }

            // Auto check row
            let table = $('#promote_student_table_list');
            let data = table.bootstrapTable('getData');
            let index = data.findIndex(r => r.id == rowId);
            if(index !== -1) {
                table.bootstrapTable('check', index);
            }
        };

        // End Custom Formatters

        function promoteStudentQueryParams(params) {
            return {
                limit: params.limit,
                sort: params.sort,
                order: params.order,
                offset: params.offset,
                search: params.search,
                from_session_year_id: $('#from_session_year_id').val(),
                class_section_id: $('#student_class_section').val(),
                promotion_status: $('#promotion_status').val(),
            };
        }

        $('#student_class_section, #from_session_year_id, #promotion_status').on('change', function () {
            $('#promote_student_table_list').bootstrapTable('refresh');
        });

        $('#from_session_year_id').on('change', function () {
            let selectedStartDate = $(this).find(':selected').data('start-date');

            $('#session_year_id option').prop('disabled', false).show();

            if (selectedStartDate) {
                let nextSessionId = '';
                let minGreaterDate = null;

                $('#session_year_id option').each(function () {
                    let optionStartDate = $(this).data('start-date');
                    if (optionStartDate && optionStartDate > selectedStartDate) {
                        if (minGreaterDate === null || optionStartDate < minGreaterDate) {
                            minGreaterDate = optionStartDate;
                            nextSessionId = $(this).val();
                        }
                    }
                });

                if (nextSessionId) {
                    $('#session_year_id').val(nextSessionId).trigger('change');
                    $('#session_year_id_hidden').val(nextSessionId);

                    $('#session_year_id option').each(function () {
                        if ($(this).val() != nextSessionId && $(this).val() != "") {
                            $(this).hide();
                        }
                    });
                } else {
                    $('#session_year_id').val("").trigger('change');
                    $('#session_year_id_hidden').val("");
                }
            } else {
                $('#session_year_id').val("").trigger('change');
                $('#session_year_id_hidden').val("");
            }
        });

        $('.btn_promote').hide();

        function set_data() {
            student_class = $('#student_class_section').val();
            session_year = $('#session_year_id').val();
            from_session_year = $('#from_session_year_id').val();
            promote_class = $('#new_student_class_section').val();
            let selectedRows = $('#promote_student_table_list').bootstrapTable('getSelections');
            
            // Handle ui selection count
            $('#selected-count').text(selectedRows.length);

            // Dynamically update UI States
            let isFinalYear = $('#final_year_checkbox').is(':checked');
            
            $('#promote_student_table_list').find('tbody tr label.active-success, tbody tr label.active-secondary, tbody tr label.active-primary, tbody tr label.active-warning').each(function(){
                // this acts as a helper loop if anything
            });

            if (from_session_year != '' && student_class != '') {
                // targetValid is true if session_year isn't empty, and if final year or promote class isn't empty
                let targetValid = session_year != '' && (isFinalYear || promote_class != '');
                if(targetValid && selectedRows.length > 0) {
                    $('.btn_promote').show();
                } else {
                    $('.btn_promote').hide();
                }
            } else {
                $('.btn_promote').hide();
            }
            
            // Manage UI Badges
            selectedRows.forEach(function(row) {
                if(row.promotion_status != 1 && !isFinalYear) {
                    $(`#state_msg_${row.id}`).replaceWith(`<span class="custom-badge badge-primary-light" id="state_msg_${row.id}"><i class="fa fa-clock-o mr-1"></i> Ready</span>`);
                }
            });
            
            // Reset unselected back to Pending or Promoted
            let allRows = $('#promote_student_table_list').bootstrapTable('getData');
            let selectedIds = selectedRows.map(r => r.id);
            allRows.forEach(function(row) {
                if(!selectedIds.includes(row.id)) {
                    if (isFinalYear) {
                        $(`#state_msg_${row.id}`).replaceWith(`<span class="custom-badge badge-success-light" id="state_msg_${row.id}"><i class="fa fa-graduation-cap mr-1"></i> Graduating / Completed</span>`);
                    } else if (row.promotion_status == 1) {
                        $(`#state_msg_${row.id}`).replaceWith(`<span class="custom-badge badge-success-light" id="state_msg_${row.id}"><i class="fa fa-check-circle mr-1"></i> Promoted</span>`);
                    } else {
                        $(`#state_msg_${row.id}`).replaceWith(`<span class="custom-badge badge-secondary-light" id="state_msg_${row.id}">Pending</span>`);
                    }
                }
            });
        }

        $('#student_class_section, #session_year_id, #new_student_class_section').on('change', function () {
            set_data();
        });

        $('#student_class_section').on('change', function(){
            $('#progress-class-name').text($(this).find(':selected').text() || 'Select Class');
        });

        function formSuccessFunction(response) {
            $('#promote_student_table_list').bootstrapTable('refresh');
            $('.btn_promote').hide();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentSelect = document.getElementById('student_class_section');
            const promoteSelect = document.getElementById('new_student_class_section');

            currentSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const selectedClassId = selectedOption?.getAttribute('data-class');

                promoteSelect.value = '';

                Array.from(promoteSelect.options).forEach(option => {
                    option.disabled = false;
                    option.style.display = 'block';
                });

                if (!selectedClassId) return;

                Array.from(promoteSelect.options).forEach(option => {
                    if (option.getAttribute('data-class') === selectedClassId) {
                        option.disabled = true;
                        option.style.display = 'none';
                    }
                });
            });
        });

        // Final year checkbox handle
        $('#final_year_checkbox').on('change', function() {
            let isFinalYear = $(this).is(':checked');
            if (isFinalYear) {
                $('#new_student_class_section').val('').trigger('change');
                $('#new_student_class_section').prop('disabled', true);
                $('#new_student_class_section').removeAttr('required');
                
                // $('#session_year_id').prop('disabled', false); // Ensure target session is selectable
                $('#session_year_id').prop('required', true);

                if ($('#session_year_id option[value="na"]').length > 0) {
                     $('#session_year_id option[value="na"]').remove();
                }
                
                if ($('#new_student_class_section option[value="na"]').length == 0) {
                     $('#new_student_class_section').prepend('<option value="na" selected>-- N/A --</option>');
                } else {
                     $('#new_student_class_section').val('na').trigger('change');
                }

                // Force layout update for checkboxes for Final year mapping
                $('#promote_student_table_list').find('tbody tr').each(function() {
                    let resultBtn = $(this).find('.result-radio:checked').length ? $(this).find('.result-radio:checked')[0] : $(this).find('.result-radio[value="1"]')[0];
                    if (resultBtn) {
                        let rowId = $(resultBtn).attr('id').split('_')[1];
                        updateRowState(rowId, resultBtn, true);
                    }
                });

            } else {                
                $('#new_student_class_section option[value="na"]').remove();
                
                $('#new_student_class_section').prop('disabled', false);
                $('#new_student_class_section').prop('required', true);
                
                $('#from_session_year_id').trigger('change');
                $('#student_class_section').trigger('change');

                // Reload states
                $('#promote_student_table_list').find('tbody tr').each(function() {
                    let resultBtn = $(this).find('.result-radio:checked').length ? $(this).find('.result-radio:checked')[0] : $(this).find('.result-radio[value="1"]')[0];
                    if (resultBtn) {
                        let rowId = $(resultBtn).attr('id').split('_')[1];
                        updateRowState(rowId, resultBtn, false);
                    }
                });
            }
            
            // force table re-render formatters by refreshing data layout
            $('#promote_student_table_list').bootstrapTable('resetView');
            set_data();
        });


        $('#promote_student_table_list').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
            set_data();
        });
        
        $('#promote_student_table_list').on('load-success.bs.table', function (e, data) {
            let total = data.total;
            let rows = data.rows;
            let promoted = 0;
            let pending = 0;
            
            rows.forEach(r => {
                 if(r.promotion_status == 1) promoted++;
                 else pending++;
            });

            // Updating stats UI
            $('#progress-total').text(total);
            $('#progress-promoted').text(promoted);
            $('#progress-pending').text(total - promoted);
            
            let pct = total > 0 ? Math.round((promoted / total) * 100) : 0;
            $('#progress-percentage').text(pct + '%');
            $('#progress-bar-el').css('width', pct + '%').attr('aria-valuenow', pct);

            // School Overall Progress based on strictly DB session year
            let schoolTotal = data.school_total || 0;
            let schoolPromoted = data.school_promoted || 0;
            let schoolPending = data.school_pending || 0;
            
            $('#school-total').text(schoolTotal);
            $('#school-promoted').text(schoolPromoted);
            $('#school-pending').text(schoolPending);
            
            let sPct = schoolTotal > 0 ? Math.round((schoolPromoted / schoolTotal) * 100) : 0;
            $('#school-progress-percentage').text(sPct + '%');
            $('#school-progress-bar').css('width', sPct + '%').attr('aria-valuenow', sPct);

            set_data();
        });

        $('.btn_promote').on('click', function (e) {
            e.preventDefault();
            let table = $('#promote_student_table_list');
            let selectedRows = table.bootstrapTable('getSelections');

            if (selectedRows.length === 0) {
                showErrorToast("{{ __('Please select at least one student') }}");
                return false;
            }

            let allRows = table.bootstrapTable('getData');
            let selectedIds = selectedRows.map(row => row.id);

            table.find('tr[data-index]').each(function () {
                let index = $(this).data('index');
                let row = table.bootstrapTable('getData')[index];
                if (row && !selectedIds.includes(row.id)) {
                    $(this).find('input').attr('disabled', true);
                } else {
                    $(this).find('input').attr('disabled', false);
                }
            });

            $(this).closest('form').submit();

            setTimeout(() => {
                table.find('input').attr('disabled', false);
            }, 1000);
        });
    </script>
@endsection
