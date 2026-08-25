@extends('layouts.master')

@section('title')
    {{ __('email_template') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('email_template') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form id="formdata" class="edit-form-without-reset" action="{{ route('system-settings.email-template.update', 1) }}" method="POST" novalidate="novalidate">
                            @csrf
                            @include('settings.forms.email-template-form')
                            <input class="btn btn-theme float-right" type="submit" value="{{ __('submit') }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        window.onload = setTimeout(() => {
            $('.email-template-tabs .nav-link.active').trigger('click');
        }, 500);
        $(document).on('click', '.email-template-tabs .nav-link', function (e) {
            e.preventDefault();
            let type = $(this).data('value');
            $('.email-template-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            $('.email-template-value').val(type);

            $('.school-email-template, .school-reject-template, .school-inquiry-template').hide(500);
            $('.' + type).show(500);
        });
    </script>
@endsection
