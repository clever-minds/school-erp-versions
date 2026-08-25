@extends('layouts.master')

@section('title')
    {{ __('system_update') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/system-update.css') }}">
@endsection

@section('content')
    <div class="content-wrapper">

        {{-- ═══ Page Header ═══ --}}
        <div class="su-page-header">
            <h3 class="su-page-title">
                {{ __('System Update') }}
                <span class="su-version-badge text-uppercase">{{ __('current') }}: V{{ $system_version->data ?? '0.0.0' }}</span>
            </h3>
            <button type="button" class="su-maintenance-btn {{ ($maintenanceMode ?? false) ? 'active' : '' }}" id="su-maintenance-btn">
                @if($maintenanceMode ?? false)
                    <i class="fa fa-lock"></i> {{ __('maintenance_mode_active') }}
                @else
                    <i class="fa fa-shield"></i> {{ __('enable_maintenance_mode') }}
                @endif
            </button>
        </div>

        {{-- ═══ Alert Banner ═══ --}}
        <div class="su-alert-banner {{ ($maintenanceMode ?? false) ? 'hidden' : '' }}" id="su-alert-banner">
            <div class="alert-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="alert-text">
                <strong>{{ __('crucial_action_required') }}</strong> —
                {{ __('please_enable') }} <strong>{{ __('maintenance_mode') }}</strong> {{ __('before_updating_the_system') }}<br>{{ __('this_ensures_users_cannot_access_the_application_during_the_update_helping_reduce_server_load_and_preventing_potential_issues_like_request_timeouts') }}
            </div>
        </div>

        <div class="row">
            {{-- ═══ Left Column (Main) ═══ --}}
            <div class="col-lg-8">

                {{-- Update Progression Card --}}
                <div class="su-card">
                    <div class="su-card-header">
                        <div>
                            <h5>{{ __('update_progression') }}</h5>
                            <p>{{ __('deployment_stages_and_database_orchestration') }}</p>
                        </div>
                        <div class="su-live-badge d-none text-uppercase" id="su-live-badge">
                            <span class="pulse-dot"></span> {{ __('live_sync_active') }}
                        </div>
                    </div>

                    <div class="su-card-body">
                        {{-- Stepper --}}
                        <div class="su-stepper">
                            <div class="su-stepper-line">
                                <div class="su-stepper-line-fill" id="su-stepper-line-fill"></div>
                            </div>
                            <div class="su-step" data-step="extracting">
                                <div class="su-step-icon"><i class="fa fa-file"></i></div>
                                <div class="su-step-label">{{ __('file_extraction') }}</div>
                            </div>
                            <div class="su-step" data-step="main_migration">
                                <div class="su-step-icon"><i class="fa fa-database"></i></div>
                                <div class="su-step-label">{{ __('main_database') }}</div>
                            </div>
                            <div class="su-step" data-step="tenant_migration">
                                <div class="su-step-icon"><i class="fa fa-building"></i></div>
                                <div class="su-step-label">{{ __('tenant_migrations') }}</div>
                            </div>
                            <div class="su-step" data-step="cache_optimization">
                                <div class="su-step-icon"><i class="fa fa-cogs"></i></div>
                                <div class="su-step-label">{{ __('cache_optimization') }}</div>
                            </div>
                        </div>

                        {{-- Input Form --}}
                        <form id="su-update-form" enctype="multipart/form-data">
                            @csrf
                            <div class="su-input-row" id="su-form-area">
                                <div class="su-input-group">
                                    <label>{{ __('purchase_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="purchase_code" id="su-purchase-code" placeholder="XXXX-XXXX-XXXX-XXXX">
                                </div>
                                <div class="su-input-group">
                                    <label>{{ __('file') }} <span class="text-danger">*</span></label>
                                    <div class="su-file-input-wrapper">
                                        <i class="fa fa-cloud-upload file-icon"></i>
                                        <span class="file-text" id="su-file-label">{{ __('upload_zip_file') }}</span>
                                        <input type="file" name="file" id="su-file-input" accept=".zip"
                                            onchange="document.getElementById('su-file-label').textContent = this.files[0] ? this.files[0].name : 'Upload .zip package'">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="su-deploy-btn btn btn-theme" id="su-deploy-btn">
                                <i class="fa fa-rocket"></i> {{ __('initialize_deployment') }} <i class="fa fa-arrow-right"></i>
                            </button>
                        </form>

                        {{-- Global Progress --}}
                        <div class="su-global-progress" id="su-global-progress" style="display: {{ ($latestRun && !in_array($latestRun['run']['status'] ?? '', ['pending'])) ? 'block' : 'none' }};">
                            <div class="su-global-progress-header">
                                <div>
                                    <div class="su-global-progress-label text-uppercase">{{ __('global_status') }}</div>
                                    <div class="su-global-progress-value" id="su-progress-value">
                                        @php
                                            $progressVal = 0;
                                            if ($latestRun) {
                                                $svc = app(\App\Services\SystemUpdateService::class);
                                                $progressVal = $svc->calculateGlobalProgress(
                                                    $latestRun['run']['status'] ?? 'pending',
                                                    $latestRun['run']['completed_schools'] ?? 0,
                                                    $latestRun['run']['total_schools'] ?? 0
                                                );
                                            }
                                        @endphp
                                        {{ $progressVal }}%
                                    </div>
                                </div>
                                <div class="su-global-progress-stage" id="su-progress-stage">
                                    @if($latestRun)
                                        {{ __('stage') }}: {{ ucwords(str_replace('_', ' ', $latestRun['run']['status'] ?? '')) }}
                                    @endif
                                </div>
                            </div>
                            <div class="su-progress-bar-wrapper">
                                <div class="su-progress-bar-fill {{ ($latestRun && ($latestRun['run']['status'] ?? '') === 'completed') ? 'completed' : '' }}"
                                     id="su-progress-bar-fill" style="width: {{ $progressVal }}%"></div>
                            </div>
                        </div>

                        {{-- Log Console --}}
                        <div class="su-log-console" id="su-log-console"
                             style="display: {{ ($latestRun && !empty($latestRun['run']['log'])) ? 'block' : 'none' }};">
                            <div class="su-log-header">
                                <span class="dot dot-red"></span>
                                <span class="dot dot-yellow"></span>
                                <span class="dot dot-green"></span>
                                <span class="log-title"> {{ __('deployment_output_v') }} {{ $system_version->data ?? '0.0.0' }}.log</span>
                            </div>
                            <div class="su-log-body" id="su-log-body"></div>
                        </div>
                    </div>
                </div>

                {{-- Tenant Synchronization --}}
                <div class="su-tenant-section">
                    <div class="su-card">
                        <div class="su-card-body">
                            <div class="su-tenant-header">
                                <div>
                                    <h5>{{ __('tenant_synchronization') }}</h5>
                                    <p id="su-school-counter">
                                        {{ __('managing_migration_status_for') }}
                                        {{ $latestRun ? ($latestRun['run']['total_schools'] ?? 0) : 0 }}
                                        {{ __('total_schools') }}
                                    </p>
                                </div>
                                <div class="su-tenant-filters">
                                    <select class="su-status-filter" id="su-status-filter">
                                        <option value="all">{{ __('All Statuses') }}</option>
                                        <option value="queued">{{ __('Queued') }}</option>
                                        <option value="migrating">{{ __('Migrating') }}</option>
                                        <option value="seeding">{{ __('Seeding') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                        <option value="failed">{{ __('Failed') }}</option>
                                    </select>
                                    <input type="text" class="su-search-input" id="su-school-search" placeholder="{{ __('search_school_name') }}">
                                </div>
                            </div>

                            <table class="su-school-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>{{ __('school_name') }}</th>
                                        <th>{{ __('error_message') }}</th>
                                        <th>{{ __('progress') }}</th>
                                        <th>{{ __('status') }}</th>
                                        <th style="width:80px"></th>
                                    </tr>
                                </thead>
                                <tbody id="su-school-tbody">
                                    <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">{{ __('Loading...') }}</td></tr>
                                </tbody>
                            </table>

                            {{-- Pagination Controls --}}
                            <div class="su-pagination" id="su-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══ Right Column (Sidebar) ═══ --}}
            <div class="col-lg-4">

                {{-- Pre-Update Tasks --}}
                <div class="su-sidebar-card">
                    <div class="su-sidebar-card-header">
                        <div class="header-icon pre"><i class="fa fa-shield"></i></div>
                        <h6>{{ __('pre_update_tasks') }}</h6>
                    </div>
                    <div class="su-sidebar-card-body">
                        @foreach($preUpdateChecks as $key => $check)
                            <div class="su-check-item">
                                @if ($check['link'] ?? '')
                                    <a href="{{ $check['link'] }}" class="text-info w-80" target="_blank">
                                        <span class="check-label text-info">{{ $check['label'] }}</span>
                                    </a>
                                @else
                                    <span class="check-label">{{ $check['label'] }}</span>
                                @endif
                                <span class="check-icon {{ $check['passed'] ? 'passed' : 'pending' }}"
                                      id="su-check-{{ $key === 'maintenance_mode' ? 'maintenance' : $key }}">
                                    @if($check['passed'])
                                        <i class="fa fa-check"></i>
                                    @else
                                        <i class="fa fa-circle-o"></i>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Post-Update Tasks --}}
                <div class="su-sidebar-card">
                    <div class="su-sidebar-card-header">
                        <div class="header-icon post"><i class="fa fa-wrench"></i></div>
                        <h6>{{ __('post_update_tasks') }}</h6>
                    </div>
                    <div class="su-sidebar-card-body" style="padding-top:8px;">
                        <button class="su-task-btn" data-task="restart_supervisor" data-label="Restart Supervisor Service">
                            <i class="fa fa-refresh task-icon"></i> {{ __('restart_supervisor_service') }}
                        </button>
                        <button class="su-task-btn" data-task="disable_maintenance" data-label="Disable Maintenance Mode">
                            <i class="fa fa-unlock task-icon"></i> {{ __('disable_maintenance_mode') }}
                        </button>
                        <button class="su-task-btn" data-task="clear_cache" data-label="Clear Route/Config Cache">
                            <i class="fa fa-eraser task-icon"></i> {{ __('clear_cache') }}
                        </button>
                        <button class="su-task-btn" data-task="hard_refresh" data-label="Hard Refresh">
                            <i class="fa fa-refresh task-icon"></i> {{ __('hard_refresh') }}
                        </button>
                        {{-- <button class="su-task-btn" data-task="verify_queue" data-label="Verify Email Queue Health">
                            <i class="fa fa-heartbeat task-icon"></i> {{ __('verify_email_queue_health') }}
                        </button> --}}
                    </div>
                </div>

                {{-- Need Support? --}}
                <div class="su-support-card">
                    <i class="fa fa-life-ring support-icon"></i>
                    <h6>{{ __('need_support') }}</h6>
                    <p>{{ __('if_you_encounter_any_issues_during_the_system_update_process_please_refer_to_the_documentation_changelog_important_notes_and_FAQs_linked_below_for_detailed_assistance') }}</p>
                    <a href="https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/faqs/" target="_blank" class="support-btn">
                        <i class="fa fa-book"></i> {{ __('check_documentation') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        window.systemUpdateConfig = {
            csrf: "{{ csrf_token() }}",
            routes: {
                update: "{{ route('system-update.update') }}",
                status: "{{ url('system-update/status') }}/__RUN_ID__",
                retrySchool: "{{ route('system-update.retry-school') }}",
                schoolTracks: "{{ route('system-update.school-tracks') }}",
                // postTask: "{{ route('system-update.post-task') }}",
                toggleMaintenance: "{{ route('system-update.toggle-maintenance') }}"
            },
            reverb: @json($reverb),
            currentRunId: @json($latestRun['run']['run_id'] ?? null),
            runStatus: @json($latestRun['run']['status'] ?? null),
            runProgress: {{ $progressVal ?? 0 }},
            runStage: @json($latestRun ? ucwords(str_replace('_', ' ', $latestRun['run']['status'] ?? '')) : ''),
            logEntries: @json($latestRun['run']['log'] ?? [])
        };
    </script>
    <script src="{{ asset('assets/js/system-update.js') }}"></script>
@endsection
