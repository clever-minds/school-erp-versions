@extends('layouts.full-screen')

@section('title')
    {{ __('Server Configuration Check') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/server-config.css') }}">
@endsection

@section('content')
    <div class="server-config-wrapper">

        {{-- ── Left Main Panel ─────────────────────────────────────── --}}
        <div class="server-config-main">

            {{-- Header --}}
            <div class="server-config-header">
                <div class="server-config-header-icon">
                    <i class="mdi mdi-server-security"></i>
                </div>
                <div class="server-config-header-text">
                    <h4>{{ __('Server Configuration Check') }}</h4>
                    <p>{{ __('Required system settings for SaaS functionality. These settings enable multi-tenancy, background jobs, and real-time features.') }}</p>
                </div>
            </div>

            {{-- ── 1. Database Root Privileges ─────────────────────── --}}
            <div class="requirement-card {{ $dbCheck == 1 ? 'check-passed' : ($dbCheck == 0 ? 'check-failed' : '') }}" id="card-db">
                <div class="requirement-card-header">
                    <div class="requirement-card-header-icon">
                        <i class="mdi mdi-database"></i>
                    </div>
                    <div class="requirement-card-header-info">
                        <h6>
                            {{ __('Database Root Privileges') }}
                            <span class="badge-required">REQUIRED</span>
                        </h6>
                        <p>{{ __('Required to automatically create tenant databases when new schools register.') }}</p>
                    </div>
                    <div class="requirement-card-status">
                        <div class="check-circle {{ $dbCheck == 1 ? 'passed' : ($dbCheck == 0 ? 'failed' : '') }}">
                            @if($dbCheck == 1)
                                <i class="mdi mdi-check"></i>
                            @elseif($dbCheck == 0)
                                <i class="mdi mdi-close"></i>
                            @endif
                        </div>
                        <i class="mdi mdi-chevron-down chevron-icon"></i>
                    </div>
                </div>

                <div class="requirement-card-body">
                    <div class="req-info-alert">
                        <i class="mdi mdi-information-outline"></i>
                        <span>
                            {{ __('We need ROOT access (or a user with') }}
                            <code>CREATE DATABASE</code>
                            {{ __('privileges) to automate school onboarding.') }}
                        </span>
                    </div>

                    <div class="db-form-grid">
                        <div class="form-group">
                            <label for="db_host">{{ __('DB Host') }}</label>
                            <input type="text" id="db_host" class="form-control" value="{{ $data['db_host'] ?? '127.0.0.1' }}" placeholder="127.0.0.1">
                        </div>
                        <div class="form-group">
                            <label for="db_port">{{ __('DB Port') }}</label>
                            <input type="number" id="db_port" class="form-control" value="{{ $data['db_port'] ?? '3306' }}" placeholder="3306">
                        </div>
                        <div class="form-group">
                            <label for="db_username">{{ __('Root Username') }}</label>
                            <input type="text" id="db_username" class="form-control" value="{{ $data['db_username'] ?? 'root' }}" placeholder="root">
                        </div>
                        <div class="form-group">
                            <label for="db_password">{{ __('Root Password') }}</label>
                            <input type="password" id="db_password" class="form-control" value="{{ $data['db_password'] ?? '' }}" placeholder="{{ __('Enter DB Password') }}">
                        </div>
                    </div>

                    <button class="btn-test-connection" id="btn-test-db" type="button">
                        <i class="mdi mdi-play"></i>
                        {{ __('Test Connection & Privileges') }}
                    </button>
                </div>
            </div>

            {{-- ── 2. Queue Worker (Supervisor) ─────────────────────── --}}
            <div class="requirement-card {{ $queueCheck == 1 ? 'check-passed' : ($queueCheck == 0 ? 'check-failed' : '') }}" id="card-queue">
                <div class="requirement-card-header">
                    <div class="requirement-card-header-icon">
                        <i class="mdi mdi-layers"></i>
                    </div>
                    <div class="requirement-card-header-info">
                        <h6>
                            {{ __('Queue Worker (Supervisor)') }}
                            <span class="badge-required">REQUIRED</span>
                        </h6>
                        <p>{{ __('Handles background tasks like email sending, school creation, etc. to keep the UI responsive.') }}</p>
                    </div>
                    <div class="requirement-card-status">
                        <div class="check-circle {{ $queueCheck == 1 ? 'passed' : ($queueCheck == 0 ? 'failed' : '') }}">
                            @if($queueCheck == 1)
                                <i class="mdi mdi-check"></i>
                            @elseif($queueCheck == 0)
                                <i class="mdi mdi-close"></i>
                            @endif
                        </div>
                        <i class="mdi mdi-chevron-down chevron-icon"></i>
                    </div>
                </div>

                <div class="requirement-card-body">
                    <div class="queue-info-box">
                        <p>Supervisor should be configured to continuously run the queue worker. <a href="https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/admin-panel-installation/queue-setup/" target="_blank">On production, add a Supervisor program:</a></p>
                        <code>php artisan queue:work --sleep=3 --tries=3 --max-time=3600</code>
                    </div>

                    <button class="btn-test-queue" id="btn-test-queue" type="button">
                        <i class="mdi mdi-refresh"></i>
                        {{ __('Test Queue Worker') }}
                    </button>
                </div>
            </div>

            {{-- ── 3. Laravel Reverb (Supervisor) ─────────────────────── --}}
            <div class="requirement-card {{ $reverbCheck == 1 ? 'check-passed' : ($reverbCheck == 0 ? 'check-failed' : '') }}" id="card-reverb">
                <div class="requirement-card-header">
                    <div class="requirement-card-header-icon">
                        <i class="mdi mdi-chat-processing"></i>
                    </div>
                    <div class="requirement-card-header-info">
                        <h6>
                            {{ __('Laravel Reverb (Supervisor)') }}
                            <span class="badge-required">REQUIRED</span>
                        </h6>
                        <p>{{ __('Provides real-time WebSocket functionality for live chat.') }}</p>
                    </div>
                    <div class="requirement-card-status">
                        <div class="check-circle {{ $reverbCheck == 1 ? 'passed' : ($reverbCheck == 0 ? 'failed' : '') }}">
                            @if($reverbCheck == 1)
                                <i class="mdi mdi-check"></i>
                            @elseif($reverbCheck == 0)
                                <i class="mdi mdi-close"></i>
                            @endif
                        </div>
                        <i class="mdi mdi-chevron-down chevron-icon"></i>
                    </div>
                </div>

                <div class="requirement-card-body">
                    <div class="queue-info-box">
                        <p>Supervisor should be configured to continuously run the Reverb WebSocket server. <a href="https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/admin-panel-installation/reverb-setup/" target="_blank">On production, add a Supervisor program:</a></p>
                        <code>php artisan reverb:start</code>
                    </div>

                    <button class="btn-test-queue" id="btn-test-reverb" type="button">
                        <i class="mdi mdi-refresh"></i>
                        {{ __('Test Reverb Worker') }}
                    </button>
                </div>
            </div>

        </div>{{-- /.server-config-main --}}

        {{-- ── Right Sidebar ────────────────────────────────────────── --}}
        <div class="server-config-sidebar">

            {{-- System Health --}}
            <div class="system-health-card">
                <h6>{{ __('System Health') }}</h6>

                <div class="health-gauge-wrap">
                    <div class="health-dot {{ $checksPassedCount === 3 ? 'all-passed' : '' }}" id="health-dot"></div>
                    <div class="health-gauge-circle {{ $checksPassedCount > 0 && $checksPassedCount < 3 ? 'some-passed' : ($checksPassedCount === 3 ? 'all-passed' : '') }}" id="health-gauge-circle">
                        <span class="health-gauge-score" id="health-score">{{ $checksPassedCount }}/3</span>
                        <span class="health-gauge-label">{{ __('Checks Passed') }}</span>
                    </div>
                </div>

                <div class="setup-status-alert {{ $checksPassedCount === 3 ? 'complete' : 'incomplete' }}" id="setup-status-alert">
                    @if($checksPassedCount === 3)
                        <i class="mdi mdi-check-circle"></i>
                        <div>
                            <strong>{{ __('Setup Complete') }}</strong>
                            {{ __('All checks passed. You can now go to the dashboard.') }}
                        </div>
                    @else
                        <i class="mdi mdi-alert"></i>
                        <div>
                            <strong>{{ __('Setup Incomplete') }}</strong>
                            {{ __('Please resolve critical issues to access the full dashboard.') }}
                        </div>
                    @endif
                </div>

                <button class="btn-go-dashboard" id="btn-go-dashboard" {{ $checksPassedCount < 3 ? 'disabled' : '' }}>
                    {{ __('Go to Dashboard') }}
                    <i class="mdi mdi-arrow-right"></i>
                </button>
            </div>

            {{-- System Output terminal --}}
            <div class="system-output-card">
                <div class="system-output-header">
                    <span>{{ __('System Output') }}</span>
                    <button class="btn-clear-output" id="btn-clear-output" type="button">{{ __('Clear') }}</button>
                </div>
                <div class="system-output-body" id="system-output-body">
                    <span class="output-waiting">{{ __('Waiting for verification tests...') }}</span>
                </div>
            </div>

        </div>{{-- /.server-config-sidebar --}}

    </div>{{-- /.server-config-wrapper --}}
@endsection

@section('js')
    <script>
        window.serverConfigRoutes = {
            testDatabase: "{{ route('server-config.test-database') }}",
            testQueue:    "{{ route('server-config.test-queue') }}",
            testReverb:   "{{ route('server-config.test-reverb') }}",
            markComplete: "{{ route('server-config.complete') }}",
        };
        window.serverConfigInitState = {
            dbPassed:     {{ is_null($dbCheck) ? 'null' : ($dbCheck == 1 ? 'true' : 'false') }},
            queuePassed:  {{ is_null($queueCheck) ? 'null' : ($queueCheck == 1 ? 'true' : 'false') }},
            reverbPassed: {{ is_null($reverbCheck) ? 'null' : ($reverbCheck == 1 ? 'true' : 'false') }},
        };
    </script>
    <script src="{{ asset('assets/js/custom/server-config.js') }}"></script>
@endsection
