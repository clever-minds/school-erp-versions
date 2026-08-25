<!DOCTYPE html>
@php $lang = Session::get('language'); @endphp
@if($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en" dir="ltr">
    @endif
@else
    <html lang="en" dir="ltr">
@endif

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('login') }} || {{ config('app.name') }}</title>

    <link rel="shortcut icon"
        href="{{ $schoolSettings['favicon'] ?? $systemSettings['favicon'] ?? url('assets/vertical-logo.svg') }}" />

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    {{-- Home-page base styles (keeps modal, registration-form styles intact) --}}
    <link href="{{ asset('assets/home_page/css/style.css') }}" rel="stylesheet">

    {{-- Toast notifications --}}
    <link rel="stylesheet" href="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.css') }}">

    <style>
        /* ---------- Root tokens ---------- */
        :root {
            --theme-color:
            <?=$systemSettings['theme_color'] ?? "#22577A" ?> !important;
            --primary: <?=$systemSettings['theme_color'] ?? "#22577A" ?> !important;
            /* --lp-primary: #17c9a2; */
            --lp-primary: var(--theme-color);
            /* teal-green accent */
            --lp-primary-dk: #0faa87;
            /* darker hover shade  */
            --lp-text-main: #1a2332;
            --lp-text-muted: #6b7a8d;
            --lp-border: #e4e9f0;
            --lp-bg-input: #f7f9fc;
            --lp-radius: 10px;
            --lp-shadow-btn: 0 4px 14px rgba(var(--theme-color), 0.35);
        }
    </style>

    {{-- Login-page custom stylesheet --}}
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">

</head>

<body>

<div class="lp-wrapper">

    {{-- =====================================================
         LEFT COLUMN – Login Form
         ===================================================== --}}
    <div class="lp-form-col">
        <div class="lp-form-inner">

            {{-- Heading --}}
            <h1 class="lp-heading">{{__("welcome_back")}}</h1>
            <p class="lp-subheading">{{__("enter_your_credentials_to_access_your_academic_portal")}}</p>

            @if (env('DEMO_MODE'))
                <div class="alert alert-info text-center" role="alert">
                    NOTE : <a target="_blank" href="{{ url('login') }}">-- Click Here --</a>
                    if you cannot login.
                </div>
            @endif

            {{-- Flash alerts --}}
            @if (Session::has('success'))
                <div class="lp-alert lp-alert-success">{{ Session::get('success') }}</div>
            @endif

            @if (Session::has('error'))
                <div class="lp-alert lp-alert-danger">{{ Session::get('error') }}</div>
            @endif

            {{-- ============== LOGIN FORM ============== --}}
            <form action="{{ route('login') }}" method="POST" id="loginForm">
                @csrf

                {{-- Email --}}
                <div class="lp-input-group">
                    <label class="lp-label" for="email">{{ __('email_address') }}</label>
                    <div class="lp-input-wrapper">
                        <span class="lp-input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="text" class="lp-input" value="{{ isset($school) && !empty($school) && $school->type == 'demo' ? $school->user->email : old('email') }}" id="email" name="email" placeholder="name@example.com" required autofocus>
                    </div>
                </div>

                {{-- Password --}}
                <div class="lp-input-group">
                    <label class="lp-label" for="password">{{ __('password') }}</label>
                    <div class="lp-input-wrapper">
                        <span class="lp-input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" value="{{ isset($school) && !empty($school) && $school->type == 'demo' ? $school->user->mobile : '' }}" class="lp-input" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="lp-pw-toggle" id="togglePassword" aria-label="Toggle password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                {{-- School Code --}}
                @if ($school ?? '')
                    {{-- School is preset – render hidden so it posts correctly --}}
                    <div class="d-none">
                        <input type="text"
                               id="school_code"
                               name="code"
                               value="{{ $school->code }}"
                               autocomplete="off">
                    </div>
                @else
                    <div class="lp-input-group">
                        <div class="lp-school-code-header">
                            <label class="lp-label mb-0" for="school_code">{{ __('school_code') }}</label>
                            <span class="lp-whats-this"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  title="{{ __('Enter your unique school code provided during registration. This code identifies your school in the system.') }}">
                                {{ __("What's this?") }}
                            </span>
                        </div>
                        <div class="lp-input-wrapper">
                            <span class="lp-input-icon"><i class="fas fa-building"></i></span>
                            <input type="text"
                                   class="lp-input"
                                   id="school_code"
                                   name="code"
                                   placeholder="SCH-XXXX">
                        </div>
                    </div>
                @endif

                {{-- Forgot password --}}
                <div class="lp-forgot">
                    <a href="{{ route('password.request') }}">{{ __('forgot_password') }}</a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="lp-btn-signin">
                    {{ __('sign_in') }}&nbsp;<i class="fas fa-arrow-right"></i>
                </button>

                {{-- Sign-up link --}}
                <div class="lp-signup-link">
                    <a href="#"
                       data-bs-toggle="modal"
                       data-bs-dismiss="offcanvas"
                       data-bs-target="#staticBackdrop">
                        {{ __('New user Sign up to manage your school activities seamlessly') }}
                    </a>
                </div>
            </form>

            {{-- ============== DEMO CREDENTIALS ============== --}}
            @if (env('DEMO_MODE'))
                <div class="lp-demo-divider">{{__("demo_access")}}</div>

                @if (empty($school) ?? '')
                    <p class="lp-demo-group-label text-center">{{__("super_admin_panels")}}</p>
                    <div class="lp-demo-btns justify-content-center">
                        <button class="lp-demo-btn lp-demo-btn-teal" id="superadmin_btn">
                            <i class="fas fa-shield-alt"></i> {{__("super_admin")}}
                        </button>
                        <button class="lp-demo-btn lp-demo-btn-indigo" id="superadmin_staff_btn">
                            <i class="fas fa-users"></i> {{__("staff")}}
                        </button>
                    </div>
                @endif

                <p class="lp-demo-group-label text-center">{{__("school_panels")}}</p>
                <div class="lp-demo-btns justify-content-center">
                    <button class="lp-demo-btn lp-demo-btn-admin" id="schooladmin_btn">
                        <i class="fas fa-user-shield"></i> {{__("school_admin")}}
                    </button>
                    <button class="lp-demo-btn lp-demo-btn-teacher" id="teacher_btn">
                        <i class="fas fa-chalkboard-teacher"></i> {{__("teacher")}}
                    </button>
                    <button class="lp-demo-btn lp-demo-btn-staff" id="schooladmin_staff_btn">
                        <i class="fas fa-user-tie"></i> {{__("staff")}}
                    </button>
                </div>
            @endif

        </div>{{-- /.lp-form-inner --}}
    </div>{{-- /.lp-form-col --}}


    {{-- =====================================================
         RIGHT COLUMN – Brand / Photo Panel
         ===================================================== --}}
    @php
        $loginPageLogo = null;
        if (isset($school) && isset($schoolSettings['login_page_logo']) && !empty($schoolSettings['login_page_logo'])) {
            $loginPageLogo = $schoolSettings['login_page_logo'];
        } elseif (isset($systemSettings['login_page_logo']) && !empty($systemSettings['login_page_logo'])) {
            $loginPageLogo = $systemSettings['login_page_logo'];
        }
    @endphp

    <div class="lp-brand-col"
         @if($loginPageLogo)
             style="background-image: url('{{ $loginPageLogo }}'); background-size: cover; background-position: center;"
         @endif>
    </div>{{-- /.lp-brand-col --}}

</div>{{-- /.lp-wrapper --}}


{{-- Registration modal (keep intact) --}}
@include('registration_form')


{{-- =====================================================
     Scripts (order preserved from original)
     ===================================================== --}}
<script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
<script src="{{ asset('/assets/js/custom/common.js') }}"></script>
<script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('/assets/js/custom/function.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
<script async src="https://www.google.com/recaptcha/api.js"></script>
<script>
    /* ---------- jQuery Validate ---------- */
    $("#loginForm").validate({
        rules: {
            email: "required",
            password: "required"
        },
        success: function (label, element) {
            $(element).parent().removeClass('has-danger');
            $(element).removeClass('form-control-danger');
        },
        errorPlacement: function (label, element) {
            if (label.text()) {
                label.addClass('mt-1 text-danger d-block');
                label.insertAfter(element.parent());
            }
        },
        highlight: function (element, errorClass) {
            $(element).parent().addClass('has-danger');
            $(element).addClass('form-control-danger');
        }
    });

    /* ---------- Password toggle ---------- */
    (function () {
        const toggle = document.getElementById('togglePassword');
        const input  = document.getElementById('password');
        if (!toggle || !input) return;

        toggle.addEventListener('click', function () {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            const icon = toggle.querySelector('i');
            icon.classList.toggle('fa-eye-slash', !isPassword);
            icon.classList.toggle('fa-eye', isPassword);
        });
    }());

    /* ---------- Bootstrap tooltips ---------- */
    $(function () {
        var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });
    });

    // create a function all buttons will disabled after click any button
    function disableAllButtons() {
        $('button').prop('disabled', true);
    }

    $('.lp-btn-signin').on('click', function () {
        disableAllButtons();
        $('#loginForm').submit();
    });

    /* ---------- Demo credentials ---------- */
    @if (env('DEMO_MODE'))
        $('#superadmin_btn').on('click', function () {
            $('#email').val('superadmin@gmail.com');
            $('#password').val('superadmin');
            disableAllButtons();
            $('#loginForm').submit();
        });

        $('#superadmin_staff_btn').on('click', function () {
            $('#email').val('mahesh@gmail.com');
            $('#password').val('staff@123');
            disableAllButtons();
            $('#loginForm').submit();
        });

        $('#schooladmin_btn').on('click', function () {
            $('#email').val('school1@gmail.com');
            $('#password').val('school@123');
            $('#school_code').val('SCH202412');
            disableAllButtons();
            $('#loginForm').submit();
        });

        $('#teacher_btn').on('click', function () {
            $('#email').val('teacher@gmail.com');
            $('#password').val('0111111111');
            $('#school_code').val('SCH202412');
            disableAllButtons();
            $('#loginForm').submit();
        });

        $('#schooladmin_staff_btn').on('click', function () {
            $('#email').val('smitc@gmail.com');
            $('#password').val('965555885');
            $('#school_code').val('SCH202412');
            disableAllButtons();
            $('#loginForm').submit();
        });
    @endif

    const please_wait             = "{{ __('Please wait') }}";
    const processing_your_request = "{{ __('Processing your request') }}";
</script>

</body>

{{-- Toast messages --}}
@if (Session::has('error'))
    <script type="text/javascript">
        $.toast({
            text: '{{ Session::get('error') }}',
            showHideTransition: 'slide',
            icon: 'error',
            loaderBg: '#f2a654',
            position: 'top-right'
        });
    </script>
@endif

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script type="text/javascript">
            $.toast({
                text: '{{ $error }}',
                showHideTransition: 'slide',
                icon: 'error',
                loaderBg: '#f2a654',
                position: 'top-right'
            });
        </script>
    @endforeach
@endif

</html>