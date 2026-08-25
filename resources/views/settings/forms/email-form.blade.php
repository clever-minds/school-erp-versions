<div class="border border-secondary rounded-lg my-4 mx-1">
    <div class="col-md-12 mt-3">
        <h4>{{ __('email_settings') }}</h4>
    </div>
    <div class="col-12 mb-3">
        <hr class="mt-0">
    </div>
    <div class="row my-4 mx-1">
        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_mailer">{{ __('mail_mailer') }}</label>
            <select required name="mail_mailer" id="mail_mailer" class="form-control select2" style="width:100%;"
                tabindex="-1" aria-hidden="true">
                <option value="">--- Select Mailer ---</option>
                <option {{ env('MAIL_MAILER') == 'smtp' ? 'selected' : '' }} value="smtp">SMTP</option>
                <option {{ env('MAIL_MAILER') == 'mailgun' ? 'selected' : '' }} value="mailgun">Mailgun</option>
                <option {{ env('MAIL_MAILER') == 'sendmail' ? 'selected' : '' }} value="sendmail">sendmail</option>
                <option {{ env('MAIL_MAILER') == 'postmark' ? 'selected' : '' }} value="postmark">Postmark</option>
                <option {{ env('MAIL_MAILER') == 'amazon_ses' ? 'selected' : '' }} value="amazon_ses">Amazon SES
                </option>
            </select>
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_host">{{ __('mail_host') }}</label>
            <input name="mail_host" id="mail_host" value="{{ env('MAIL_HOST') }}" type="text" required
                placeholder="{{ __('mail_host') }}" class="form-control" />
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_port">{{ __('mail_port') }}</label>
            <input name="mail_port" id="mail_port" value="{{ env('MAIL_PORT') }}" type="text" required
                placeholder="{{ __('mail_port') }}" class="form-control" />
        </div>

        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_username">{{ __('mail_username') }}</label>
            <input name="mail_username" id="mail_username" value="{{ env('MAIL_USERNAME') }}" type="text" required
                placeholder="{{ __('mail_username') }}" class="form-control" />
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <label for="password">{{ __('mail_password') }}</label>
            <div class="input-group">
                <input id="mail_password" name="mail_password" value="{{ env('MAIL_PASSWORD') }}" type="password"
                    required placeholder="{{ __('mail_password') }}" class="form-control" />
                <div class="input-group-append togglePasswordShowHide" style="cursor: pointer;">
                    <span class="input-group-text">
                        <i class="fa fa-eye-slash"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_encryption">{{ __('mail_encryption') }}</label>
            <input name="mail_encryption" id="mail_encryption" value="{{ env('MAIL_ENCRYPTION') }}" type="text"
                required placeholder="{{ __('mail_encryption') }}" class="form-control" />
        </div>

        <div class="form-group col-md-4 col-sm-12">
            <label for="mail_send_from">{{ __('mail_send_from') }}</label>
            <input name="mail_send_from" id="mail_send_from" value="{{ env('MAIL_FROM_ADDRESS') }}" type="text"
                required placeholder="{{ __('mail_send_from') }}" class="form-control" />
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '.togglePasswordShowHide', function() {
            const password = $(this).siblings('input');
            const icon = $(this).find('i');

            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });
    });
</script>
