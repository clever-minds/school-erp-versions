<div class="border border-secondary rounded-lg my-4 mx-1">
    <div class="col-md-12 mt-3">
        <h4>{{ __('email_template_settings') }}</h4>
    </div>
    <div class="col-12 mb-3">
        <hr class="mt-0">
    </div>
    <div class="row my-4 mx-1">
        <label class="col-12">{{ __('template') }} <span class="text-danger">*</span></label>
        <div class="col-12 d-flex row mb-3">
            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input type="radio" class="form-check-input email-template" checked name="template"
                        id="email-template" value="school-email-template" required="required">
                    {{ __('school_register_email_template') }}
                </label>
            </div>

            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input type="radio" class="form-check-input email-template" name="template" id="email-template"
                        value="school-reject-template" required="required">
                    {{ __('school_application_reject_email_template') }}
                </label>
            </div>

            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input type="radio" class="form-check-input email-template" name="template" id="email-template"
                        value="school-inquiry-template" required="required">
                    {{ __('school_inquiry_received_email_template') }}
                </label>
            </div>

        </div>

        <div class="col-12">
            <div class="row school-email-template">
                <div class="form-group col-md-12 col-sm-12">
                    <textarea id="tinymce_school_registration" class="tinymce-editor" name="email_template_school_registration" required
                        placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['email_template_school_registration'] ?? '') }}</textarea>
                </div>
                <div class="form-group col-sm-12 col-md-12">
                    <a data-value="{school_admin_name}" class="btn btn-gradient-light btn_tag mt-2">{
                        {{ __('school_admin_name') }}
                        }</a>
                    <a data-value="{code}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('code') }} }</a>
                    <a data-value="{email}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('email') }} }</a>
                    <a data-value="{password}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('password') }} }</a>
                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }}
                        }</a>
                </div>


                <div class="form-group col-sm-12 col-md-12">
                    <hr>
                    <a data-value="{super_admin_name}" class="btn btn-gradient-light btn_tag mt-2">{
                        {{ __('super_admin_name') }}
                        }</a>
                    <a data-value="{contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('contact') }} }</a>
                    <a data-value="{system_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('system_name') }}
                        }</a>
                    <a data-value="{url}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('url') }} }</a>
                </div>
            </div>

            <div class="row school-reject-template">
                <div class="form-group col-md-12 col-sm-12">
                    <textarea id="tinymce_school_reject" class="tinymce-editor" name="school_reject_template" required
                        placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['school_reject_template'] ?? '') }}</textarea>
                </div>

                <div class="form-group col-sm-12 col-md-12">
                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }}
                        }</a>
                    <a data-value="{super_admin_name}" class="btn btn-gradient-light btn_tag mt-2">{
                        {{ __('super_admin_name') }}
                        }</a>
                    <a data-value="{contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('contact') }} }</a>
                    <a data-value="{system_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('system_name') }}
                        }</a>
                    <a data-value="{url}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('url') }} }</a>
                </div>
            </div>

            <div class="row school-inquiry-template">
                <div class="form-group col-md-12 col-sm-12">
                    <textarea id="tinymce_school_inquiry" class="tinymce-editor" name="school_inquiry_template" required
                        placeholder="{{ __('email_template') }}">{{ htmlspecialchars_decode($settings['school_inquiry_template'] ?? '') }}</textarea>
                </div>

                <div class="form-group col-sm-12 col-md-12">
                    <a data-value="{system_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('system_name') }}
                        }</a>
                    <a data-value="{school_name}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('school_name') }}
                        }</a>
                    <a data-value="{school_email}" class="btn btn-gradient-light btn_tag mt-2">{
                        {{ __('school_email') }} }</a>
                    <a data-value="{contact}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('contact') }} }</a>
                    <a data-value="{address}" class="btn btn-gradient-light btn_tag mt-2">{ {{ __('address') }} }</a>
                </div>
            </div>
        </div>
    </div>
</div>
