@extends('layouts.master')

@section('title')
    {{ __('manage_assignment') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/assignment-submission.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_assignment_submission') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list_assignment_submission') }}
                        </h4>

                        <div class="row" id="toolbar">
                            <div class="form-group col-12 col-sm-12 col-md-3 col-lg-4">
                                <label for="filter-class-section-id" class="filter-menu">{{__("class_section")}}</label>
                                <select name="class_section_id" id="filter-class-section-id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($classSections as $data)
                                        <option value="{{ $data->id }}">
                                            {{ $data->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-12 col-sm-12 col-md-3 col-lg-4">
                                <label for="filter-subject-id" class="filter-menu">{{__("subject")}}</label>
                                <select name="class_subject_id" id="filter-subject-id" class="form-control select2">
                                    <option value="">-- {{ __('Select Subject') }} --</option>
                                    {{-- <option value="data-not-found">-- {{ __('no_data_found') }} --</option> --}}
                                    @foreach ($subjectTeachers as $item)
                                        <option value="{{ $item->class_subject_id }}" 
                                                data-class-section="{{ $item->class_section_id }}"
                                                data-semester-id="{{ isset($item->class_subject) && $item->class_subject ? ($item->class_subject->semester_id ?? '') : '' }}">
                                            {{ $item->subject_with_name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                          
                            

                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('assignment.submission.list') }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-fixed-columns="false" data-fixed-number="2" data-fixed-right-number="1"
                               data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                               data-query-params="AssignmentSubmissionQueryParams" data-sort-order="desc"
                               data-maintain-selected="true" data-export-data-type='all'
                               data-export-options='{ "fileName": "assignment-submission-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}'
                               data-show-export="true" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="false" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="assignment.name" data-sortable="false">{{ __('assignment_name') }}</th>
                                <th scope="col" data-field="assignment.class_section.full_name" data-sortable="false">{{ __('class_section') }}</th>
                                <th scope="col" data-field="assignment.class_subject.subject.name_with_type" data-sortable="false">{{ __('subject') }}</th>
                                <th scope="col" data-field="student" data-formatter="AssignmentSubmissionStudentNameFormatter" data-sortable="false">{{ __('student_name') }}</th>
                                <th scope="col" data-field="file" data-sortable="false" data-formatter="fileFormatter">{{ __('files') }}</th>
                                <th scope="col" data-field="status" data-sortable="false" data-formatter="assignmentSubmissionStatusFormatter">{{ __('status') }}</th>
                                <th scope="col" data-field="points" data-sortable="false">{{ __('points') }}</th>
                                <th scope="col" data-field="feedback" data-sortable="false">{{ __('feedback') }}</th>
                                <th scope="col" data-field="session_year.name" data-sortable="false" data-visible="false">{{ __('Session Year') }}</th>
                                <th scope="col" data-field="created_at"  data-sortable="false" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at"  data-sortable="false" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-events="assignmentSubmissionEvents" data-escape="false">{{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="modal-title text-white" id="exampleModalLabel">
                                    {{ __('review_submission') }}
                                </h4>
                                <p class="modal-subtitle text-white" id="modal-subtitle-text">
                                    {{ __('evaluating_student_work') }}
                                </p>
                            </div>
                            <button type="button" class="close m-0 p-0" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="class-edit-form" id="edit-form" action="{{ url('assignment-submission') }}" novalidate="novalidate">
                            <input type="hidden" name="edit_id" id="edit_id" value=""/>
                            <div class="modal-body p-0">
                                <div class="row no-gutters">
                                    <!-- Left Panel -->
                                    <div class="col-md-8 left-panel p-4">
                                        <!-- Hidden original fields to maintain JS compatibility -->
                                        <div class="d-none">
                                            <input type="text" id="assignment_name">
                                            <input type="text" id="subject">
                                            <input type="text" id="student_name">
                                        </div>

                                        <!-- Student Profile Card -->
                                        <div class="student-profile-card">
                                            <div class="student-avatar-circle" id="student-avatar">M</div>
                                            <div class="student-info">
                                                <h6 id="display-student-name">Student Name</h6>
                                                <div class="student-meta">
                                                    <span id="display-subject-category"><i class="fa fa-book-open mr-1"></i> Subject - Category</span>
                                                    <span class="mx-2">|</span>
                                                    <span id="display-submitted-date"><i class="fa fa-clock mr-1"></i> Submitted Oct 24, 2023</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Attached Files -->
                                        <div class="mb-4">
                                            <div class="section-title">
                                                <i class="fa fa-file-alt"></i> {{ __('attached_files') }}
                                            </div>
                                            <div id="files">
                                                <!-- Populated via JS -->
                                            </div>
                                        </div>

                                        <!-- Submission Link -->
                                        <div>
                                            <div class="section-title">
                                                <i class="fa fa-link"></i> {{ __('submission_link') }}
                                            </div>
                                            <div id="other_link" class="submission-link-container">
                                                <!-- Populated via JS -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Panel -->
                                    <div class="col-md-4 right-panel p-4">
                                        <!-- Submission Status -->
                                        <div class="mb-4">
                                            <div class="section-title">{{ __('submission_status') }}</div>
                                            <div class="status-buttons-grid">
                                                <div>
                                                    <input type="radio" class="edit-status d-none" name="status" id="status_accept" value="1">
                                                    <label for="status_accept" class="status-btn mb-0">
                                                        <i class="fa fa-check-circle"></i>
                                                        <span>{{ __('accept') }}</span>
                                                    </label>
                                                </div>
                                                <div>
                                                    <input type="radio" class="edit-status d-none" name="status" id="status_reject" value="2">
                                                    <label for="status_reject" class="status-btn mb-0">
                                                        <i class="fa fa-times-circle"></i>
                                                        <span>{{ __('reject') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grade Points -->
                                        <div class="form-group mb-4" id="points_div">
                                            <div class="grade-points-group">
                                                <label class="section-title p-0 mb-2">{{ __('grade_points') }}</label>
                                                <span class="max-label mt-3">{{ __('max') }}: <span id="assignment_points_val">0</span></span>
                                                
                                                    <input type="number" name="points" id="points" class="form-control" min="0" placeholder="0.0">
                                                
                                            </div>
                                        </div>

                                        <!-- Feedback -->
                                        <div class="form-group mb-0">
                                            <label class="section-title p-0 mb-2">{{ __('internal_feedback') }}</label>
                                            <textarea name="feedback" id="feedback" placeholder="{{ __('write_specific_comments_here') }}..." class="form-control feedback-textarea"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                                <button class="btn btn-theme" type="submit">
                                    <i class="fa fa-save mr-1"></i> {{ __('submit_review') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('.edit-status').change(function (e) { 
            e.preventDefault();
            var status = $('input[name="status"]:checked').val();
            if (status == 1) {
                $('#points').attr('disabled', false);
                $('#points').attr('readonly', false);
            } else {
                $('#points').val(null);
                $('#points').attr('disabled', true);
            }
        });

        let classSections = @json($classSections);

        $(document).on('change', '#filter-class-section-id', function() {
            let classSectionId = $(this).val();
            let subjectSelect = $('#filter-subject-id');
            let semesterSelect = $('#filter-semester-id');
            let semesterGroup = $('#semester-filter-group');

            // Always reset semester dropdown first when class section changes
            semesterSelect.val('').prop('required', false).trigger('change');
            
            // Reset subject dropdown
            subjectSelect.val('').trigger('change');
            
            if (!classSectionId) {
                // Show all subjects and hide semester filter
                semesterGroup.hide();
                subjectSelect.find('option').show();
                return;
            }

            // Convert to number for comparison
            classSectionId = parseInt(classSectionId);

            // Find the selected class section
            let selectedSection = classSections.find(cs => parseInt(cs.id) == classSectionId);
            if (!selectedSection) {
                semesterGroup.hide();
                subjectSelect.find('option').show();
                return;
            }

            // Check if class has semesters
            let classHasSemesters = selectedSection.class?.include_semesters || false;

            if (classHasSemesters) {
                // Show semester dropdown and make it required
                semesterGroup.show();
                semesterSelect.prop('required', true);
                
                // Hide all subjects until semester is selected (don't show subjects with null semester_id)
                subjectSelect.find('option:not(:first)').hide();
            } else {
                // Hide semester dropdown and show all subjects for this class section
                semesterGroup.hide();
                
                subjectSelect.find('option').each(function() {
                    let $option = $(this);
                    let optionClassSection = parseInt($option.data('class-section')) || 0;
                    if (optionClassSection == classSectionId) {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });
            }
        });

        // Handle semester change to filter subjects
        $(document).on('change', '#filter-semester-id', function() {
            let semesterId = $(this).val();
            let classSectionId = $('#filter-class-section-id').val();
            let subjectSelect = $('#filter-subject-id');

            if (!classSectionId) {
                return;
            }

            // Convert to number for comparison
            classSectionId = parseInt(classSectionId);

            // Reset subject dropdown
            subjectSelect.val('').trigger('change');

            if (!semesterId) {
                // Show all subjects for this class section
                subjectSelect.find('option').each(function() {
                    let $option = $(this);
                    let optionClassSection = parseInt($option.data('class-section')) || 0;
                    if (optionClassSection == classSectionId) {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });
                return;
            }

            // Filter subjects by class section and semester
            // Only show subjects that have the selected semester_id (exclude null semester_id)
            subjectSelect.find('option').each(function() {
                let $option = $(this);
                let optionClassSection = parseInt($option.data('class-section')) || 0;
                let optionSemesterId = $option.data('semester-id');
                
                // Show subject if:
                // 1. It belongs to the selected class section AND
                // 2. It has the selected semester_id (exclude null/empty semester_id)
                if (optionClassSection == classSectionId) {
                    // Convert both to strings for comparison to handle type mismatches
                    let optionSemesterIdStr = String(optionSemesterId || '');
                    let semesterIdStr = String(semesterId || '');
                    
                    // Only show if semester_id matches exactly (exclude null, empty, or undefined)
                    if (optionSemesterIdStr && optionSemesterIdStr != '' && optionSemesterIdStr == semesterIdStr) {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                } else {
                    $option.hide();
                }
            });
        });

        // Initialize on page load
        $(document).ready(function() {
            // If class section is already selected, trigger change to filter subjects
            if ($('#filter-class-section-id').val()) {
                $('#filter-class-section-id').trigger('change');
            } else {
                // If no class section selected, show all subjects
                $('#filter-subject-id').find('option').show();
            }
        });
    </script>
@endsection
