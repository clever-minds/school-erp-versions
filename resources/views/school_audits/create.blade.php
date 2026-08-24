@extends('layouts.master')

@section('title')
    {{ __('create') . ' ' . __('School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('create') . ' ' . __('School Audit') }}
            </h3>
            <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form class="pt-3" action="{{ route('school-audits.store') }}" method="POST">
                            @csrf
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Audit Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required placeholder="e.g. Monthly Academic Audit">
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('School') }} <span class="text-danger">*</span></label>
                                    <select name="school_id" id="school_id" class="form-control" required>
                                        <option value="">{{ __('Select School') }}</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Assign Auditor') }} <span class="text-danger">*</span></label>
                                    <select name="auditor_id" id="auditor_id" class="form-control" required>
                                        <option value="">{{ __('Select School First') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Audit Frequency') }} <span class="text-danger">*</span></label>
                                    <select name="frequency" id="frequency" class="form-control" required>
                                        <option value="">{{ __('Select Frequency') }}</option>
                                        <option value="One-Time">{{ __('One-Time') }}</option>
                                        <option value="Monthly">{{ __('Monthly') }}</option>
                                        <option value="Quarterly">{{ __('Quarterly') }}</option>
                                        <option value="Half Yearly">{{ __('Half Yearly') }}</option>
                                        <option value="Yearly">{{ __('Yearly') }}</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Audit Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="audit_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Due Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                                </div>
                            </div>
                            
                            <div class="row form-group">
                                <div class="col-sm-12">
                                    <label>{{ __('General Remarks') }}</label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="{{ __('General remarks about the audit...') }}"></textarea>
                                </div>
                            </div>

                            <hr>
                            <h4 class="card-title mt-4 mb-4">{{ __('Select Audit Categories') }}</h4>
                            
                            @if(count($categories) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%">
                                                    <input type="checkbox" id="selectAll">
                                                </th>
                                                <th width="5%">#</th>
                                                <th width="45%">{{ __('Category') }}</th>
                                                <th width="45%">{{ __('Total Questions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($categories as $category)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="category-checkbox">
                                                    </td>
                                                    <td>{{ ++$index }}</td>
                                                    <td>
                                                        {{ $category->name }}
                                                    </td>
                                                    <td>
                                                        {{ $category->questions->count() }} Questions
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    {{ __('No active audit categories found. Please add categories first.') }}
                                </div>
                            @endif

                            <div class="row form-group mt-4">
                                <div class="col-sm-12 text-right">
                                    <a href="{{ route('school-audits.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <input class="btn btn-theme" type="submit" value="{{ __('submit') }}">
                                </div>
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
        $(document).ready(function() {
            $('#school_id').change(function() {
                var school_id = $(this).val();
                if (school_id) {
                    var fetchUrl = "{{ url('get-staff-by-school') }}/" + school_id + "?role=Auditer";
                    $.get(fetchUrl, function(data) {
                        var options = '<option value="">{{ __("Select Auditor") }}</option>';
                        data.forEach(function(staff) {
                            options += '<option value="'+staff.id+'">'+staff.first_name+' '+staff.last_name+'</option>';
                        });
                        $('#auditor_id').html(options);
                    }).fail(function() {
                        alert('{{ __("Failed to fetch staff members.") }}');
                    });
                } else {
                    $('#auditor_id').html('<option value="">{{ __("Select School First") }}</option>');
                }
            });

            $('#selectAll').change(function() {
                $('.category-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            $('.category-checkbox').change(function() {
                if ($('.category-checkbox:checked').length === $('.category-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                } else {
                    $('#selectAll').prop('checked', false);
                }
            });
        });
    </script>
@endsection


