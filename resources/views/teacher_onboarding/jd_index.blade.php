@extends('layouts.master')

@section('title')
    {{ __('Teacher JD Management') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Teacher JD Management') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form id="jd-form" method="POST" action="{{ route('teacher-onboarding.jd.store') }}">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('JD Title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="jd-title" class="form-control" required placeholder="Ex: Teacher Job Description">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description / Full JD Content') }} <span class="text-danger">*</span></label>
                                <textarea name="description" id="jd-description" class="form-control" required rows="10" placeholder="Enter full JD text here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-theme">{{ __('Save JD') }}</button>
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
            // Load existing JD
            $.get("{{ route('teacher-onboarding.jd.show') }}", function(data) {
                if (data) {
                    $('#jd-title').val(data.title);
                    $('#jd-description').val(data.description);
                    if (CKEDITOR.instances['jd-description']) {
                        CKEDITOR.instances['jd-description'].setData(data.description);
                    }
                }
            });

            $('#jd-form').submit(function(e) {
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
                        } else {
                            showErrorToast(response.message);
                        }
                    }
                });
            });
        });
        $(document).ready(function() {
            if ($('#jd-description').length) {
                CKEDITOR.replace('jd-description');
            }
        });
    </script>
@endsection
