<div class="modal fade" id="migrationModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="migrationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow-lg" id="migrationModalContent">
            <div class="modal-header bg-white border-bottom-0">
                <h5 class="modal-title font-weight-bold" id="migrationModalLabel">
                    <i class="mdi mdi-wrench text-primary mr-2"></i> {{ __('Session Migration Intelligence') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="mig-wizard-container">
                <!-- Stepper Header -->
                <div class="mig-stepper">
                    <div class="mig-step-item active" id="mig-stepper-0">
                        <span class="mig-number">1</span> {{ __('Modules') }}
                    </div>
                    <div class="mig-step-item" id="mig-stepper-1">
                        <span class="mig-number">2</span> {{ __('Alignment') }}
                    </div>
                    <div class="mig-step-item" id="mig-stepper-2">
                        <span class="mig-number">3</span> {{ __('Review') }}
                    </div>
                </div>

                <!-- Step 0: Modules -->
                <div class="mig-step-content active" id="mig-step-0">
                    <div class="alert alert-info border mb-4">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-information-outline text-primary mr-3 mdi-24px"></i>
                            <p class="mb-0">{{ __('Select the educational and administrative modules you wish to bridge into the new session.') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        @php
                            $modules = [
                                ['id' => 'cb_semesters', 'val' => 'semesters', 'label' => 'Semesters', 'icon' => 'mdi-calendar', 'desc' => 'Clone academic terms and auto-adjust start and end dates for the new year'],
                                ['id' => 'cb_class_subjects', 'val' => 'class_subjects', 'label' => 'Class Subjects', 'icon' => 'mdi-book-open-variant', 'desc' => 'Carry forward all assigned class subjects'],
                                ['id' => 'cb_teachers', 'val' => 'teachers', 'label' => 'Subject Teachers', 'icon' => 'mdi-account-convert', 'desc' => 'Replicate existing teacher allocations across all classes and subjects'],
                                ['id' => 'cb_class_timetables', 'val' => 'class_timetables', 'label' => 'Timetables', 'icon' => 'mdi-history', 'desc' => 'Duplicate weekly class schedules, periods and break timings'],
                                ['id' => 'cb_fees', 'val' => 'fees', 'label' => 'Fee Policy', 'icon' => 'mdi-cash-multiple', 'desc' => 'Copy fee structures installments'],
                                ['id' => 'cb_leave_settings', 'val' => 'leave_settings', 'label' => 'Leave Logic', 'icon' => 'mdi-sync', 'desc' => 'Copy staff leave types policies and standard allocation rules'],
                            ];
                        @endphp
                        @foreach($modules as $mod)
                            <div class="col-md-4 mb-3" id="div_{{ $mod['id'] }}" @if($mod['val'] == 'semesters') style="display:none;" @endif>
                                <div class="mig-card">
                                    <div class="d-flex align-items-start">
                                        <i class="mdi {{ $mod['icon'] }} mdi-24px text-primary mr-3"></i>
                                        <div class="flex-grow-1">
                                            <div class="form-check">
                                                <label class="form-check-label font-weight-bold p-0">
                                                    <input type="checkbox" class="form-check-input migration-option" name="migrate_options[]" value="{{ $mod['val'] }}" id="{{ $mod['id'] }}">
                                                    {{ __($mod['label']) }}
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-0 mt-1">{{ __($mod['desc']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Step 1: Temporal Alignment -->
                <div class="mig-step-content" id="mig-step-1">
                    <div class="mb-4">
                        <h6 class="font-weight-bold"><i class="mdi mdi-calendar-sync text-warning mr-2"></i> {{ __('Semester Date Configuration') }}</h6>
                        <p class="text-muted small">{{ __('Please define the operational dates for semesters in the upcoming session year.') }}</p>
                    </div>
                    <div id="semester-dates-container">
                        <div class="text-center py-5 text-muted">
                            <i class="mdi mdi-calendar-blank mdi-48px"></i>
                            <p>{{ __('No temporal configuration required.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Final Intelligence Review -->
                <div class="mig-step-content" id="mig-step-2">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-4 text-uppercase small text-muted">{{ __('Migration Manifesto') }}</h6>
                            <div id="mig-review-summary"></div>
                            <div class="mt-4 p-3 bg-white rounded border border-warning shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-alert mdi-24px text-warning mr-3"></i>
                                    <p class="mb-0 small text-dark">
                                        {{ __('Confirmation will initiate the data replication process. This action is background-processed for system stability.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Footer -->
                <div class="modal-footer bg-white border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-secondary ml-auto px-4" id="mig-btn-prev" style="display:none;">{{ __('Previous') }}</button>
                    <button type="button" class="btn btn-theme px-4" id="mig-btn-next">{{ __('Next Step') }} <i class="mdi mdi-arrow-right ml-1"></i></button>
                    <button type="button" class="btn btn-primary btn-theme px-4" id="mig-btn-submit" style="display:none;">{{ __('Initiate Migration') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>