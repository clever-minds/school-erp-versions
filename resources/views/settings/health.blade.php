@extends('layouts.master')

@section('title')
    {{ __('system_health') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/server-config.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/health.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('system_health') }}
            </h3>
        </div>

        {{-- ── Server Configuration Check Wrapper ────────────────────────── --}}
        <div class="server-config-wrapper" style="padding: 0; background: transparent;">

            {{-- ── Left Main Panel ────────────────────────────── --}}
            <div class="server-config-main">

                {{-- Page Header Card --}}
                <div class="server-config-header">
                    <div class="server-config-header-icon">
                        <i class="mdi mdi-server-security"></i>
                    </div>
                    <div class="server-config-header-text">
                        <h4>{{ __('server_configuration_check') }}</h4>
                        <p>{{ __('validate_critical_saas_services_and_environment') }}</p>
                    </div>
                    <div class="ml-auto">
                        <button class="btn btn-theme" id="btn-rerun-check" type="button">
                            <i class="mdi mdi-refresh"></i>
                            {{ __('re_run_check') }}
                        </button>
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
                                {{ __('database_root_privileges') }}
                                <span class="badge-required">{{ __('required') }}</span>
                            </h6>
                            <p>{{ __('required_to_automatically_create_tenant_databases_when_new_schools_register') }}</p>
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
                                {{ __('we_need_ROOT_access_or_a_user_with') }}
                                <code class="text-uppercase">{{ __('create_database') }}</code>
                                {{ __('privileges_to_automate_school_onboarding') }}
                            </span>
                        </div>

                        <div class="db-form-grid">
                            <div class="form-group">
                                <label for="health_db_host">{{ __('db_host') }}</label>
                                <input type="text" id="health_db_host" class="form-control" value="{{ $data['db_host'] }}" placeholder="127.0.0.1">
                            </div>
                            <div class="form-group">
                                <label for="health_db_port">{{ __('db_port') }}</label>
                                <input type="number" id="health_db_port" class="form-control" value="{{ $data['db_port'] }}" placeholder="3306">
                            </div>
                            <div class="form-group">
                                <label for="health_db_username">{{ __('db_username') }}</label>
                                <input type="text" id="health_db_username" class="form-control" value="{{ $data['db_username'] }}" placeholder="root">
                            </div>
                            <div class="form-group">
                                <label for="health_db_password">{{ __('password') }}</label>
                                <input type="password" id="health_db_password" class="form-control" value="{{ $data['db_password'] }}" placeholder="{{ __('Enter DB Password') }}">
                            </div>
                        </div>

                        <button class="btn-test-connection" id="health-btn-test-db" type="button">
                            <i class="mdi mdi-play"></i>
                            {{ __('text_connection_update_credential') }}
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
                                {{ __('queue_worker_supervisor') }}
                                <span class="badge-required">{{ __('required') }}</span>
                            </h6>
                            <p>{{ __('handles_background_tasks_like_email_sending_and_school_creation') }}</p>
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
                            <p>
                                {!! __('supervisor_should_be_configured_to_continuously_run_the_queue_worker', [
                                    'link' => '<a href="https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/admin-panel-installation/queue-setup/" target="_blank">' . __('add_supervisor_program') . '</a>'
                                ]) !!}
                            </p>
                            <code>php artisan queue:work --sleep=3 --tries=3 --max-time=3600</code>
                        </div>

                        <button class="btn-test-queue" id="health-btn-test-queue" type="button">
                            <i class="mdi mdi-refresh"></i>
                            {{ __('re-test_queue_worker') }}
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
                                {{ __('laravel_reverb_supervisor') }}
                                <span class="badge-required">{{ __('required') }}</span>
                            </h6>
                            <p>{{ __('provides_real_time_webSocket_functionality_for_live_chat') }}</p>
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
                            <p>
                                {!! __('supervisor_should_be_configured_to_continuously_run_the_Reverb_WebSocket_server', [
                                    'link' => '<a href="https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/admin-panel-installation/reverb-setup/" target="_blank">' . __('add_supervisor_program') . '</a>'
                                ]) !!}
                            </p>
                            <code>php artisan reverb:start</code>
                        </div>

                        <button class="btn-test-queue" id="health-btn-test-reverb" type="button">
                            <i class="mdi mdi-refresh"></i>
                            {{ __('test_reverb_worker') }}
                        </button>
                    </div>
                </div>

            </div>{{-- /.server-config-main --}}

            {{-- ── Right Sidebar ────────────────────────────────────────── --}}
            <div class="server-config-sidebar">

                {{-- System Health Score Card --}}
                <div class="system-health-card">
                    <h6>{{ __('system_health_score') }}</h6>

                    <div class="health-gauge-wrap">
                        <div class="health-dot {{ $checksPassedCount === 3 ? 'all-passed' : '' }}" id="health-dot"></div>
                        <div class="health-gauge-circle {{ $checksPassedCount > 0 && $checksPassedCount < 3 ? 'some-passed' : ($checksPassedCount === 3 ? 'all-passed' : '') }}" id="health-gauge-circle">
                            <span class="health-gauge-score" id="health-score">{{ $checksPassedCount }}/3</span>
                        </div>
                    </div>

                    <div class="setup-status-alert {{ $checksPassedCount === 3 ? 'complete' : 'incomplete' }}" id="health-setup-status-alert">
                        @if($checksPassedCount === 3)
                            <i class="mdi mdi-check-circle"></i>
                            <div>
                                <strong>{{ __('setup_complete') }}</strong>
                                {{ __('all_checks_passed') }}
                            </div>
                        @else
                            <i class="mdi mdi-alert"></i>
                            <div>
                                <strong>{{ __('setup_incomplete') }}</strong>
                                {{ __('please_resolve_the_critical_issues_above') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- System Output terminal --}}
                <div class="system-output-card">
                    <div class="system-output-header">
                        <span>{{ __('system_output') }}</span>
                        <button class="btn-clear-output" id="health-btn-clear-output" type="button">{{ __('clear') }}</button>
                    </div>
                    <div class="system-output-body" id="health-system-output-body">
                        <span class="output-waiting">{{ __('waiting_for_verification_tests') }}</span>
                    </div>
                </div>

            </div>{{-- /.server-config-sidebar --}}

        </div>{{-- /.server-config-wrapper --}}

    </div>
@endsection

@section('js')
    <script>
        window.healthCheckRoutes = {
            testDatabase: "{{ route('system-settings.health.test-database') }}",
            testQueue:    "{{ route('system-settings.health.test-queue') }}",
            testReverb:   "{{ route('system-settings.health.test-reverb') }}",
        };
        window.healthInitState = {
            dbPassed:     {{ is_null($dbCheck) ? 'null' : ($dbCheck == 1 ? 'true' : 'false') }},
            queuePassed:  {{ is_null($queueCheck) ? 'null' : ($queueCheck == 1 ? 'true' : 'false') }},
            reverbPassed: {{ is_null($reverbCheck) ? 'null' : ($reverbCheck == 1 ? 'true' : 'false') }},
        };
    </script>
    <script src="{{ asset('assets/js/custom/health.js') }}"></script>
@endsection
