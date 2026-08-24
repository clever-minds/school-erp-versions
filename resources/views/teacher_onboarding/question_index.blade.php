@extends('layouts.master')

@section('title')
    {{ __('Teacher Onboarding Test Questions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Teacher Onboarding Test Questions') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Add New Question') }}</h4>
                        <form id="question-form" method="POST" action="{{ route('teacher-onboarding.questions.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Question') }} <span class="text-danger">*</span></label>
                                    <textarea name="question" id="question" required class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Option A') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="option_a" required class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Option B') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="option_b" required class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Option C') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="option_c" required class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Option D') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="option_d" required class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Correct Answer') }} <span class="text-danger">*</span></label>
                                    <select name="answer" required class="form-control">
                                        <option value="a">Option A</option>
                                        <option value="b">Option B</option>
                                        <option value="c">Option C</option>
                                        <option value="d">Option D</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-theme">{{ __('Add Question') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Questions List') }}</h4>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('teacher-onboarding.questions.list') }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                               data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                               data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                               data-maintain-selected="true" data-export-types='["json","xml","csv","txt","pdf","excel"]'
                               data-export-options='{ "fileName": "onboarding-questions-list-<?= date('d-m-y') ?>" }'
                               data-query-params="queryParams">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="question">{{ __('Question') }}</th>
                                <th scope="col" data-field="option_a">{{ __('A') }}</th>
                                <th scope="col" data-field="option_b">{{ __('B') }}</th>
                                <th scope="col" data-field="option_c">{{ __('C') }}</th>
                                <th scope="col" data-field="option_d">{{ __('D') }}</th>
                                <th scope="col" data-field="answer" data-formatter="answerFormatter">{{ __('Ans') }}</th>
                                <th scope="col" data-field="operate" data-events="actionEvents">{{ __('operate') }}</th>
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
                search: p.search
            };
        }

        function answerFormatter(value) {
            return value.toUpperCase();
        }

        window.actionEvents = {
            'click .delete-data': function (e, value, row, index) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('teacher-onboarding/questions') }}/" + row.id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.error == false) {
                                    showSuccessToast(response.message);
                                    $('#table_list').bootstrapTable('refresh');
                                } else {
                                    showErrorToast(response.message);
                                }
                            }
                        });
                    }
                })
            }
        };

        $(document).ready(function() {
            if ($('#question').length) {
                CKEDITOR.replace('question');
            }

            $('#question-form').submit(function(e) {
                e.preventDefault();
                for (var instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }
                let formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.error == false) {
                            showSuccessToast(response.message);
                            $('#question-form')[0].reset();
                            for (var instance in CKEDITOR.instances) {
                                CKEDITOR.instances[instance].setData('');
                            }
                            $('#table_list').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.message);
                        }
                    }
                });
            });
        });
    </script>
@endsection
