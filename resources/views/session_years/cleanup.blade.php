<div class="modal fade" id="cleanupModal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="modal-title font-weight-bold" id="cleanupModalTitle"> 
                                <i class="fa fa-calendar mr-1"></i> 
                                {{ __('manage_data') }} : <span id="cleanupSessionName"></span>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
                            </button>
                        </div>
                        <p class="text-white mt-1" style="font-size: 0.85rem;">
                            {{ __('select_data_to_remove_core_records_are_protected') }}
                        </p>
                    </div>
                </div>

                <div class="modal-body pb-0">
                    <div class="alert alert-warning d-flex align-items-center mb-4" style="background-color: #fffaf0; border: 1px solid #ffeeba; border-radius: 8px;">
                        <i class="mdi mdi-alert-outline pb-4 mr-3" style="color: #d97706; font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1 font-weight-bold text-uppercase" style="color: #d97706;">{{ __('important_notice') }}</h6>
                            <span class="text-muted" style="font-size: 0.85rem; color: #b45309;">
                                {{ __('deleting_session_data_is_permanent_this_action_may_affect_reports_and_analytical_summaries_for_this_session_year') }}
                            </span>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted font-weight-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">
                        <i class="mdi mdi-delete-outline mr-1"></i> {{ __('deletable_data') }}
                    </h6>

                    <div class="row">
                        {{-- Attendance --}}
                        <div class="col-md-6 mb-3">
                            <div class="card border cleanup-card" id="card_attendance" data-module="attendance" style="border-radius: 10px; cursor: pointer;">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="mr-3 p-2 bg-primary-light rounded text-secondary text-center" style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-account-group mdi-18px" style="line-height:1.2;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">{{ __('student_staff_attendance') }}</h6>
                                        <small class="text-muted">{{ __('includes_transportation_logs') }}</small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input cleanup-module-cb" id="cb_attendance" value="attendance">
                                        <label class="custom-control-label" for="cb_attendance"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Assignments --}}
                        <div class="col-md-6 mb-3">
                            <div class="card border cleanup-card" id="card_assignments" data-module="assignments" style="border-radius: 10px; cursor: pointer;">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="mr-3 p-2 bg-light rounded text-secondary text-center" style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-book-multiple mdi-18px" style="line-height:1.2;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">{{ __('assignments') }}</h6>
                                        <small class="text-muted">{{ __('includes_all_student_submissions') }}</small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input cleanup-module-cb" id="cb_assignments" value="assignments">
                                        <label class="custom-control-label" for="cb_assignments"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Online Exams --}}
                        <div class="col-md-6 mb-3">
                            <div class="card border cleanup-card" id="card_online_exams" data-module="online_exams" style="border-radius: 10px; cursor: pointer;">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="mr-3 p-2 bg-light rounded text-secondary text-center" style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-laptop mdi-18px" style="line-height:1.2;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">{{ __('online_exams') }}</h6>
                                        <small class="text-muted">{{ __('student_results') }}</small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input cleanup-module-cb" id="cb_online_exams" value="online_exams">
                                        <label class="custom-control-label" for="cb_online_exams"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Diary Entries --}}
                        <div class="col-md-6 mb-3">
                            <div class="card border cleanup-card" id="card_diary" data-module="diary" style="border-radius: 10px; cursor: pointer;">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="mr-3 p-2 bg-light rounded text-secondary text-center" style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-notebook-outline mdi-18px" style="line-height:1.2;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">{{ __('diary_entries') }}</h6>
                                        <small class="text-muted">{{ __('daily_teacher_student_logs') }}</small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input cleanup-module-cb" id="cb_diary" value="diary">
                                        <label class="custom-control-label" for="cb_diary"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Communications --}}
                        <div class="col-md-6 mb-3">
                            <div class="card border cleanup-card" id="card_communications" data-module="communications" style="border-radius: 10px; cursor: pointer;">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="mr-3 p-2 bg-light rounded text-secondary text-center" style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-bell-outline mdi-18px" style="line-height:1.2;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">{{ __('communications') }}</h6>
                                        <small class="text-muted">{{ __('notifications_and_announcements') }}</small>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input cleanup-module-cb" id="cb_communications" value="communications">
                                        <label class="custom-control-label" for="cb_communications"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted font-weight-bold mt-4 mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">
                        <i class="mdi mdi-lock-outline mr-1"></i> {{ __('Protected Core Data') }}
                    </h6>
                    <div class="card border mb-2" style="border-radius: 10px; border-color: #e5e7eb !important;">
                        <div class="card-body p-3">
                            <div class="row align-items-center text-muted" style="font-size: 0.85rem; font-weight: 500;">
                                <div class="col-6 col-md-3 mb-2 mb-md-0 text-center text-md-left">
                                    <i class="mdi mdi-shield-check-outline text-success mr-1"></i> {{ __('students') }}
                                </div>
                                <div class="col-6 col-md-3 mb-2 mb-md-0 text-center text-md-left">
                                    <i class="mdi mdi-shield-check-outline text-success mr-1"></i> {{ __('offline_exams') }}
                                </div>
                                <div class="col-6 col-md-3 mb-2 mb-md-0 text-center text-md-left">
                                    <i class="mdi mdi-shield-check-outline text-success mr-1"></i> {{ __('final_results') }}
                                </div>
                                <div class="col-6 col-md-3 mb-2 mb-md-0 text-center text-md-left">
                                    <i class="mdi mdi-shield-check-outline text-success mr-1"></i> {{ __('fees_and_finance') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted text-left mb-4" style="font-size: 0.75rem; font-style: italic;">
                        {{ __('note_the_session_year_record_will_remain_in_the_database_for_historical_reference') }}
                    </div>

                    <input type="hidden" id="cleanup_session_year_id">
                </div>
                <div class="modal-footer border-top-1 pb-4 pr-4">
                    <button type="button" class="btn btn-secondary rounded" data-dismiss="modal" style="font-weight: 500;">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-primary rounded" id="btnProccedCleanup" style="font-weight: 500;">
                        {{ __('proceed_to_confirm') }} <i class="mdi mdi-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Final Confirmation Modal --}}
    <div class="modal fade" id="cleanupConfirmModal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-body text-center p-5">
                    <div class="mb-4 text-danger rounded-circle bg-danger-light mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #fee2e2;">
                        <i class="mdi mdi-alert-outline" style="font-size: 40px; color: #ef4444;"></i>
                    </div>
                    <h4 class="font-weight-bold mb-3">{{ __('final_confirmation') }}</h4>
                    <p class="text-muted mb-4" id="cleanupConfirmText">
                        {{ __('you_are_about_to_permanently_delete_the_selected_data_categories_for_this_session_this_cannot_be_undone') }}
                    </p>
                    <div class="d-flex justify-content-center mt-4">
                        <button type="button" class="btn btn-secondary rounded mr-3" data-dismiss="modal" style="font-weight: 500;">{{ __('cancel') }}</button>
                        <button type="button" class="btn btn-danger rounded" id="btnConfirmCleanup" style="font-weight: 500;">
                            {{ __('yes_delete_selected_data_permanently') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>