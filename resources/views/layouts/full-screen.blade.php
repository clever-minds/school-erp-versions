<!DOCTYPE html>
@php
    $lang = Session::get('language');
@endphp
@if($lang)
    @if ($lang->is_rtl)
        <html lang="{{ $lang->code ?? 'en' }}" dir="rtl">
    @else
        <html lang="{{ $lang->code ?? 'en' }}" dir="ltr">
    @endif
@else
    <html lang="en" dir="ltr">
@endif

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="favicon"
        content="{{ $schoolSettings['favicon'] ?? $systemSettings['favicon'] ?? url('assets/vertical-logo.svg') }}">
    <title>
        @yield('title') ||
        {{ $systemSettings['system_name'] ?? 'eSchool - Saas' }}
    </title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.include')
    @yield('css')
    <style>
        .full-screen-wrapper {
            background: var(--primary-background-color, #f2f5f7);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .wizard-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .wizard-card {
            width: 100%;
            max-width: 80%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 15px;
        }

        .wizard-header {
            padding: 2rem 2rem 1rem;
            text-align: center;
        }

        .brand-logo {
            margin-bottom: 2rem;
        }

        .brand-logo img {
            max-height: 60px;
        }

        @media (max-width: 992px) {
            .wizard-card {
                max-width: 95%;
            }

            .wizard-container {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="full-screen-wrapper">
        <div class="wizard-container">
            <div class="card wizard-card">
                <div class="wizard-header">
                    <div class="brand-logo">
                        @if ($systemSettings['horizontal_logo'] ?? '')
                            <img src="{{ $systemSettings['horizontal_logo'] }}" alt="logo">
                        @else
                            <img src="{{ url('assets/horizontal-logo.svg') }}" alt="logo">
                        @endif
                    </div>
                </div>
                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.footer_js')
    @yield('js')
    @yield('script')
</body>

</html>