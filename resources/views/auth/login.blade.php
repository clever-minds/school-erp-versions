<!DOCTYPE html>
<html lang="en">
@php
    $lang = Session::get('language');
@endphp
@if($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en" dir="ltl">
    @endif
@else
    <html lang="en" dir="ltl">
@endif

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="{{ asset('assets/home_page/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.css') }}">


    <title>{{ __('login') }} || {{ config('app.name') }}</title>

    <link rel="shortcut icon"
        href="{{$schoolSettings['favicon'] ?? $systemSettings['favicon'] ?? url('assets/vertical-logo.svg') }}" />

    <style>
        :root {
            --primary-color: #56cc99;
            --secondary-color: #215679;
            --secondary-color-1: #38a3a5;
            --primary-bg: #f2f5f7;
            --text-secondary: #5c788c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-y: auto;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
        }

        /* Left Panel - Brand Showcase */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color-1) 50%, var(--secondary-color) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: movePattern 20s linear infinite;
        }

        @keyframes movePattern {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .brand-logo img {
            max-width: 80px;
            max-height: 80px;
        }

        .brand-content h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .brand-content p {
            font-size: 20px;
            opacity: 0.95;
            font-weight: 300;
        }

        /* Right Panel - Login Form */
        .form-panel {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 32px;
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .login-container .form-group {
            margin-bottom: 25px;
        }

        .login-container .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--secondary-color);
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
        }

        .login-container .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .login-container .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(86, 204, 153, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--secondary-color-1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color-1));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(86, 204, 153, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(86, 204, 153, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--secondary-color-1);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Demo Buttons */
        .demo-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e6ed;
        }

        .demo-section h6 {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
        }

        .btn-demo {
            padding: 10px 15px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            background: white;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-demo:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(86, 204, 153, 0.05);
        }

        /* Responsive Design */
        /* Tablet Portrait and Below (768px - 991px) */
        @media (max-width: 991px) and (min-width: 768px) {
            .brand-panel {
                min-height: 350px;
                padding: 50px 30px;
            }

            .brand-content h1 {
                font-size: 40px;
            }

            .brand-content p {
                font-size: 18px;
            }

            .form-panel {
                padding: 40px 30px;
            }

            .form-wrapper {
                max-width: 500px;
            }
        }

        /* Tablet and Below (max-width: 992px) */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }

            .brand-panel {
                min-height: 300px;
                padding: 40px 20px;
            }

            .brand-content h1 {
                font-size: 36px;
            }

            .brand-content p {
                font-size: 16px;
            }

            .form-panel {
                padding: 30px 20px;
            }
        }

        /* Small Mobile (max-width: 576px) */
        @media (max-width: 576px) {
            .brand-panel {
                min-height: 200px;
                padding: 30px 15px;
            }

            .brand-logo {
                width: 80px;
                height: 80px;
                margin-bottom: 20px;
            }

            .brand-logo img {
                max-width: 50px;
                max-height: 50px;
            }

            .brand-content h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }

            .brand-content p {
                font-size: 14px;
            }

            .form-panel {
                padding: 25px 15px;
            }

            .form-wrapper {
                max-width: 100%;
            }

            .form-header h2 {
                font-size: 24px;
            }

            .form-header p {
                font-size: 14px;
            }

            .login-container .form-control {
                padding: 12px 15px 12px 45px;
                font-size: 14px;
            }

            .input-icon {
                left: 15px;
                font-size: 16px;
            }

            .btn-login {
                padding: 14px;
                font-size: 15px;
            }

            .demo-buttons {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .btn-demo {
                padding: 12px;
                font-size: 13px;
            }

            .demo-section {
                margin-top: 20px;
                padding-top: 20px;
            }
        }

        /* Extra Small Mobile (max-width: 480px) */
        @media (max-width: 480px) {
            .brand-panel {
                min-height: 180px;
                padding: 25px 15px;
            }

            .brand-logo {
                width: 70px;
                height: 70px;
                margin-bottom: 15px;
            }

            .brand-logo img {
                max-width: 45px;
                max-height: 45px;
            }

            .brand-content h1 {
                font-size: 24px;
            }

            .brand-content p {
                font-size: 13px;
            }

            .form-panel {
                padding: 20px 12px;
            }

            .form-header {
                margin-bottom: 25px;
            }

            .form-header h2 {
                font-size: 22px;
            }

            .login-container .form-group {
                margin-bottom: 18px;
            }

            .forgot-password {
                font-size: 13px;
            }
        }

        /* Very Small Devices (max-width: 375px) */
        @media (max-width: 375px) {
            .brand-panel {
                min-height: 160px;
                padding: 20px 12px;
            }

            .brand-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 12px;
            }

            .brand-logo img {
                max-width: 40px;
                max-height: 40px;
            }

            .brand-content h1 {
                font-size: 22px;
            }

            .brand-content p {
                font-size: 12px;
            }

            .form-panel {
                padding: 18px 10px;
            }

            .form-header h2 {
                font-size: 20px;
            }

            .form-header p {
                font-size: 13px;
            }

            .login-container .form-control {
                padding: 11px 12px 11px 40px;
                font-size: 13px;
            }

            .input-icon {
                left: 12px;
                font-size: 14px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }

            .demo-section h6 {
                font-size: 11px;
            }

            .btn-demo {
                padding: 10px;
                font-size: 12px;
            }
        }

        /* Landscape Mobile Devices */
        @media (max-height: 600px) and (orientation: landscape) {
            .login-container {
                flex-direction: row;
            }

            .brand-panel {
                min-height: auto;
                padding: 30px 20px;
                flex: 0.8;
            }

            .brand-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 10px;
            }

            .brand-logo img {
                max-width: 40px;
                max-height: 40px;
            }

            .brand-content h1 {
                font-size: 24px;
                margin-bottom: 8px;
            }

            .brand-content p {
                font-size: 13px;
            }

            .form-panel {
                padding: 20px 15px;
                flex: 1.2;
            }

            .form-header {
                margin-bottom: 20px;
            }

            .form-header h2 {
                font-size: 22px;
            }

            .form-header p {
                font-size: 13px;
            }

            .login-container .form-group {
                margin-bottom: 15px;
            }

            .demo-section {
                margin-top: 15px;
                padding-top: 15px;
            }

            .demo-buttons {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
        }

        /* Modal Styles from assets/home_page/css/style.css */
        .formModal .modal-content {
            background: transparent;
            border: none;
            padding: 0;
        }

        .formModal .modal-title {
            color: #000000;
            font-size: 30px !important;
            font-weight: 700;
        }

        .formModal .rightSide {
            background-color: #fff;
            border-radius: 20px;
            padding: 20px;
            /* Added padding for better spacing */
        }

        .formModal .rightSide .headingWrapper span {
            color: #000000;
            font-size: 22px;
            font-weight: 700;
            position: relative;
        }

        .formModal .rightSide .headingWrapper span::before {
            content: '';
            position: absolute;
            bottom: -9px;
            background-color: var(--secondary-color);
            width: 6px;
            height: 6px;
            border-radius: 50px;
            left: 70px;
            right: auto;
            z-index: 1;
        }

        .formModal .rightSide .headingWrapper span::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0px;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }

        .formModal .rightSide .formWrapper {
            margin-top: 20px;
        }

        .formModal .rightSide .formWrapper .row {
            gap: 12px 0px;
        }

        .formModal .rightSide .formWrapper .inputWrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 6px;
            margin-bottom: 0;
            /* Override global margin if any */
        }

        .formModal .rightSide .formWrapper .inputWrapper label {
            color: #000000;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 5px;
        }

        .formModal .rightSide .formWrapper .inputWrapper input {
            border: 1px solid lightgray;
            border-radius: 6px;
            padding: 8px;
            width: 100%;
            background-color: white;
            /* Ensure white background */
            color: #000;
            /* Ensure black text */
        }

        .formModal .rightSide .formWrapper .inputWrapper input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Specific fix for select inputs in modal if any */
        .formModal select {
            border: 1px solid lightgray;
            border-radius: 6px;
            padding: 8px;
            width: 100%;
            background-color: white;
        }

        /* Specific fix for textarea in modal if any */
        .formModal textarea {
            border: 1px solid lightgray;
            border-radius: 6px;
            padding: 8px;
            width: 100%;
            background-color: white;
        }

        .formModal .adminFormWrapper {
            margin-top: 20px;
        }

        .formModal .modalfooter {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .formModal .modalfooter .commonBtn {
            padding: 8px 40px;
            border-radius: 8px;
            background-color: var(--secondary-color);
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .formModal .modalfooter .commonBtn:hover {
            background-color: var(--primary-color);
        }

        @media screen and (max-width: 575px) {
            .formModal .modal-content {
                padding-left: 0;
            }

            .formModal .rightSide {
                padding: 15px;
            }

            .formModal .modal-title {
                font-size: 22px !important;
            }
        }

        .modal-header .btn-close {
            margin: 0px !important;
        }

        /* File Upload Fix */
        .file-upload-default {
            visibility: hidden;
            position: absolute;
        }

        .school-registration .file-upload-default {
            opacity: 1 !important;
            position: relative !important;
            z-index: 1 !important;
            pointer-events: auto !important;
            visibility: visible !important;
        }

        .school-registration .file-upload-default:focus {
            outline: 2px solid var(--primary-color) !important;
            outline-offset: 2px !important;
        }

        .file-upload-info {
            background: white;
            border-top-left-radius: 6px !important;
            border-bottom-left-radius: 6px !important;
        }

        .btn-themes {
            background-color: var(--primary-color) !important;
            color: #fff !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        /* General Modal Fixes */
        .modal .modal-dialog {
            margin-top: unset !important;
        }

        a {
            color: var(--secondary-color);
            text-decoration: none;
        }

        a:hover {
            color: var(--primary-color);
        }

        .form-check .form-check-label input {
            opacity: 1 !important;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Left Panel - Login Form -->
        <div class="form-panel">
            <div class="form-wrapper">
                <div class="form-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to access your account</p>
                </div>

                <!-- Alerts -->
                @if (Session::has('success'))
                    <div class="alert alert-success">
                        {{ Session::get('success') }}
                    </div>
                @endif

                @if (Session::has('error'))
                    <div class="alert alert-danger">
                        {{ Session::get('error') }}
                    </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('login') }}" method="POST" id="loginForm">
                    @csrf

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="text" class="form-control" id="email" name="email"
                                placeholder="Enter your email" required autofocus>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                            <i class="fas fa-eye-slash password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    @if ($school ?? '')
                        <div class="form-group d-none">
                            <label for="school_code">{{ __('school_code') }}</label>
                            <div class="input-wrapper">
                                <i class="fas fa-building input-icon"></i>
                                <input type="text" class="form-control" id="school_code" name="code"
                                    value="{{ $school->code }}" autocomplete="school_code" autofocus
                                    placeholder="{{ __('school_code') }}">
                            </div>
                        </div>
                    @else
                        <!-- School Code -->
                        <div class="form-group">
                            <label for="school_code">
                                {{ __('school_code') }}
                                <i class="fas fa-info-circle" style="font-size: 0.85em; color: #6c757d; cursor: help;"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Enter your unique school code provided during registration. This code identifies your school in the system."></i>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-building input-icon"></i>
                                <input type="text" class="form-control" id="school_code" name="code"
                                    placeholder="{{ __('school_code') }}">
                            </div>
                        </div>
                    @endif

                    <!-- Forgot Password -->
                    <div class="forgot-password">
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>

                    <!-- Signup Link -->
                    <div class="signup-link">
                        <a class="text-blue" href="#" data-bs-toggle="modal" data-bs-dismiss="offcanvas"
                            data-bs-target="#staticBackdrop">{{ __('New user Sign up to manage your school activities seamlessly') }}</a>
                    </div>
                </form>

                <!-- Signup Modal form added last due to design issue -->

                <!-- Demo Credentials (only in demo mode) -->
                @if (env('DEMO_MODE'))

                    <div class="row mt-3">
                        <hr style="width: -webkit-fill-available;">
                        <div class="col-12 text-center mb-4 text-black-50">Demo Credentials</div>
                    </div>
                    @if (empty($school) ?? '')
                        <div class="col-12 text-center">
                            Super Admin Panels
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <button class="btn w-100 btn-success mt-2" id="superadmin_btn">Super Admin</button>
                            </div>

                            <div class="col-md-6">
                                <button class="btn w-100 btn-info mt-2" id="superadmin_staff_btn">Staff</button>
                            </div>
                        </div>
                    @endif

                    <div class="col-12 text-center mt-3">
                        <hr class="w-100">
                        School Admin Panels
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <button class="btn w-100 btn-info mt-2" id="schooladmin_btn">School Admin</button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn w-100 btn-danger mt-2" id="teacher_btn">Teacher</button>
                        </div>

                        <div class="col-md-4">
                            <button class="btn w-100 btn-primary mt-2" id="schooladmin_staff_btn">Staff</button>
                        </div>
                    </div>

                @endif
            </div>
        </div>

        <!-- Right Panel - Brand Showcase -->
        @php
            // Determine which login page image to use (priority: school > system > none)
            $loginPageLogo = null;
            if (isset($school) && isset($schoolSettings['login_page_logo']) && !empty($schoolSettings['login_page_logo'])) {
                $loginPageLogo = $schoolSettings['login_page_logo'];
            } elseif (isset($systemSettings['login_page_logo']) && !empty($systemSettings['login_page_logo'])) {
                $loginPageLogo = $systemSettings['login_page_logo'];
            }
        @endphp
        <div class="brand-panel" style="
            @if($loginPageLogo)
                background: url('{{ $loginPageLogo }}') center/cover no-repeat;
            @endif
        ">
            @if(!$loginPageLogo)
                <div class="brand-content">
                    <div class="brand-logo">
                        @if(isset($school) && isset($schoolSettings['horizontal_logo']) && !empty($schoolSettings['horizontal_logo']))
                            <img src="{{ $schoolSettings['horizontal_logo'] }}" alt="Logo">
                        @elseif(isset($systemSettings['horizontal_logo']) && !empty($systemSettings['horizontal_logo']))
                            <img src="{{ $systemSettings['horizontal_logo'] }}" alt="Logo">
                        @else
                            <img src="{{ url('assets/horizontal-logo.svg') }}" alt="Logo">
                        @endif
                    </div>
                    <h1>{{ (isset($school) ? ($schoolSettings['school_name'] ?? '') : ($systemSettings['system_name'] ?? '')) ?: 'eSchool Saas' }}
                    </h1>
                    <p>{{ (isset($school) ? ($schoolSettings['school_tagline'] ?? '') : ($systemSettings['tag_line'] ?? '')) ?: 'Manage your school efficiently' }}
                    </p>
                </div>
            @endif
        </div>
    </div>
    @include('registration_form');

    <script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/common.js') }}"></script>
    <script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/function.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <script>
        $("#loginForm").validate({
            rules: {
                username: "required",
                password: "required",
            },
            success: function (label, element) {
                $(element).parent().removeClass('has-danger')
                $(element).removeClass('form-control-danger')
            },
            errorPlacement: function (label, element) {
                if (label.text()) {
                    if ($(element).attr("name") == "password") {
                        label.insertAfter(element.parent()).addClass('text-danger mt-2');
                    } else {
                        label.addClass('mt-2 text-danger');
                        label.insertAfter(element);
                    }
                }
            },
            highlight: function (element, errorClass) {
                $(element).parent().addClass('has-danger')
                $(element).addClass('form-control-danger')
            }
        });

        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Initialize Bootstrap tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Form Validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const code = document.getElementById('school_code').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });


        @if (env('DEMO_MODE'))
            // Super admin panel
            $('#superadmin_btn').on('click', function (e) {
                $('#email').val('superadmin@gmail.com');
                $('#password').val('superadmin');
                $('#loginForm').submit();
            })

            $('#superadmin_staff_btn').on('click', function (e) {
                $('#email').val('mahesh@gmail.com');
                $('#password').val('staff@123');
                $('#loginForm').submit();
            })

            // School Panel
            $('#schooladmin_btn').on('click', function (e) {
                $('#email').val('school1@gmail.com');
                $('#password').val('school@123');
                $('#school_code').val('SCH202412');
                $('#loginForm').submit();
            })
            $('#teacher_btn').on('click', function (e) {
                $('#email').val('teacher@gmail.com');
                $('#password').val('0111111111');
                $('#school_code').val('SCH202412');
                $('#loginForm').submit();
            })

            $('#schooladmin_staff_btn').on('click', function (e) {
                $('#email').val('smitc@gmail.com');
                $('#password').val('965555885');
                $('#school_code').val('SCH202412');
                $('#loginForm').submit();
            })
        @endif

        const please_wait = "{{__('Please wait')}}"
        const processing_your_request = "{{__('Processing your request')}}"
    </script>
</body>

@if (Session::has('error'))
    <script type='text/javascript'>
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
        <script type='text/javascript'>
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