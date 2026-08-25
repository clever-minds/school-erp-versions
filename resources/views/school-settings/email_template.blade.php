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
                        <form id="formdata1" class="school-email-template" action="{{ route('school-settings.email-template.update', 1) }}" method="POST" novalidate="novalidate">
                            @csrf


                            <div class="form-group">
                                <label>{{ __('template') }} <span class="text-danger">*</span></label>
                                <div class="col-12">
                                    <input type="hidden" name="template" class="email-template-value" value="staff-template">
                                    <ul class="nav nav-tabs email-template-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-value="staff-template" href="javascript:void(0)" role="tab">
                                                {{ __('staff-email-template') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-value="parent-template" href="javascript:void(0)" role="tab">
                                                {{ __('parent-email-template') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-value="reject-template" href="javascript:void(0)" role="tab">
                                                {{ __('application-reject-email-template') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="row staff-email-template">
                                <div class="form-group col-md-12 col-sm-12">
                                    <textarea id="tinymce_message" name="staff_data" id="data" required placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['email-template-staff'] ?? '') }}</textarea>
                                </div>

                                <div class="form-group col-sm-12 col-md-12">
                                    <a data-value="{full_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('full_name') }} }</a>
                                    <a data-value="{code}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('code') }} }</a>
                                    <a data-value="{email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('email') }} }</a>
                                    <a data-value="{password}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('password') }} }</a>
                                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }} }</a>
                                    <a data-value="{android_app}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('android_app') }} }</a>
                                    <a data-value="{ios_app}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('ios_app') }} }</a>
                                    <a data-value="{url}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('url') }} }</a>
                                    <a data-value="{support_email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_email') }} }</a>
                                    <a data-value="{support_contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_contact') }} }</a>
                                </div>
                            </div>

                            <div class="row parent-email-template">
                                <div class="form-group col-md-12 col-sm-12">
                                    <textarea id="tinymce_message" name="parent_data" id="data" required placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['email-template-parent'] ?? '') }}</textarea>
                                </div>

                                <div class="form-group col-sm-12 col-md-12">
                                    <a data-value="{parent_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('parent_name') }} }</a>
                                    <a data-value="{code}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('code') }} }</a>
                                    <a data-value="{email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('email') }} }</a>
                                    <a data-value="{password}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('password') }} }</a>
                                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }} }</a>

                                    <a data-value="{child_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('child_name') }} }</a>
                                    <a data-value="{grno}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('Gr Number') }} }</a>
                                    <a data-value="{child_password}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('child_password') }} }</a>
                                    <a data-value="{admission_no}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('admission_no') }} }</a>
                                    <a data-value="{android_app}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('android_app') }} }</a>
                                    <a data-value="{ios_app}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('ios_app') }} }</a>
                                    <a data-value="{support_email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_email') }} }</a>
                                    <a data-value="{support_contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_contact') }} }</a>

                                </div>
                            </div>

                            <div class="row reject-email-template">
                                <div class="form-group col-md-12 col-sm-12">
                                    <textarea id="tinymce_message" name="reject_email_data" id="data" required placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['email-template-application-reject'] ?? '') }}</textarea>
                                </div>

                                <div class="form-group col-sm-12 col-md-12">
                                    <a data-value="{parent_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('parent_name') }} }</a>
                                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }} }</a>
                                    <a data-value="{child_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('child_name') }} }</a>
                                    <a data-value="{support_email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_email') }} }</a>
                                    <a data-value="{support_contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('support_contact') }} }</a>
                                    <a data-value="{class}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('class') }} }</a>
                                </div>
                            </div>

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
        let templateMap = {
            'staff-template': 'staff-email-template',
            'parent-template': 'parent-email-template',
            'reject-template': 'reject-email-template'
        };
        $(document).on('click', '.email-template-tabs .nav-link', function (e) {
            e.preventDefault();
            let type = $(this).data('value');
            $('.email-template-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            $('.email-template-value').val(type);

            $('.staff-email-template, .parent-email-template, .reject-email-template').hide(500);
            $('.' + templateMap[type]).show(500);
        });
    </script>
@endsection