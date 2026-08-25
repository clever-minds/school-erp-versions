@extends('layouts.master')

@section('title')
    {{ __('Class Subject')}}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/class-subject.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('Class Subject')}}
            </h3>
        </div>

        <select id="syllabus-template" style="display: none;">
            <option value="">{{ __('select_syllabus') }}</option>
            @foreach ($syllabus as $sy)
                <option value="{{ $sy->id }}" data-class-id="{{ $sy->class_id }}" data-subject-id="{{ $sy->subject_id }}">{{ $sy->title }}</option>
            @endforeach
        </select>

        <div class="class-info-box mb-4">
            <div class="info-block">
                <div class="info-icon-wrap bg-light-blue">
                    <i class="fa fa-book"></i>
                </div>
                <div class="info-text-wrap">
                    <span class="info-label">{{__("Class")}}</span>
                    <span class="info-value">{{$class->full_name}}</span>
                </div>
            </div>
            <div class="info-divider"></div>
            <div class="info-block">
                <div class="info-icon-wrap {{ $class->include_semesters ? 'bg-light-green' : 'bg-light-red' }}">
                    <i class="fa {{ $class->include_semesters ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                </div>
                <div class="info-text-wrap">
                    <span class="info-label">{{__("Semester Included")}}</span>
                    <span class="info-value">
                        @if($class->include_semesters)
                            {{ __("Yes") }}
                        @else
                            {{ __("No") }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="card-body p-4">
                <form class="edit-class-subject-validate-form" data-success-function="formSuccessFunction" data-pre-submit-function="classValidation" id="edit-form" action="{{ route('class.subject.update',[$id]) }}" novalidate="novalidate">
                    
                    <!-- Core Subjects Section -->
                    <div class="section-container mb-5">
                        <h4 class="section-title" title="{{ __('Core Subjects are the Compulsory Subject') }}.">
                            {{ __('Core Subjects') }} <i class="fa fa-info-circle"></i>
                        </h4>
                        
                        <div class="core-subject-repeater">
                            <div data-repeater-list="core_subject">
                                <div class="core-subject-row row align-items-end" data-repeater-item>
                                    @if($class->include_semesters)
                                        <div class="col-md-3 semester-div">
                                            <div class="form-group mb-2">
                                                <label class="label-custom">{{__("Semester")}}</label>
                                                <select name="semester_id" class="form-control-custom semesters w-100" required>
                                                    <option value="" hidden="">-- {{__("Select Semester")}} --</option>
                                                    @foreach ($semesters as $semester)
                                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="col-md-4">
                                        <div class="form-group mb-2">
                                            <input type="hidden" name="class_subject_id" class="class_subject_id"/>
                                            <label class="label-custom">{{ __('Subject Name') }}</label>
                                            <select name="id" class="form-control-custom subject w-100" required>
                                                <option value="">{{ __('Select Subject') }}</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}">{{ $subject->name }} - {{ __($subject->type)}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group mb-2">
                                            <label class="label-custom">{{ __('Syllabus') }}</label>
                                            <select name="syllabus_id" class="form-control-custom syllabus w-100" required>
                                                <option value="">{{ __('select_syllabus') }}</option>
                                                @foreach ($syllabus as $sy)
                                                    <option value="{{ $sy->id }}" data-class-id="{{ $sy->class_id }}" data-subject-id="{{ $sy->subject_id }}">{{ $sy->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-1 text-center mb-2">
                                        <button data-repeater-delete type="button" class="btn-remove-row" title="{{__('Remove Core Subject')}}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn-add-outline" data-repeater-create>
                                    <i class="fa fa-plus-circle mr-1"></i> {{ __('Add Core Subject') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5">

                    <!-- Elective Subjects Section -->
                    <div class="section-container">
                        <h4 class="section-title" title="{{ __('Elective Subjects are the subjects where student have the choice to select the subject from the given subjects') }}.">
                            {{ __('elective_subject') }} <i class="fa fa-info-circle"></i>
                        </h4>

                        <div class="elective-subject-group-repeater">
                            <div data-repeater-list="elective_subject_group">
                                <div data-repeater-item class="elective-group-card elective-subject-group">
                                    <input type="hidden" name="id" class="class_subject_group_id"/>
                                    
                                    <div class="group-header">
                                        <span class="group-badge group-no">{{ __('Group') }}</span>
                                        <button data-repeater-delete type="button" class="btn btn-link text-danger p-0 font-weight-bold" title="Delete Subject Group">
                                            <i class="fa fa-trash mr-1"></i> {{ __('Delete Group') }}
                                        </button>
                                    </div>

                                    @if($class->include_semesters)
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label class="label-custom">{{__("Semester")}}</label>
                                                    <select name="semester_id" class="form-control-custom semesters w-100" required>
                                                        <option value="" hidden="">-- {{__("Select Semester")}} --</option>
                                                        @foreach($semesters as $semester)
                                                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="elective-subject-repeater">
                                        <div class="option-container" data-repeater-list="subject">
                                            <div data-repeater-item class="d-flex align-items-stretch elective-subject">
                                                <div class="elective-option-card">
                                                    <input type="hidden" name="class_subject_id" class="class_subject_id"/>
                                                    
                                                    <div class="form-group">
                                                        <label class="label-custom">{{__('subject')}}</label>
                                                        <select name="id" class="form-control-custom subject w-100" required>
                                                            <option value="">{{ __('Select Subject') }}</option>
                                                            @foreach ($subjects as $subject)
                                                                <option value="{{ $subject->id }}">{{ $subject->name }} - {{ __($subject->type)}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-0">
                                                        <label class="label-custom">{{__('syllabus')}}</label>
                                                        <select name="syllabus_id" class="form-control-custom syllabus w-100" required>
                                                            <option value="">{{ __('select_syllabus') }}</option>
                                                            @foreach ($syllabus as $sy)
                                                                <option value="{{ $sy->id }}" data-class-id="{{ $sy->class_id }}" data-subject-id="{{ $sy->subject_id }}">{{ $sy->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <button data-repeater-delete type="button" class="btn-remove-row remove-elective-subject position-absolute" style="top: 5px; right: 5px;" title="Delete Subject">
                                                        <i class="fa fa-times-circle"></i>
                                                    </button>
                                                </div>
                                                <div class="or-divider mx-2">{{ __('or') }}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 d-inline-block">
                                            <div data-repeater-create class="add-option-box add-new-elective-subject">
                                                <i class="fa fa-plus-circle"></i>
                                                <span>{{ __('Add Option') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4 align-items-center">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="label-custom">{{ __('total_selectable_subjects') }} <span class="text-danger">*</span></label>
                                                <input name="total_selectable_subjects" type="text" placeholder="{{ __('total_selectable_subjects') }}" class="form-control-custom total_selectable_subjects w-100" min="1" required/>
                                            </div>
                                            <small class="text-muted mt-2 d-block">({{ __('Students must pick X out of Y options') }})</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button data-repeater-create type="button" class="btn btn-inverse-success font-weight-bold p-3">
                                    <i class="fa fa-plus-circle mr-1"></i> {{ __('Add Elective Group') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pb-4 text-right">
                        <button class="btn btn-theme" id="create-btn" type="submit">
                            {{ __('submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            @if($class->core_subjects)
            coreSubject.setList([
                    @foreach($class->core_subjects as $key=>$coreSubject)
                {
                    id: "{{$coreSubject->id}}",
                    class_subject_id: "{{$coreSubject->class_subject_id}}",
                    semester_id: "{{$coreSubject->pivot->semester_id ?? ''}}",
                    syllabus_id: "{{$coreSubject->pivot->syllabus_id ?? ''}}",
                },
                @endforeach
            ]);
            @endif

            @if($class->elective_subject_groups)
            electiveSubjectGroupRepeater.setList([
                    @foreach($class->elective_subject_groups as $key=>$group)
                {
                    subject: [
                            @foreach($group->subjects as $subjects)
                        {
                            id: "{{$subjects->id}}",
                            class_subject_id: "{{$subjects->class_subject_id}}",
                            syllabus_id: "{{$subjects->pivot->syllabus_id ?? ''}}",

                        },
                        @endforeach
                    ],
                    id: "{{$group->id}}",
                    total_selectable_subjects: "{{$group->total_selectable_subjects}}",
                    semester_id: "{{$group->semester_id}}",
                },
                @endforeach
            ])
            @endif

            /* This line will auto add Group id to subjects */
            $('.semesters').trigger('change');

            // Dynamic Syllabus Filtering Based on Subject Selection
            window.originalSyllabusOptions = $('#syllabus-template').find('option').clone();

            $(document).on('change', '.subject', function() {
                let subjectId = $(this).val();
                let $row = $(this).closest('.core-subject-row, .elective-subject');
                let $syllabusDropdown = $row.find('.syllabus');
                
                // Get current value to persist after filtering
                let currentSyllabus = $syllabusDropdown.val();

                $syllabusDropdown.empty();
                $syllabusDropdown.append(window.originalSyllabusOptions.filter('[value=""]').clone());

                if (subjectId) {
                    window.originalSyllabusOptions.filter(function() {
                        return $(this).attr('data-subject-id') == subjectId;
                    }).clone().appendTo($syllabusDropdown);
                }

                if (currentSyllabus && $syllabusDropdown.find(`option[value="${currentSyllabus}"]`).length > 0) {
                    $syllabusDropdown.val(currentSyllabus);
                }
            });

            // Trigger change on load to set initial filter for existing subjects
             setTimeout(() => {
                $('.subject').each(function() {
                    $(this).trigger('change');
                });
             }, 100);

            // Robust OR divider visibility logic
            function updateOrDividers(container) {
                const dividers = $(container).find('.or-divider');
                dividers.removeClass('d-none');
                dividers.last().addClass('d-none');
            }

            // Initialize existing dividers
            $('.option-container').each(function() {
                updateOrDividers(this);
            });

            // Observe changes in option containers for dynamic updates
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        updateOrDividers(mutation.target);
                    }
                });
            });

            // Start observing each container
            $('.option-container').each(function() {
                observer.observe(this, { childList: true });
            });

            // Fallback for click events if needed
            $(document).on('click', '[data-repeater-create], [data-repeater-delete]', function() {
                setTimeout(() => {
                    $('.option-container').each(function() {
                        updateOrDividers(this);
                    });

                    // Ensure syllabus dropdowns in newly added empty rows are filtered correctly
                    $('.subject').each(function() {
                        if (!$(this).val()) {
                            let $syllabusDropdown = $(this).closest('.core-subject-row, .elective-subject').find('.syllabus');
                            $syllabusDropdown.empty();
                            $syllabusDropdown.append(window.originalSyllabusOptions.filter('[value=""]').clone());
                        }
                    });
                }, 100);
            });
        });

        function formSuccessFunction() {
            window.location.href = "{{route('class.subject.index')}}"
        }
    </script>
@endsection
