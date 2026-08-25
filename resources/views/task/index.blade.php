@extends('layouts.master')

@section('title')
    {{ __('task') }}
@endsection

@section('css')
    <style>
        .bootstrap-table .fixed-table-container {
            overflow-x: auto !important;
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('task') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="card-title">
                                {{ __('manage_tasks') }}
                            </h4>
                            <button type="button" data-toggle="modal" data-target="#createModal" class="btn btn-theme btn-sm">{{ __('add_new_task') }}</button>
                        </div>
                        <div id="toolbar">
                            <div class="row">
                                @if (Auth::user()->can('task-assign'))
                                    <div class="col-md-3 mb-3">
                                        <label for="filter_type" class="filter-menu">{{ __('task_type') }}</label>
                                        <select name="type" id="filter_type" class="form-control">
                                            <option value="staff_task">{{ __('staff_member_task') }}</option>
                                            <option value="my_task">{{ __('my_tasks') }}</option>
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="type" id="filter_type" value="my_task">
                                @endif
                                <div class="col-md-3 mb-3">
                                    <label for="filter_status" class="filter-menu">{{ __('status') }}</label>
                                    <select name="status" id="filter_status" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        <option value="pending">{{ __('pending') }}</option>
                                        <option value="completed">{{ __('completed') }}</option>
                                        <option value="in_progress">{{ __('in_progress') }}</option>
                                        <option value="overdue">{{ __('overdue') }}</option>
                                    </select>
                                </div>
                                @if (Auth::user()->can('task-assign'))
                                    <div class="col-md-3 mb-3" id="staff_filter_container">
                                        <label for="filter_user_id" class="filter-menu">{{ __('staff') }}</label>
                                        <select name="user_id" id="filter_user_id" class="form-control">
                                            <option value="">{{ __('all') }}</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                data-url="{{ route('tasks.show', [1]) }}" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                data-mobile-responsive="true" data-maintain-selected="true" data-export-data-type='all'
                                data-export-options='{ "fileName": "task-list-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                                data-show-export="true" data-escape="true" data-query-params="taskQueryParams">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-visible="false">{{ __('id') }} </th>
                                        <th scope="col" data-field="no">{{ __('no.') }}</th>
                                        <th scope="col" data-field="title" data-formatter="taskTitleFormatter">{{ __('task_details') }}</th>
                                        <th scope="col" data-field="assignee" data-formatter="assigneeFormatter">{{ __('assignees') }}</th>
                                        <th scope="col" data-field="due_date">{{ __('due_date') }}</th>
                                        <th scope="col" data-field="status" data-formatter="taskStatusFormatter">{{ __('status') }}</th>
                                        <th scope="col" data-field="created_at"> {{ __('created_at') }}</th>
                                        <th scope="col" data-field="updated_at" data-visible="false"> {{ __('updated_at') }}</th>
                                        <th scope="col" data-field="operate" data-events="taskEvents" data-escape="false"> {{ __('action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                </div>
            </div>
        </div>

            <!-- Create Task Modal -->
            <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('create_tasks') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="create-form" action="{{ route('tasks.store') }}" method="POST"
                            novalidate="novalidate">
                            @csrf
                            <div class="modal-body">

                                @if (Auth::user()->can('task-assign'))
                                    <div class="form-group">
                                        <label for="">{{ __('assign_to') }}</label>
                                        <div class="col-12 d-flex row">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input type" checked name="type" value="1"
                                                        required="required">
                                                    {{ __('myself') }}
                                                </label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input type" name="type" value="2"
                                                        required="required">
                                                    {{ __('staff_members') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="type" value="1">
                                    <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                                @endif
                                
                                <div class="form-group">
                                    <label for="">{{ __('title') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('title', null, ['class' => 'form-control title', 'placeholder' => __('title')]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('description') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('description', null, ['class' => 'form-control description', 'placeholder' => __('description')]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('due_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('due_date', null, ['class' => 'form-control datepicker-popup', 'placeholder' => __('due_date')]) !!}
                                </div>

                                <div class="form-group d-none" id="assignee_wrapper">
                                    <label for="">{{ __('assign_to') }} <span class="text-danger">*</span></label>
                                    {!! Form::select('user_id', $users->pluck('full_name', 'id'), Auth::user()->id, ['class' => 'form-control select2', 'placeholder' => __('select_user')]) !!}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit_task') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="edit-form" action="{{ url('tasks') }}" method="POST"
                            novalidate="novalidate">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="id" class="edit_id" value="">

                                @if (Auth::user()->can('task-assign'))
                                    <div class="form-group">
                                        <label for="">{{ __('assign_to') }}</label>
                                        <div class="col-12 d-flex row">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input edit_type" checked name="type" value="1"
                                                        required="required">
                                                    {{ __('myself') }}
                                                </label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input edit_type" name="type" value="2"
                                                        required="required">
                                                    {{ __('staff_members') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="type" value="1">
                                    <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                                @endif
                                
                                <div class="form-group">
                                    <label for="">{{ __('title') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('title', null, ['class' => 'form-control edit_title', 'placeholder' => __('title')]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('description') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('description', null, ['class' => 'form-control edit_description', 'placeholder' => __('description')]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('due_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('due_date', null, ['class' => 'form-control datepicker-popup edit_due_date', 'placeholder' => __('due_date')]) !!}
                                </div>

                                <div class="form-group d-none" id="edit_assignee_wrapper">
                                    <label for="">{{ __('assign_to') }} <span class="text-danger">*</span></label>
                                    {!! Form::select('user_id', $users->pluck('full_name', 'id'), null, ['class' => 'form-control select2 edit_user_id', 'placeholder' => __('select_user')]) !!}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('change', '.edit_type', function() {
            if ($(this).val() == 1) {
                $('#edit_assignee_wrapper').addClass('d-none');
            } else {
                $('#edit_assignee_wrapper').removeClass('d-none');
            }
        });

        $(document).on('change', '.type', function() {
            if ($(this).val() == 1) {
                $('#assignee_wrapper').addClass('d-none');
            } else {
                $('#assignee_wrapper').removeClass('d-none');
            }
        });

        function taskQueryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                type: $('#filter_type').val(),
                status: $('#filter_status').val(),
                user_id: $('#filter_user_id').val(),
            };
        }

        function toggleAssigneeColumn() {
            if ($('#filter_type').val() == 'my_task') {
                $('#table_list').bootstrapTable('hideColumn', 'assignee');
                $('#staff_filter_container').hide();
            } else {
                $('#table_list').bootstrapTable('showColumn', 'assignee');
                $('#staff_filter_container').show();
            }
        }

        $('#filter_type, #filter_status, #filter_user_id').on('change', function() {
            if (this.id == 'filter_type') {
                toggleAssigneeColumn();
            }
            $('#table_list').bootstrapTable('refresh');
        });

        $(function() {
            toggleAssigneeColumn();
        });

        $(document).on('change', '.task-status-dropdown', function() {
            let $this = $(this);
            let id = $this.data('id');
            let status = $this.val();
            let url = baseUrl + '/tasks/update-status/' + id;

            // Update classes immediately for better UX
            $this.removeClass('badge-warning badge-info badge-success badge-danger text-dark text-white');
            if (status == 'pending') $this.addClass('badge-warning badge text-dark');
            else if (status == 'in_progress') $this.addClass('badge-info badge text-white');
            else if (status == 'completed') $this.addClass('badge-success badge text-white');
            else if (status == 'overdue') $this.addClass('badge-danger badge text-white');

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    status: status,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.error) {
                        showErrorToast(response.message);
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        showSuccessToast(response.message);
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function(xhr) {
                    showErrorToast('Something went wrong');
                    $('#table_list').bootstrapTable('refresh');
                }
            });
        });
    </script>
@endsection