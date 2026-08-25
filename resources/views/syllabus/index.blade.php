@extends('layouts.master')

@section('title')
    {{ __('syllabus') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_syllabus') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-12 col-sm-12 col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create_syllabus') }}
                        </h4>
                        <form class="pt-3 section-create-form" id="create-form" action="{{route('syllabus.store')}}" method="POST" novalidate="novalidate">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="class_id">{{ __('class') }} <span class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id" class="form-control" required>
                                        <option value="">{{ __('select_class') }}</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}" data-medium-id="{{ $class->medium_id }}">{{ $class->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="subject_id">{{ __('subject') }} <span class="text-danger">*</span></label>
                                    <select name="subject_id" id="subject_id" class="form-control" required>
                                        <option value="">{{ __('select_subject') }}</option>
                                        @foreach ($subjects as $subject)
                                            <option value="{{ $subject->id }}" data-medium-id="{{ $subject->medium_id }}">{{ $subject->full_name_with_medium }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="title">{{ __('title') }} <span class="text-danger">*</span></label>
                                    <input name="title" id="title" type="text" placeholder="{{ __('title') }}" class="form-control" required/>
                                </div>
                            </div>
                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                            <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list_syllabus') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="class_id" class="filter-menu">{{ __('class') }} <span class="text-danger">*</span></label>
                                <select name="class_id" id="filter_class_id" class="form-control" required>
                                    <option value="">{{ __('select_class') }}</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" data-medium-id="{{ $class->medium_id }}">{{ $class->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-sm-12 col-md-4">
                                <label for="subject_id" class="filter-menu">{{ __('subject') }} <span class="text-danger">*</span></label>
                                <select name="subject_id" id="filter_subject_id" class="form-control" required>
                                    <option value="">{{ __('select_subject') }}</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}" data-medium-id="{{ $subject->medium_id }}">{{ $subject->full_name_with_medium }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('syllabus.show', [1]) }}" data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                               data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-fixed-columns="false" data-fixed-number="2" data-fixed-right-number="1"
                               data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                               data-sort-order="desc" data-maintain-selected="true" data-query-params="syllabusQueryParams"
                               data-show-export="true"
                               data-export-options='{"fileName": "shift-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}'
                               data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{__('id')}}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{__('no.')}}</th>
                                <th scope="col" data-field="class.full_name" data-sortable="false">{{__('class_name')}}</th>
                                <th scope="col" data-field="subject.name_with_type" data-sortable="false">{{__('subject_name')}}</th>
                                <th scope="col" data-field="title" data-sortable="false">{{__('title')}}</th>
                                <th scope="col" data-field="lesson_common_count" data-sortable="false">{{__('lesson_count')}}</th>
                                <th scope="col" data-field="status" data-sortable="false" data-formatter="activeStatusLabelFormatter">{{__('status')}}</th>
                                <th scope="col" data-field="created_at"  data-sortable="true" data-visible="false">{{__('created_at')}}</th>
                                <th scope="col" data-field="updated_at"  data-sortable="true" data-visible="false">{{__('updated_at')}}</th>
                                <th scope="col" data-field="operate" data-sortable="false" data-events="syllabusEvents" data-escape="false">{{__('action')}}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{__('edit_syllabus')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="edit-form" action="{{ url('syllabus') }}" novalidate="novalidate">
                            <input type="hidden" name="edit_id" id="edit_id" value=""/>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label for="edit_class_id">{{ __('class') }} <span class="text-danger">*</span></label>
                                        <select name="class_id" id="edit_class_id" class="form-control" required>
                                            <option value="">{{ __('select_class') }}</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" data-medium-id="{{ $class->medium_id }}">{{ $class->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label for="edit_subject_id">{{ __('subject') }} <span class="text-danger">*</span></label>
                                        <select name="subject_id" id="edit_subject_id" class="form-control" required>
                                            <option value="">{{ __('select_subject') }}</option>
                                            @foreach ($subjects as $subject)
                                            <option value="{{ $subject->id }}" data-medium-id="{{ $subject->medium_id }}">{{ $subject->full_name_with_medium }}</option>
                                        @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-12">
                                        <label for="edit_title">{{ __('title') }} <span class="text-danger">*</span></label>
                                        <input name="title" id="edit_title" type="text" placeholder="{{ __('title') }}" class="form-control" required/>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('close')}}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
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
        // onchange #class_id and #subject_id then i want add default name like this format "Class Name - Subject Name"
        $('#class_id,#subject_id').on('change', function() {
            var className = $('#class_id option:selected').text();
            var subjectName = $('#subject_id option:selected').text();
            $('#title').val(className + ' - ' + subjectName);
        });

        $('#class_id, #edit_class_id, #filter_class_id').on('change', function() {
            var mediumId = $(this).find(':selected').data('medium-id');
            var targetSubjectId = '';
            if ($(this).attr('id') == 'class_id') {
                targetSubjectId = '#subject_id';
            } else if ($(this).attr('id') == 'edit_class_id') {
                targetSubjectId = '#edit_subject_id';
            } else {
                targetSubjectId = '#filter_subject_id';
            }

            $(targetSubjectId + ' option').each(function() {
                if ($(this).val() == '') {
                    $(this).show();
                    return;
                }
                if (!mediumId || $(this).data('medium-id') == mediumId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Trigger change for initial filtering if needed
        $('#class_id, #edit_class_id, #filter_class_id').trigger('change');
    </script>
@endsection
