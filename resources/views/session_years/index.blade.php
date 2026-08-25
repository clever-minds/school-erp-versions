@extends('layouts.master')

@section('title')
    {{ __('Session Years') }}
@endsection

@section('css')
    <style>
        .mig-wizard-container { min-height: 450px; display: flex; flex-direction: column; }
        .mig-stepper { display: flex; justify-content: space-around; background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 1rem 0; }
        .mig-step-item { display: flex; align-items: center; color: #adb5bd; font-weight: 600; font-size: 0.9rem; position: relative; }
        .mig-step-item.active { color: var(--theme-color); }
        .mig-step-item.completed { color: #28a745; }
        .mig-step-item .mig-number { width: 24px; height: 24px; border-radius: 50%; border: 2px solid currentColor; display: flex; align-items: center; justify-content: center; margin-right: 8px; font-size: 0.75rem; }
        .mig-step-content { display: none; padding: 2rem; flex-grow: 1; }
        .mig-step-content.active { display: block; animation: migFadeIn 0.3s ease; }
        .mig-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 1.25rem; transition: all 0.2s; cursor: pointer; height: 100%; position: relative; }
        .mig-card:hover { border-color: var(--theme-color); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .mig-card.selected { border-color: var(--theme-color); background: rgba(var(--theme-color-rgb), 0.02); }
        .mig-card .form-check { margin: 0; padding: 0; }
        .mig-card .form-check-label { padding-left: 25px; cursor: pointer; display: block; width: 100%; }
        @keyframes migFadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .mig-review-row { display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #f1f1f1; }
        .mig-review-row i { font-size: 1.25rem; margin-right: 1rem; }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Session Years') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <span class="text-danger mt-4 ml-4">
                        {{ __('note If you change the session year the chat history will be deleted') }}
                    </span>
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Create Session Years') }}
                        </h4>
                        <form action="{{ route('session-year.store') }}" class="create-form pt-3 " id="formdata"
                            method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('name', null, ['required', 'placeholder' => __('name'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('start_date', null, ['required', 'placeholder' => __('start_date'), 'class' => 'datepicker-popup form-control', 'autocomplete' => 'off']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('end_date', null, ['required', 'placeholder' => __('end_date'), 'class' => 'datepicker-popup form-control', 'autocomplete' => 'off']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Migrate Data From') }}</label>
                                    <select name="migrate_session_year_id" id="migrate_session_year_id"
                                        class="form-control">
                                        <option value="">{{ __('Select Session Year') }}</option>
                                        @foreach($sessionYears as $year)
                                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Migration Modal Redux --}}
                            @include('session_years.migration')
                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                            <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('List Session Years') }}
                        </h4>
                        <div class="col-12 mt-4 text-right">
                            <b><a href="#" class="table-list-type active mr-2" data-id="0">{{__('all')}}</a></b> | <a
                                href="#" class="ml-2 table-list-type" data-id="1">{{__("Trashed")}}</a>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ route('session-year.show', 1) }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                    data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                    data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                    data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                    data-export-options='{ "fileName": "session-year-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}'
                                    data-query-params="queryParams" data-escape="true">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                                {{__('id')}}</th>
                                            <th scope="col" data-field="no">{{__('no.')}}</th>
                                            <th scope="col" data-field="name">{{__('name')}}</th>
                                            <th scope="col" data-field="start_date" data-sortable="true">
                                                {{__('start_date')}}</th>
                                            <th scope="col" data-field="end_date" data-sortable="true">{{__('end_date')}}
                                            </th>
                                            <th scope="col" data-field="default" data-sortable="true"
                                                data-formatter="yesAndNoStatusFormatter">{{__('default')}}</th>
                                            <th data-events="sessionYearEvents" scope="col" data-field="operate"
                                                data-escape="false">{{__('action')}}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ __('Edit Session Years') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>

                <form action="{{ url('session-year') }}" class="edit-form pt-3" data-success-function="formSuccessFunction"
                    id="formdata" method="POST" novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-12 col-sm-12 col-md-12">
                                <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                {!! Form::text('name', null, ['required', 'placeholder' => __('name'), 'class' => 'form-control', 'id' => 'edit-name']) !!}
                            </div>
                            <div class="form-group col-12 col-sm-12 col-md-12">
                                <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                {!! Form::text('start_date', null, ['required', 'placeholder' => __('start_date'), 'class' => 'datepicker-popup form-control', 'id' => 'edit-start-date']) !!}
                            </div>
                            <div class="form-group col-12 col-sm-12 col-md-12">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                {!! Form::text('end_date', null, ['required', 'placeholder' => __('end_date'), 'class' => 'datepicker-popup form-control', 'id' => 'edit-end-date']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Data Cleanup / Segregation Modal --}}
    @include('session_years.cleanup')

@endsection
@section('script')
    <script>
        const formSuccessFunction = () => {
            if (window.location.href.includes('session-year')) {
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        }

        // --- Migration Wizard Redux Logic ---
        let currentStep = 0;
        const totalStepsCount = 3;

        function updateWizardUI() {
            // Update content visibility
            $('.mig-step-content').removeClass('active');
            $(`#mig-step-${currentStep}`).addClass('active');

            // Update stepper visual state
            $('.mig-step-item').removeClass('active').removeClass('completed');
            $('.mig-step-item').each(function(idx) {
                if (idx < currentStep) $(this).addClass('completed');
                if (idx === currentStep) $(this).addClass('active');
            });

            // Update footer buttons
            if (currentStep === 0) {
                $('#mig-btn-prev').hide();
                $('#mig-btn-next').show();
                $('#mig-btn-submit').hide();
            } else if (currentStep === 1) {
                $('#mig-btn-prev').show();
                $('#mig-btn-next').show();
                $('#mig-btn-submit').hide();
            } else if (currentStep === 2) {
                $('#mig-btn-prev').show();
                $('#mig-btn-next').hide();
                $('#mig-btn-submit').show();
                generateMigReview();
            }
        }

        function generateMigReview() {
            let html = '';
            const metadata = {
                'semesters': { label: 'Academic Semesters', icon: 'mdi-calendar', color: 'text-primary' },
                'class_subjects': { label: 'Class Subjects', icon: 'mdi-book-open-variant', color: 'text-primary' },
                'teachers': { label: 'Subject Teachers', icon: 'mdi-account-convert', color: 'text-info' },
                'class_timetables': { label: 'Class Timetables', icon: 'mdi-history', color: 'text-warning' },
                'fees': { label: 'Fee Structures', icon: 'mdi-cash-multiple', color: 'text-success' },
                'leave_settings': { label: 'Leave Master Logic', icon: 'mdi-sync', color: 'text-secondary' },
                'staff_payroll_settings': { label: 'Staff Payroll Settings', icon: 'mdi-cash-multiple', color: 'text-success' }
            };

            $('.migration-option:checked').each(function() {
                const val = $(this).val();
                if (metadata[val]) {
                    html += `
                        <div class="mig-review-row">
                            <i class="mdi ${metadata[val].icon} ${metadata[val].color}"></i>
                            <div>
                                <h6 class="mb-0 font-weight-bold">${metadata[val].label}</h6>
                                <span class="text-muted small">{{ __('Ready for baseline replication') }}</span>
                            </div>
                        </div>
                    `;
                }
            });

            if ($('#cb_semesters').is(':checked')) {
                const semCount = $('.semester-date-row').length;
                const label = semCount === 1 ? "{{ __('Semester timeline') }}" : "{{ __('Semester timelines') }}";
                html += `
                    <div class="p-2 mt-2 bg-light rounded text-center small text-primary font-weight-bold">
                        <i class="mdi mdi-check-underline mr-1"></i> ${semCount} ${label} {{ __('configured') }}
                    </div>
                `;
            }

            if ($('.migration-option[value="fees"]:checked').length > 0) {
                 html += `
                    <div class="alert alert-warning mt-3 mb-0 p-3 small d-flex align-items-center" style="color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; border-left: 4px solid #ffbc34;">
                        <i class="mdi mdi-information-outline mr-2 mdi-18px"></i>
                        <div class="text-left">
                            <h6 class="mb-1 font-weight-bold" style="color: #856404;">Fee Migration Note</h6>
                            Migrated fees will be set to the session end date by default. Please review and update them after migration.
                        </div>
                    </div>
                `;
            }

            $('#mig-review-summary').html(html || '<div class="text-center py-4 text-muted">{{ __("No data modules selected.") }}</div>');
        }

        const parseDMY = (dateStr) => {
            if (!dateStr) return null;
            const parts = dateStr.split('-');
            return new Date(parts[2], parts[1] - 1, parts[0]);
        };

        // Datepicker Isolation & Barrier
        function initMigDatepickers() {
            let sessionStart = $('input[name="start_date"]').val();
            let sessionEnd = $('input[name="end_date"]').val();

            $('#semester-dates-container .datepicker-popup').each(function() {
                $(this).datepicker({
                    format: 'dd-mm-yyyy',
                    autoclose: true,
                    todayHighlight: true,
                    startDate: sessionStart,
                    endDate: sessionEnd,
                    container: '#migrationModal' // Keep dropdowns inside the namespaced modal
                }).on('show', function(e) {
                    // Close all other datepickers when this one opens
                    $('.datepicker-popup').not(this).datepicker('hide');
                    e.stopPropagation();
                });
            });

            // Ensure datepickers close when clicking any navigation button or modal background
            $('#mig-btn-next, #mig-btn-prev, #mig-btn-submit, .close, [data-dismiss="modal"]').on('click', function() {
                $('.datepicker-popup').datepicker('hide');
            });
        }

        // --- Event Handlers (Scoping to Modal to avoid global JS interference) ---
        const $migModal = $('#migrationModal');

        // Main Trigger
        $(document).on('click', '#create-btn', function (e) {
            if ($('#migrate_session_year_id').val() != '') {
                let name = $('input[name="name"]').val();
                let start = $('input[name="start_date"]').val();
                let end = $('input[name="end_date"]').val();

                if (!name || !start || !end) {
                    showErrorToast("{{ __('Please fill name, start date and end date first') }}");
                    return false;
                }

                e.preventDefault();
                currentStep = 0;
                updateWizardUI();
                $migModal.modal('show');
                // Ensure semesters are loaded if session is already selected
                $('#migrate_session_year_id').trigger('change');
            }
        });

        // Catch-all to block global JS for ANY button in the modal
        $migModal.on('click', '.btn, .close', function(e) {
            e.stopPropagation();
            if ($(this).attr('data-dismiss') === 'modal' || $(this).hasClass('close')) {
                $migModal.modal('hide');
            }
        });

        // Step Navigation
        $migModal.on('click', '#mig-btn-next', function(e) {
            e.stopPropagation(); // Block global wizard
            
            if (currentStep === 0) {
                if ($('.migration-option:checked').length === 0) {
                    showErrorToast("{{ __('Please select at least one module.') }}");
                    return;
                }
            }

            if (currentStep === 1) {
                if ($('#cb_semesters').is(':checked')) {
                    let isValid = true;
                    $('#semester-dates-container input[required]').each(function() {
                        if (!$(this).val()) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                        } else {
                            $(this).removeClass('is-invalid');
                        }
                    });

                    if (!isValid) {
                        showErrorToast("{{ __('Please complete all semester timing definitions.') }}");
                        return;
                    }

                    let sessionStart = parseDMY($('input[name="start_date"]').val());
                    let sessionEnd = parseDMY($('input[name="end_date"]').val());
                    let allInRange = true;
                    
                    $('.semester-date-row').each(function() {
                        let subStart = parseDMY($(this).find('input[name*="[start_date]"]').val());
                        let subEnd = parseDMY($(this).find('input[name*="[end_date]"]').val());
                        if (subStart < sessionStart || subStart > sessionEnd || subEnd < sessionStart || subEnd > sessionEnd) {
                            allInRange = false;
                            $(this).find('input').addClass('is-invalid');
                        }
                    });

                    if (!allInRange) {
                        showErrorToast("{{ __('Semester dates must align within the session window.') }}");
                        return;
                    }
                }
            }

            if (currentStep < totalStepsCount - 1) {
                currentStep++;
                updateWizardUI();
            }
        });

        $migModal.on('click', '#mig-btn-prev', function(e) {
            e.stopPropagation(); // Block global wizard
            if (currentStep > 0) {
                currentStep--;
                updateWizardUI();
            }
        });

        $migModal.on('click', '#mig-btn-submit', function(e) {
            e.stopPropagation(); // Block global wizard
            if ($('#cb_semesters').is(':disabled') && $('#cb_semesters').is(':checked')) {
                if (!$('input[name="migrate_options[]"][value="semesters"]').length) {
                    $('<input>').attr({type: 'hidden', name: 'migrate_options[]', value: 'semesters'}).appendTo('#formdata');
                }
            }
            $migModal.modal('hide');
            $('#formdata').submit();
        });

        // Card Interaction
        $migModal.on('click', '.mig-card', function(e) {
            e.stopPropagation();
            // If they clicked the actual checkbox or label, let them handle it normally
            if ($(e.target).is('input') || $(e.target).is('label')) {
                return;
            }
            const $checkbox = $(this).find('input[type="checkbox"]');
            if (!$checkbox.is(':disabled')) {
                $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
            }
        });

        $migModal.on('change', '.migration-option', function (e) {
            e.stopPropagation();
            $(this).closest('.mig-card').toggleClass('selected', $(this).is(':checked'));

            let semestersExist = $('#div_cb_semesters').is(':visible');
            let semestersChecked = $('#cb_semesters').is(':checked');
            let classSubjectChecked = $('#cb_class_subjects').is(':checked');
            let teachersChecked = $('#cb_teachers').is(':checked');

            // 1. Class Subject Dependency
            if (semestersExist) {
                if (!semestersChecked) {
                    $('#cb_class_subjects').prop('checked', false).prop('disabled', true);
                    $('#cb_class_subjects').closest('.mig-card').css('opacity', '0.5');
                } else {
                    $('#cb_class_subjects').prop('disabled', false);
                    $('#cb_class_subjects').closest('.mig-card').css('opacity', '1');
                }
            }

            // 2. Teachers Dependency
            if (!classSubjectChecked) {
                $('#cb_teachers').prop('checked', false).prop('disabled', true);
                $('#cb_teachers').closest('.mig-card').css('opacity', '0.5');
            } else {
                $('#cb_teachers').prop('disabled', false);
                $('#cb_teachers').closest('.mig-card').css('opacity', '1');
            }

            // 3. Timetable Dependency
            if (!classSubjectChecked || !teachersChecked) {
                $('#cb_class_timetables').prop('checked', false).prop('disabled', true);
                $('#cb_class_timetables').closest('.mig-card').css('opacity', '0.5');
            } else {
                $('#cb_class_timetables').prop('disabled', false);
                $('#cb_class_timetables').closest('.mig-card').css('opacity', '1');
            }
        });

        $(document).on('change', '#migrate_session_year_id', function (e) {
            let sessionYearId = $(this).val();
            if (sessionYearId) {
                $.ajax({
                    url: "{{ url('session-year/get-semesters') }}/" + sessionYearId,
                    type: 'GET',
                    success: function (response) {
                        let semesters = response.semesters;
                        let mustRequireSemesters = response.must_require_semesters;

                        if (semesters.length > 0) {
                            $('#div_cb_semesters').show();
                            $('#cb_semesters').prop('checked', true);

                            if (mustRequireSemesters) {
                                $('#cb_semesters').prop('disabled', true);
                                if (!$('#semester-mandatory-note').length) {
                                    $('#div_cb_semesters label').append('<small id="semester-mandatory-note" class="text-info ml-2">(Required)</small>');
                                }
                                $('#cb_semesters').closest('.mig-card').addClass('selected');
                            } else {
                                $('#cb_semesters').prop('disabled', false);
                                $('#semester-mandatory-note').remove();
                            }

                            let html = '<div class="table-responsive"><table class="table table-bordered"><thead><tr class="bg-light"><th>{{ __("Semester") }}</th><th>{{ __("Start Date") }}</th><th>{{ __("End Date") }}</th></tr></thead><tbody>';
                            semesters.forEach(function (semester) {
                                html += `
                                    <tr class="semester-date-row">
                                        <td class="font-weight-bold align-middle">${semester.name}</td>
                                        <input type="hidden" name="semester_data[${semester.id}][id]" value="${semester.id}">
                                        <td><input type="text" name="semester_data[${semester.id}][start_date]" class="datepicker-popup form-control" placeholder="{{ __('Start') }}" required autocomplete="off"></td>
                                        <td><input type="text" name="semester_data[${semester.id}][end_date]" class="datepicker-popup form-control" placeholder="{{ __('End') }}" required autocomplete="off"></td>
                                    </tr>
                                `;
                            });
                            html += '</tbody></table></div>';
                            $('#semester-dates-container').html(html);
                            initMigDatepickers();
                        } else {
                            $('#div_cb_semesters').hide();
                            $('#cb_semesters').prop('checked', false);
                            $('#semester-dates-container').html('<div class="text-center py-5 text-muted"><i class="mdi mdi-calendar-blank mdi-48px"></i><p>{{ __("No temporal configuration required.") }}</p></div>');
                        }
                        $('.migration-option').trigger('change');
                    }
                });
            } else {
                $('#div_cb_semesters').hide();
                $('#cb_semesters').prop('checked', false);
                $('#semester-dates-container').html('');
                $('.migration-option').trigger('change');
            }
        });

        // --- Permanent Delete (Trash Action) Modal Logic ---
        $(document).on('click', '.cleanup-data-btn', function(e) {
            e.preventDefault();
            let sessionYearId = $(this).data('id');
            let tr = $(this).closest('tr');
            
            $('#cleanup_session_year_id').val(sessionYearId);
            
            // Reset modal state
            $('.cleanup-module-cb').prop('checked', false);
            $('.cleanup-card').removeClass('border-primary shadow-sm').addClass('border');
            $('#btnProccedCleanup').prop('disabled', true);
            
            $.ajax({
                url: "{{ url('session-year/cleanup-info') }}/" + sessionYearId,
                type: 'GET',
                success: function(res) {
                    if(!res.error) {
                        const deletable = res.deletable_data;
                        
                        function toggleCard(module, exists) {
                            let $cb = $('#cb_' + module);
                            let $card = $('#card_' + module);
                            if(exists) {
                                $cb.prop('disabled', false);
                                $card.css('opacity', '1').removeClass('bg-light');
                            } else {
                                $cb.prop('disabled', true);
                                $card.css('opacity', '0.5').addClass('bg-light');
                            }
                        }

                        
                        if (res.has_core_data) {
                            $('#btnProccedCleanup').prop('disabled', true);
                            $('#cleanupSessionName').text(res.session_year.name);
                        } else {
                            $('#btnProccedCleanup').prop('disabled', false);
                            $('#cleanupSessionName').text(res.session_year.name + ' (No Core Data)');
                        }

                        toggleCard('attendance', deletable.attendance);
                        toggleCard('assignments', deletable.assignments);
                        toggleCard('online_exams', deletable.online_exams);
                        toggleCard('diary', deletable.diary);
                        toggleCard('communications', deletable.communications);

                        $('#cleanupModal').modal('show');
                    } else {
                        showErrorToast(res.message);
                    }
                },
                error: function() {
                    showErrorToast("{{ __('Failed to fetch session year data.') }}");
                }
            });
        });

        // Select card behavior
        $('.cleanup-card').on('click', function(e) {
            // Prevent trigger if clicking on the actual checkbox/label to avoid double toggle
            if ($(e.target).is('input') || $(e.target).is('label') || $(e.target).hasClass('custom-control')) {
                return;
            }
            let $cb = $(this).find('.cleanup-module-cb');
            if (!$cb.prop('disabled')) {
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            }
        });

        $('.cleanup-module-cb').on('change', function() {
            let $card = $(this).closest('.cleanup-card');
            if ($(this).is(':checked')) {
                $card.removeClass('border').addClass('border-primary shadow-sm');
                // style icon container inside active card
                $card.find('.rounded').removeClass('bg-light text-secondary').addClass('bg-primary-light text-primary').css({'background': '#e0f2fe', 'color': '#0284c7'});
            } else {
                $card.addClass('border').removeClass('border-primary shadow-sm');
                $card.find('.rounded').addClass('bg-light text-secondary').removeClass('bg-primary-light text-primary').css({'background': '', 'color': ''});
            }

            // Enable proceed button if at least one checkbox is checked
            if($('.cleanup-module-cb:checked').length > 0) {
                $('#btnProccedCleanup').prop('disabled', false);
            } else {
                $('#btnProccedCleanup').prop('disabled', true);
            }
        });

        // Proceed to confirm
        $('#btnProccedCleanup').on('click', function() {
            let sessionName = $('#cleanupSessionName').text();
            $('#cleanupConfirmText').html("{{ __('You are about to permanently delete the selected data categories for the session') }} <b>" + sessionName + "</b>. {{ __('This cannot be undone.') }}");
            
            $('#cleanupModal').modal('hide');
            // short delay to avoid modal background flicker
            setTimeout(() => {
                $('#cleanupConfirmModal').modal('show');
            }, 300);
        });

        // Real Submission for Hard Delete
        $('#btnConfirmCleanup').on('click', function() {
            let sessionYearId = $('#cleanup_session_year_id').val();
            let selectedModules = [];
            $('.cleanup-module-cb:checked').each(function() {
                selectedModules.push($(this).val());
            });

            // if(selectedModules.length === 0) {
            //     showErrorToast("{{ __('Please select modules to delete.') }}");
            //     $('#cleanupConfirmModal').modal('hide');
            //     return;
            // }

            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> {{ __("Processing...") }}');

            $.ajax({
                url: "{{ url('session-year/cleanup-data') }}/" + sessionYearId,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    modules: selectedModules
                },
                success: function(res) {
                    if(!res.error) {
                        $('#cleanupConfirmModal').modal('hide');
                        showSuccessToast(res.message);
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message);
                    }
                },
                error: function() {
                    showErrorToast("{{ __('An error occurred while permanently deleting data.') }}");
                },
                complete: function() {
                    $('#btnConfirmCleanup').prop('disabled', false).html("{{ __('Yes, Delete Selected Data Permanently') }}");
                }
            });
        });
    </script>
@endsection