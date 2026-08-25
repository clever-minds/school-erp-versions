@extends('layouts.full-screen')

@section('title')
    {{ __('Academy Setup Wizard') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/academy-setup-wizard.css') }}">
@endsection

@section('content')
    <div class="academy-setup-wrapper">
        <div class="academy-wizard-container">
            <div class="academy-setup-header d-none">
                <h2>
                    <span class="header-icon"><i class="mdi mdi-school"></i></span>
                    {{ __('Setup Wizard') }}
                </h2>
                <a href="{{ route('skip-academy-setup-wizard') }}" class="btn btn-link px-0 skip-installation" id="skip-installation-btn">
                    Skip for now, I'll do it later
                </a>
            </div>

            <div class="academy-setup-progress-wrapper d-none">
                <div class="academy-setup-progress-steps" id="academy-steps">
                    <!-- Progress line injected in JS or CSS -->
                    <div class="progress-line"></div>
                </div>
            </div>

            <div class="academy-setup-body">
                <section class="academy-step active" data-step="welcome">
                    <div class="welcome-illustration">
                        <div class="welcome-icon-large" style="background: var(--theme-color); color: #ffffff; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3);"><i class="mdi mdi-school"></i></div>
                    </div>
                    <div class="step-header text-center mb-5">
                        <h2 class="font-weight-bold" style="color: #111827; letter-spacing: -0.02em;">Welcome to {{ env('APP_NAME') }}</h2>
                    </div>
                    
                    <div class="row align-items-center justify-content-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="feature-card p-4 d-flex align-items-start" style="gap: 16px; flex-direction: row; border: 1px solid #f3f4f6;">
                                        <i class="mdi mdi-flash text-warning" style="font-size: 1.5rem; margin-top: -2px;"></i>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ __('Stream Mapping') }}</div>
                                            <div class="text-muted" style="font-size: 0.85rem; font-weight: 400; margin-top: 4px;">{{ __('Assign subjects per stream & semester.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="feature-card p-4 d-flex align-items-start" style="gap: 16px; flex-direction: row; border: 1px solid #f3f4f6;">
                                        <i class="mdi mdi-shield-check-outline text-success" style="font-size: 1.5rem; margin-top: -2px;"></i>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ __('Elective Groups') }}</div>
                                            <div class="text-muted" style="font-size: 0.85rem; font-weight: 400; margin-top: 4px;">{{ __('Flexible elective choices per term.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="feature-card p-4 d-flex align-items-start" style="gap: 16px; flex-direction: row; border: 1px solid #f3f4f6;">
                                        <i class="mdi mdi-view-grid text-primary" style="font-size: 1.5rem; margin-top: -2px;"></i>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ __('Unified View') }}</div>
                                            <div class="text-muted" style="font-size: 0.85rem; font-weight: 400; margin-top: 4px;">{{ __('One place for your academic structure.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="feature-card p-4 d-flex align-items-start" style="gap: 16px; flex-direction: row; border: 1px solid #f3f4f6;">
                                        <i class="mdi mdi-cellphone text-info" style="font-size: 1.5rem; margin-top: -2px;"></i>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ __('Global Ready') }}</div>
                                            <div class="text-muted" style="font-size: 0.85rem; font-weight: 400; margin-top: 4px;">{{ __('Configured for regional standards.') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4 mb-2">
                        <button type="button" class="btn btn-primary" id="begin-setup-btn" style="padding: 0.75rem 2rem; font-size: 1.05rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2);">
                            {{ __('Begin Setup') }} <i class="mdi mdi-chevron-right ml-1"></i>
                        </button>
                    </div>
                </section>

                <section class="academy-step" data-step="session">
                    <div class="step-header">
                        <h4>{{ __('Academy Year') }}</h4>
                        <p>{{ __('Define the upcoming operational session duration and board mapping.') }}</p>
                    </div>
                    <div class="content-block">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{ __('Session Name') }}</label>
                                <input type="text" id="session-name" class="form-control" placeholder="2025-2026">
                            </div>
                             <div class="col-md-4 form-group">
                                 <label>{{ __('Start Date') }}</label>
                                 <input type="text" id="session-start-date" class="form-control datepicker-wizard" placeholder="dd-mm-yyyy" autocomplete="off">
                             </div>
                             <div class="col-md-4 form-group">
                                 <label>{{ __('End Date') }}</label>
                                 <input type="text" id="session-end-date" class="form-control datepicker-wizard" placeholder="dd-mm-yyyy" autocomplete="off">
                             </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Board') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                                <select id="optional-board-id" class="form-control"></select>
                            </div>
                            <div class="col-md-6 form-group d-flex align-items-end">
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="enable-semesters" checked>
                                    <label class="custom-control-label pt-1" for="enable-semesters">{{ __('Enable Semester System') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-block semester-block mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 font-weight-bold">{{ __('Semesters') }}</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-semester-btn"><i class="mdi mdi-plus"></i> {{ __('Add Semester') }}</button>
                        </div>
                        <div id="semester-list"></div>
                    </div>
                </section>

                <section class="academy-step" data-step="medium-section">
                    <div class="step-header">
                        <h4>{{ __('Mediums & Sections') }}</h4>
                        <p>{{ __('Choose the instructional languages and operational sections in your school.') }}</p>
                    </div>
                    <h6 class="font-weight-bold mb-3">{{ __('Select Mediums') }}</h6>
                    <div class="select-grid" id="mediums-grid"></div>
                    
                    <h6 class="font-weight-bold mb-3 mt-5">{{ __('Select Sections') }}</h6>
                    <div class="select-grid" id="sections-grid"></div>
                </section>

                <section class="academy-step" data-step="shifts">
                    <div class="step-header d-flex justify-content-between align-items-start">
                        <div>
                            <h4>{{ __('School Shifts') }}</h4>
                            <p>{{ __('Manage the shifts your school operates in.') }}</p>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-shift-btn"><i class="mdi mdi-plus"></i> {{ __('Add New Shift') }}</button>
                    </div>
                    <div id="shifts-list" class="content-block"></div>
                </section>

                <section class="academy-step" data-step="streams">
                    <div class="step-header">
                        <h4>{{ __('Academic Streams') }}</h4>
                        <p>{{ __('Select the standard educational streams offered.') }}</p>
                    </div>
                    <div class="select-grid" id="streams-grid"></div>
                </section>

                <section class="academy-step" data-step="classes">
                    <div class="step-header">
                        <h4>{{ __('School Grades') }}</h4>
                        <p>{{ __('Choose the classes/grades being taught.') }}</p>
                    </div>
                    <div class="select-grid" id="classes-grid"></div>
                </section>

                <section class="academy-step" data-step="subjects">
                    <div class="step-header">
                        <h4>{{ __('Master Subjects') }}</h4>
                        <p>{{ __('Select all subjects available across the academy.') }}</p>
                    </div>
                    <div class="select-grid" id="subjects-grid"></div>
                </section>

                <section class="academy-step" data-step="mapping">
                    <div class="mapping-layout mt-2">
                        <!-- Left Sidebar (Grades) -->
                        <div class="mapping-sidebar">
                            <h6 class="sidebar-title"><i class="mdi mdi-layers"></i> CLASSES / GRADES</h6>
                            <div class="mapping-class-tabs" id="mapping-class-tabs"></div>
                        </div>

                        <!-- Right Content Area -->
                        <div class="mapping-body">
                            <!-- Medium Selection Row -->
                            <div class="">
                                <div class="mapping-block info-card">
                                    {{-- <div class="medium-switch-icon"><i class="mdi mdi-translate"></i></div> --}}
                                    <div class="info-card-header"><i class="mdi mdi-clock-outline"></i> SELECT MEDIUMS</div>
                                    <div class="medium-tabs" id="mapping-medium-tabs"></div>
                                </div>
                            </div>

                            <!-- Editable Info Row -->
                            <div class="mapping-row-grid">
                                <div class="mapping-block info-card">
                                    <div class="info-card-header"><i class="mdi mdi-clock-outline"></i> ASSIGNED SHIFTS</div>
                                    <div class="info-tag-list checkbox-list" id="mapping-shifts-list"></div>
                                </div>
                                <div class="mapping-block info-card">
                                    <div class="info-card-header"><i class="mdi mdi-source-branch text-warning"></i> <span class="text-warning">ACTIVE STREAMS & SHIFT ASSIGNMENT</span></div>
                                    <div class="info-tag-list checkbox-list borderless" id="mapping-streams-list"></div>
                                </div>
                            </div>

                            <!-- Stream Isolation Tabs (Hidden by default) -->
                            <div class="stream-tabs-wrapper mt-3" id="mapping-stream-tabs-container" style="display:none;">
                                <div class="stream-tabs-list" id="mapping-stream-tabs"></div>
                            </div>
                            
                            <div id="mapping-stream-config-body">
                                <!-- Semester Block -->
                                <div class="mapping-block stream-semester-control mt-3 mb-3 d-flex justify-content-between" id="mapping-semester-status">
                                    <div class="d-flex align-items-center" style="gap:16px;">
                                        <div class="semester-control-icon">
                                            <i class="mdi mdi-calendar-blank-outline text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 font-weight-bold text-dark">{{ __('Enable Semester System?') }}</h6>
                                            <p class="text-muted mb-0" style="font-size: 0.85rem;">{{ __('Should subjects for this class be divided by terms/semesters?') }}</p>
                                        </div>
                                    </div>
                                    <div class="semester-badge" id="stream-semester-toggle" style="cursor:pointer; user-select:none;">
                                        <i class="mdi mdi-check-circle mr-1" id="semester-toggle-icon" style="display:none;"></i> <span id="semester-toggle-text">SINGLE TERM SYSTEM</span>
                                    </div>
                                </div>
                                
                                <div class="core-general-divider mt-4 mb-2">
                                    <span class="core-tag" id="context-stream-tag">CORE / GENERAL</span>
                                </div>

                                <div class="semester-tabs-container mb-3" id="mapping-semester-tabs" style="display:none;"></div>

                                <!-- Subject Mapping Section -->
                                <div class="mapping-block subject-mapping-wrapper">
                                    <div class="subject-mapping-header">
                                        <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center" style="gap: 8px;">
                                            <i class="mdi mdi-book-open-page-variant text-primary" style="font-size: 1.25rem;"></i> {{ __('Subject Mapping') }}
                                        </h6>
                                        <div class="context-tags">
                                            <span class="context-tag text-primary font-weight-bold" id="context-medium-tag">--</span>
                                            <span class="context-tag text-muted font-weight-bold" id="context-semester-tag">--</span>
                                        </div>
                                    </div>
                                    <div id="mapping-subject-grid" class="subject-radio-grid mt-4"></div>
                                </div>

                                <!-- Elective Subject Groups Section -->
                                <div class="mapping-block elective-groups-wrapper mt-4 mb-4" id="mapping-elective-groups-container">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-dark d-flex align-items-center" style="gap: 8px;">
                                            <i class="mdi mdi-format-list-checks text-primary" style="font-size: 1.25rem;"></i> {{ __('Elective Groups') }}
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="btn-toggle-electives" style="border-radius: 20px; padding: 6px 16px;">
                                            + ENABLE ELECTIVES
                                        </button>
                                    </div>
                                    
                                    <div id="elective-groups-body" class="mt-4" style="display:none; background: #fafbfc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="font-weight-bold text-secondary mb-0" style="font-size: 0.9rem;">Configure Elective Sets</h6>
                                            <button type="button" class="btn btn-sm text-primary font-weight-bold bg-white border-primary" id="btn-add-elective-group" style="border-radius: 20px;">+ NEW GROUP</button>
                                        </div>
                                        <div id="elective-groups-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="academy-step" data-step="finish">
                    <div class="step-header">
                        <h4>{{ __('Ready to Initialize') }}</h4>
                        <p>{{ __('Review your selections. Your academic setup will be processed and applied securely.') }}</p>
                    </div>
                    <div id="setup-summary" class="setup-summary content-block"></div>

                    <div id="processing-panel" class="processing-panel d-none">
                        <h6><i class="mdi mdi-loading mdi-spin"></i> {{ __('Finalizing your academy data...') }}</h6>
                        <ul id="processing-checkpoints" class="processing-checkpoints"></ul>
                    </div>
                </section>
            </div>

            <div class="academy-setup-footer" id="wizard-footer">
                <button type="button" class="btn btn-nav btn-prev-arrow" id="prev-step-btn">
                    <i class="mdi mdi-chevron-left mr-1"></i> {{ __('PREVIOUS') }}
                </button>
                <div class="ml-auto">
                    <button type="button" class="btn btn-nav btn-next-pill btn-primary" id="next-step-btn">
                        <span class="font-weight-bold" style="font-size: 0.9rem;">{{ __('NEXT STEP') }}</span> <i class="mdi mdi-chevron-right ml-2"></i>
                    </button>
                    <button type="button" class="btn btn-nav btn-next-pill btn-primary d-none" id="finalize-setup-btn">
                        <span class="font-weight-bold" style="font-size: 0.9rem;">{{ __('COMPLETE SETUP') }}</span> <i class="mdi mdi-check-circle ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mapping Incomplete Confirmation Modal --}}
<div class="modal fade" id="mapping-incomplete-modal" tabindex="-1" role="dialog" aria-labelledby="mappingIncompleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 540px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);">
            <div class="modal-header border-0" style="padding: 24px 28px 12px;">
                <div class="d-flex align-items-center" style="gap: 14px;">
                    <div style="width: 44px; height: 44px; background: #fff7ed; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="mdi mdi-alert-outline" style="font-size: 1.5rem; color: #f97316;"></i>
                    </div>
                    <div>
                        <h5 id="mappingIncompleteModalLabel" class="mb-0 font-weight-bold text-white" style="font-size: 1.05rem;">{{ __('Incomplete Subject Mapping') }}</h5>
                        <p class="mb-0 text-white" style="font-size: 0.8rem;">{{ __('The following combinations have no subjects assigned') }}</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin: -8px -8px 0 auto; padding: 8px;">
                    <span aria-hidden="true" style="font-size: 1.5rem; color: #9ca3af;">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 16px 28px 20px;">
                <div id="mapping-pending-list" style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 14px; max-height: 280px; overflow-y: auto;"></div>
                <p class="mt-3 mb-0 text-muted" style="font-size: 0.82rem; line-height: 1.5;">
                    <i class="mdi mdi-information-outline mr-1"></i>{{ __('You can always assign subjects later from the Dashboard → Class Subjects section.') }}
                </p>
            </div>
            <div class="modal-footer border-0" style="padding: 0 28px 24px; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-light font-weight-bold" id="btn-assign-now" data-dismiss="modal" style="border-radius: 10px; padding: 9px 20px; font-size: 0.88rem; border: 1px solid #e5e7eb;">
                    <i class="mdi mdi-pencil-outline mr-1"></i> {{ __('Assign Now') }}
                </button>
                <button type="button" class="btn btn-primary font-weight-bold" id="btn-continue-anyway" style="border-radius: 10px; padding: 9px 20px; font-size: 0.88rem;">
                    {{ __('Continue Anyway') }} <i class="mdi mdi-arrow-right ml-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('js')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        window.academySetupWizard = {
            masterData: @json($masterData),
            progress: @json($progress),
            runId: @json($runId),
            routes: {
                submit: "{{ route('academy-setup-wizard.submit') }}",
                progress: "{{ url('academy-setup-wizard/progress') }}"
            },
            dashboardUrl: "{{ route('dashboard') }}",
            csrf: "{{ csrf_token() }}",
            reverb: @json($reverb)
            ,schoolId: {{ Auth::user()->school_id ?? 0 }}
        };
    </script>
    <script src="{{ asset('assets/js/academy-setup-wizard.js') }}"></script>
@endsection
