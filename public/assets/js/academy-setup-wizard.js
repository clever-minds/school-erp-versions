(function () {
    const config = window.academySetupWizard || {};
    if (!config.masterData) {
        return;
    }

    const orderedSteps = [
        { key: 'welcome', label: 'Welcome' },
        { key: 'session', label: 'Academy Year' },
        { key: 'medium-section', label: 'Mediums & Sections' },
        { key: 'shifts', label: 'Shifts' },
        { key: 'streams', label: 'Streams' },
        { key: 'classes', label: 'Classes' },
        { key: 'subjects', label: 'Subjects' },
        { key: 'mapping', label: 'Mapping' },
        { key: 'finish', label: 'Finish' }
    ];

    const state = {
        currentStepIndex: 0,
        runId: config.runId || null,
        mappingMediumId: null,
        mappingClassId: null,
        mappingStreamId: 0,
        mappingSemesterId: null,
        submitting: false,
        values: {
            session: {
                name: '',
                start_date: '',
                end_date: '',
                board_id: null,
                enable_semesters: true,
                semesters: [
                    { name: 'Semester 1', start_date: '', end_date: '' },
                    { name: 'Semester 2', start_date: '', end_date: '' }
                ]
            },
            medium_ids: [],
            section_ids: [],
            stream_ids: [],
            class_ids: [],
            subject_ids: [],
            shifts: [],
            mapping: {}
        }
    };

    const dom = {
        stepsContainer: $('#academy-steps'),
        stepSections: $('.academy-step'),
        beginBtn: $('#begin-setup-btn'),
        prevBtn: $('#prev-step-btn'),
        nextBtn: $('#next-step-btn'),
        finalizeBtn: $('#finalize-setup-btn'),
        addSemesterBtn: $('#add-semester-btn'),
        semesterList: $('#semester-list'),
        semesterBlock: $('.semester-block'),
        mediumsGrid: $('#mediums-grid'),
        sectionsGrid: $('#sections-grid'),
        streamsGrid: $('#streams-grid'),
        classesGrid: $('#classes-grid'),
        subjectsGrid: $('#subjects-grid'),
        addShiftBtn: $('#add-shift-btn'),
        shiftsList: $('#shifts-list'),
        mediumTabs: $('#mapping-medium-tabs'),
        classTabs: $('#mapping-class-tabs'),
        mappingSubjectGrid: $('#mapping-subject-grid'),
        summary: $('#setup-summary'),
        processingPanel: $('#processing-panel'),
        processingList: $('#processing-checkpoints'),
        skipBtn: $('#skip-installation-btn')
    };

    let pollingTimer = null;
    let pusherChannel = null;

    init();

    function init() {
        initDatePicker();
        renderStepChips();
        renderMasterGrids();
        renderSemesters();
        renderShifts();
        hydrateProgress(config.progress || {});
        bindEvents();
        renderCurrentStep();
    }

    function bindEvents() {
        dom.beginBtn.on('click', () => goToStep(1));
        dom.prevBtn.on('click', () => goToStep(Math.max(1, state.currentStepIndex - 1)));
        dom.nextBtn.on('click', handleNextStep);
        dom.finalizeBtn.on('click', submitSetup);
        dom.addSemesterBtn.on('click', addSemesterRow);
        dom.addShiftBtn.on('click', addShiftRow);
        dom.skipBtn.on('click', () => {
            state.allowExit = true;
            window.location.href = config.dashboardUrl;
        });

        $(document).on('change', '.session-input', syncSessionValues);
        $(document).on('change', '.semester-input', syncSemesters);
        $(document).on('change', '.shift-input', syncShifts);
        $(document).on('change', '.datepicker-wizard', function () {
            if ($(this).hasClass('session-input')) syncSessionValues();
            if ($(this).hasClass('semester-input')) syncSemesters();
        });
        $(document).on('click', '.remove-semester', function () {
            const idx = Number($(this).data('idx'));
            state.values.session.semesters.splice(idx, 1);
            renderSemesters();
        });
        $(document).on('click', '.remove-shift', function () {
            const idx = Number($(this).data('idx'));
            state.values.shifts.splice(idx, 1);
            renderShifts();
        });

        window.addEventListener('beforeunload', function (event) {
            if (!state.submitting && !state.allowExit && state.currentStepIndex > 0) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    function renderStepChips() {
        const lineHtml = '<div class="progress-line"></div>';
        dom.stepsContainer.empty().append(lineHtml);

        orderedSteps.slice(1).forEach((step, index) => {
            const actualIndex = index + 1;
            const isActive = actualIndex === state.currentStepIndex;
            const isDone = actualIndex < state.currentStepIndex || state.currentStepIndex === orderedSteps.length - 1 && state.submitting;

            const statusClass = isActive ? 'active' : (isDone ? 'done' : '');
            const iconText = isDone ? '<i class="mdi mdi-check"></i>' : actualIndex;

            dom.stepsContainer.append(`
                <div class="progress-step-item ${statusClass}" data-index="${actualIndex}">
                    <div class="progress-step-circle">${iconText}</div>
                    <div class="progress-step-label">${step.label}</div>
                </div>
            `);
        });

        if (window.innerWidth <= 768) {
            const count = orderedSteps.length - 1;
            const linePercent = (count - 1) * 33.33;
            $('.progress-line').css('width', `${linePercent}%`);
        }

        setTimeout(scrollActiveStepIntoView, 50);
    }

    function scrollActiveStepIntoView() {
        if (window.innerWidth > 768) return;

        const $activeItem = dom.stepsContainer.find('.progress-step-item.active');
        if (!$activeItem.length) return;

        const containerWidth = dom.stepsContainer.width();
        const itemWidth = $activeItem.outerWidth();
        const itemOffset = $activeItem.position().left;

        const actualIndex = Number($activeItem.data('index'));
        const totalSteps = orderedSteps.length - 1;

        let scrollPos;
        if (actualIndex === 1) {
            scrollPos = 0;
        } else if (actualIndex === totalSteps) {
            scrollPos = dom.stepsContainer[0].scrollWidth - containerWidth;
        } else {
            scrollPos = dom.stepsContainer.scrollLeft() + itemOffset - (containerWidth / 2) + (itemWidth / 2);
        }

        dom.stepsContainer.stop().animate({
            scrollLeft: scrollPos
        }, 300);
    }

    function renderCurrentStep() {
        const stepKey = orderedSteps[state.currentStepIndex].key;
        dom.stepSections.removeClass('active');
        dom.stepSections.filter(`[data-step="${stepKey}"]`).addClass('active');

        dom.prevBtn.toggleClass('d-none', state.currentStepIndex <= 1 || state.currentStepIndex === 0);
        dom.nextBtn.toggleClass('d-none', state.currentStepIndex === 0 || state.currentStepIndex === orderedSteps.length - 1);
        dom.finalizeBtn.toggleClass('d-none', state.currentStepIndex !== orderedSteps.length - 1);

        const footer = $('#wizard-footer');
        if (footer.length) {
            footer.toggleClass('d-none', state.currentStepIndex === 0);
        }

        $('.academy-setup-header').toggleClass('d-none', state.currentStepIndex === 0);
        $('.academy-setup-progress-wrapper').toggleClass('d-none', state.currentStepIndex === 0);

        renderStepChips();

        if (stepKey === 'mapping') {
            renderMappingTabs();
        }
        if (stepKey === 'finish') {
            renderSummary();
        }
    }

    function goToStep(index) {
        state.currentStepIndex = index;
        renderCurrentStep();
    }

    function handleNextStep() {
        if (!validateStep(state.currentStepIndex)) {
            return;
        }
        goToStep(Math.min(orderedSteps.length - 1, state.currentStepIndex + 1));
    }

    function validateStep(stepIndex) {
        clearErrors();
        switch (orderedSteps[stepIndex].key) {
            case 'session':
                return validateSessionDates(false);

                if (!isValid) {
                    scrollToFirstError();
                }
                return isValid;
            case 'medium-section':
                if (!state.values.medium_ids.length || !state.values.section_ids.length) {
                    showError('Please select at least one medium and one section.');
                    return false;
                }
                return true;
            case 'shifts':
                syncShifts();
                const shifts = state.values.shifts || [];
                let isShiftValid = true;

                if (!shifts.length) {
                    showError('Please add at least one shift.');
                    return false;
                }

                shifts.forEach((shift, idx) => {
                    const nameEl = $(`.shift-input[data-idx="${idx}"][data-key="name"]`);
                    const startEl = $(`.shift-input[data-idx="${idx}"][data-key="start_time"]`);
                    const endEl = $(`.shift-input[data-idx="${idx}"][data-key="end_time"]`);

                    if (!shift.name) {
                        highlightError(nameEl, 'Shift name is required');
                        isShiftValid = false;
                    }
                    if (!shift.start_time) {
                        highlightError(startEl, 'Start time is required');
                        isShiftValid = false;
                    }
                    if (!shift.end_time) {
                        highlightError(endEl, 'End time is required');
                        isShiftValid = false;
                    }

                    if (shift.start_time && shift.end_time) {
                        if (shift.start_time >= shift.end_time) {
                            highlightError(endEl, `Shift "${shift.name}" end time must be after start time.`);
                            isShiftValid = false;
                        }
                    }
                });

                if (!isShiftValid) {
                    scrollToFirstError();
                }
                return isShiftValid;
            case 'classes':
                if (!state.values.class_ids.length) {
                    showError('Please select at least one class.');
                    return false;
                }
                return true;
            case 'subjects':
                if (!state.values.subject_ids.length) {
                    showError('Please select at least one subject.');
                    return false;
                }
                return true;
            case 'mapping':
                const duplicates = checkMappingIntegrity();
                if (duplicates.length > 0) {
                    showError(duplicates[0]);
                    return false;
                }

                if (!hasValidMapping()) {
                    const pending = collectPendingMappings();
                    const listHtml = pending.map(item => `
                        <div class="d-flex align-items-start mb-2" style="gap: 10px;">
                            <i class="mdi mdi-circle-medium text-warning mt-1" style="font-size: 1rem; flex-shrink: 0;"></i>
                            <span style="font-size: 0.88rem; color: #374151; line-height: 1.4;">${item}</span>
                        </div>`).join('');
                    $('#mapping-pending-list').html(listHtml);
                    $('#mapping-incomplete-modal').modal('show');
                    // Intercept Continue Anyway — bypass validation and advance
                    $('#btn-continue-anyway').off('click').on('click', function () {
                        $('#mapping-incomplete-modal').modal('hide');
                        goToStep(Math.min(orderedSteps.length - 1, state.currentStepIndex + 1));
                    });
                    return false;
                }
                return true;
            default:
                return true;
        }
    }

    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
    }

    function highlightError($el, message) {
        $el.addClass('is-invalid');
        if (message) {
            showError(message);
        }
    }

    function scrollToFirstError() {
        const firstError = $('.is-invalid').first();
        if (firstError.length) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
        }
    }

    function validateSessionDates(silent = true) {
        clearErrors();
        syncSessionValues(false); // Sync without re-triggering validation
        syncSemesters(false);

        let isValid = true;
        const messages = new Set();
        const session = state.values.session;

        function addError($el, msg) {
            $el.addClass('is-invalid');
            isValid = false;
            if (msg && !silent) {
                messages.add(msg);
            }
        }

        // 1. Session Level Validation
        if (!session.name) {
            addError($('#session-name'), 'Session name is required');
        }

        if (!session.start_date) {
            addError($('#session-start-date'), 'Start date is required');
        }

        if (!session.end_date) {
            addError($('#session-end-date'), 'End date is required');
        }

        if (session.start_date && session.end_date) {
            const sessionStart = new Date(session.start_date);
            const sessionEnd = new Date(session.end_date);
            if (sessionStart >= sessionEnd) {
                addError($('#session-end-date'), 'Session end date must be greater than start date.');
            }
        }

        // 2. Semester Level Validation (if enabled)
        if (session.enable_semesters) {
            const semesters = session.semesters || [];
            if (!semesters.length && !silent) {
                messages.add('Please add at least one semester.');
                isValid = false;
            }

            const sessionStart = session.start_date ? new Date(session.start_date) : null;
            const sessionEnd = session.end_date ? new Date(session.end_date) : null;

            semesters.forEach((sem, idx) => {
                const semStartEl = $(`.semester-input[data-idx="${idx}"][data-key="start_date"]`);
                const semEndEl = $(`.semester-input[data-idx="${idx}"][data-key="end_date"]`);
                const semNameEl = $(`.semester-input[data-idx="${idx}"][data-key="name"]`);

                if (!sem.name) {
                    addError(semNameEl, 'Semester name is required');
                }

                if (!sem.start_date) {
                    addError(semStartEl, 'Start date is required');
                }

                if (!sem.end_date) {
                    addError(semEndEl, 'End date is required');
                }

                if (sem.start_date && sem.end_date) {
                    const currentStart = new Date(sem.start_date);
                    const currentEnd = new Date(sem.end_date);

                    // Internal Chronological Check
                    if (currentStart >= currentEnd) {
                        addError(semEndEl, 'Semester start date must be before end date.');
                    }

                    // Within Session Range Check
                    if (sessionStart && (currentStart < sessionStart || (sessionEnd && currentStart > sessionEnd))) {
                        addError(semStartEl, 'Semester dates must fall within the selected session year.');
                    }
                    if (sessionEnd && (currentEnd < sessionStart || currentEnd > sessionEnd)) {
                        addError(semEndEl, 'Semester dates must fall within the selected session year.');
                    }
                }
            });

            // Overlap & Gap Validation
            if (semesters.length > 1) {
                for (let i = 0; i < semesters.length; i++) {
                    for (let j = i + 1; j < semesters.length; j++) {
                        const semA = semesters[i];
                        const semB = semesters[j];

                        if (semA.start_date && semA.end_date && semB.start_date && semB.end_date) {
                            const startA = new Date(semA.start_date);
                            const endA = new Date(semA.end_date);
                            const startB = new Date(semB.start_date);
                            const endB = new Date(semB.end_date);

                            // Overlap logic: (StartA <= EndB) and (EndA >= StartB)
                            if (startA <= endB && endA >= startB) {
                                const semBStartEl = $(`.semester-input[data-idx="${j}"][data-key="start_date"]`);
                                addError(semBStartEl, `Semester date range overlaps with another semester.`);
                            }

                            // Gap Rule logic: B should start after A ends
                            if (j === i + 1 && startB <= endA && startB != "") {
                                const semBStartEl = $(`.semester-input[data-idx="${j}"][data-key="start_date"]`);
                                addError(semBStartEl, `Semester ${j + 1} start date must be after Semester ${i + 1} end date.`);
                            }
                        }
                    }
                }
            }
        }

        if (!silent) {
            messages.forEach(msg => showError(msg));
            if (!isValid) {
                scrollToFirstError();
            }
        }
        return isValid;
    }

    function renderMasterGrids() {
        renderSelectGrid(dom.mediumsGrid, config.masterData.mediums, 'medium_ids', 'id', 'name');
        renderSelectGrid(dom.sectionsGrid, config.masterData.sections, 'section_ids', 'id', 'name');
        renderSelectGrid(dom.streamsGrid, config.masterData.streams, 'stream_ids', 'id', 'name');
        renderSelectGrid(dom.classesGrid, config.masterData.classes, 'class_ids', 'id', 'name', onClassSelectionChanged);
        renderSelectGrid(dom.subjectsGrid, config.masterData.subjects, 'subject_ids', 'id', 'name');

        const boardOptions = ['<option value="">Select board (optional)</option>']
            .concat((config.masterData.boards || []).map(b => `<option value="${b.id}">${b.name}</option>`));
        $('#optional-board-id').html(boardOptions.join('')).addClass('session-input');
        $('#session-name,#session-start-date,#session-end-date,#enable-semesters').addClass('session-input');
    }

    function initDatePicker() {
        $('body').on('focus', ".datepicker-wizard", function () {
            $(this).datepicker({
                enableOnReadonly: false,
                todayHighlight: true,
                format: "dd-mm-yyyy",
                autoclose: true,
                orientation: "auto"
            });
        });
    }

    function parseDate(str) {
        if (!str) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
        const parts = str.split('-');
        if (parts.length === 3) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return str;
    }

    function formatDate(str) {
        if (!str) return '';
        const parts = str.split('-');
        if (parts.length === 3) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return str;
    }

    function renderSelectGrid($container, items, key, valueField, labelField, onChangeCb) {
        const html = (items || []).map(item => {
            const selected = state.values[key].includes(item[valueField]) ? 'selected' : '';
            return `<div class="select-chip ${selected}" data-list="${key}" data-id="${item[valueField]}">${item[labelField]}</div>`;
        }).join('');
        $container.html(html);

        $container.off('click').on('click', '.select-chip', function () {
            const list = $(this).data('list');
            const id = Number($(this).data('id'));
            toggleArraySelection(state.values[list], id);
            $(this).toggleClass('selected', state.values[list].includes(id));
            if (onChangeCb) {
                onChangeCb();
            }
        });
    }

    function onClassSelectionChanged() {
        Object.keys(state.values.mapping).forEach((mediumId) => {
            const classMap = state.values.mapping[mediumId] || {};
            Object.keys(classMap).forEach((classId) => {
                if (!state.values.class_ids.includes(Number(classId))) {
                    delete classMap[classId];
                }
            });
        });
    }

    function addSemesterRow() {
        state.values.session.semesters.push({ name: '', start_date: '', end_date: '' });
        renderSemesters();
    }

    function renderSemesters() {
        const enable = $('#enable-semesters').is(':checked');
        state.values.session.enable_semesters = enable;
        dom.semesterBlock.toggleClass('d-none', !enable);

        if (!enable) return;

        const html = state.values.session.semesters.map((semester, idx) => `
            <div class="row align-items-end mb-2">
                <div class="col-md-4 form-group mb-1">
                    <label>Semester Name</label>
                    <input type="text" class="form-control semester-input" data-idx="${idx}" data-key="name" value="${escapeAttr(semester.name || '')}">
                </div>
                <div class="col-md-3 form-group mb-1">
                    <label>Start Date</label>
                    <input type="text" class="form-control semester-input datepicker-wizard" data-idx="${idx}" data-key="start_date" value="${escapeAttr(formatDate(semester.start_date) || '')}" placeholder="dd-mm-yyyy" autocomplete="off">
                </div>
                <div class="col-md-3 form-group mb-1">
                    <label>End Date</label>
                    <input type="text" class="form-control semester-input datepicker-wizard" data-idx="${idx}" data-key="end_date" value="${escapeAttr(formatDate(semester.end_date) || '')}" placeholder="dd-mm-yyyy" autocomplete="off">
                </div>
                <div class="col-md-2 mb-1 d-flex justify-content-end">
                    <button type="button" class="btn-delete-icon remove-semester" data-idx="${idx}"><i class="mdi mdi-delete-outline"></i></button>
                </div>
            </div>
        `).join('');
        dom.semesterList.html(html);
    }

    function addShiftRow() {
        state.values.shifts.push({ name: '', start_time: '', end_time: '', status: 1 });
        renderShifts();
    }

    function renderShifts() {
        if (!state.values.shifts.length) {
            state.values.shifts.push({ name: 'Morning', start_time: '07:30', end_time: '13:00', status: 1 });
        }

        const html = state.values.shifts.map((shift, idx) => `
            <div class="shift-item">
                <div class="row align-items-end">
                    <div class="col-md-3 form-group mb-1">
                        <label>Shift Name</label>
                        <input type="text" class="form-control shift-input" data-idx="${idx}" data-key="name" value="${escapeAttr(shift.name || '')}">
                    </div>
                    <div class="col-md-3 form-group mb-1">
                        <label>Start Time</label>
                        <input type="time" class="form-control shift-input" data-idx="${idx}" data-key="start_time" value="${escapeAttr(shift.start_time || '')}">
                    </div>
                    <div class="col-md-3 form-group mb-1">
                        <label>End Time</label>
                        <input type="time" class="form-control shift-input" data-idx="${idx}" data-key="end_time" value="${escapeAttr(shift.end_time || '')}">
                    </div>
                    <div class="col-md-3 mb-1 d-flex justify-content-end">
                        <button type="button" class="btn-delete-icon remove-shift" data-idx="${idx}"><i class="mdi mdi-delete-outline"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
        dom.shiftsList.html(html);
    }

    function syncSessionValues(triggerValidation = true) {
        state.values.session.name = $('#session-name').val().trim();
        state.values.session.start_date = parseDate($('#session-start-date').val());
        state.values.session.end_date = parseDate($('#session-end-date').val());
        state.values.session.board_id = $('#optional-board-id').val() ? Number($('#optional-board-id').val()) : null;
        state.values.session.enable_semesters = $('#enable-semesters').is(':checked');
        if (!state.values.session.enable_semesters) {
            state.values.session.semesters = [];
        }
        renderSemesters();
        if (triggerValidation) {
            validateSessionDates(true);
        }
    }

    function syncSemesters(triggerValidation = true) {
        $('.semester-input').each(function () {
            const idx = Number($(this).data('idx'));
            const key = $(this).data('key');
            if (!state.values.session.semesters[idx]) {
                state.values.session.semesters[idx] = { name: '', start_date: '', end_date: '' };
            }
            let val = $(this).val();
            if (key === 'start_date' || key === 'end_date') {
                val = parseDate(val);
            }
            state.values.session.semesters[idx][key] = val;
        });

        if (triggerValidation) {
            validateSessionDates(true);
        }
    }

    function syncShifts() {
        $('.shift-input').each(function () {
            const idx = Number($(this).data('idx'));
            const key = $(this).data('key');
            if (!state.values.shifts[idx]) {
                state.values.shifts[idx] = { name: '', start_time: '', end_time: '', status: 1 };
            }
            state.values.shifts[idx][key] = $(this).val();
        });
    }

    function renderMappingTabs() {
        const classes = (config.masterData.classes || []).filter(c => state.values.class_ids.includes(c.id));
        if (!classes.length) {
            dom.classTabs.html('<p class="text-muted text-center mt-3">Select classes first.</p>');
            dom.mediumTabs.html('');
            dom.mappingSubjectGrid.html('');
            return;
        }

        if (!state.mappingClassId || !state.values.class_ids.includes(state.mappingClassId)) {
            state.mappingClassId = classes[0].id;
        }

        const classHtml = classes.map(cls => `
            <div class="mapping-class-tab ${state.mappingClassId === cls.id ? 'active' : ''}" data-class-id="${cls.id}">
                ${cls.name}
            </div>
        `).join('');
        dom.classTabs.html(classHtml);

        dom.classTabs.off('click').on('click', '.mapping-class-tab', function () {
            state.mappingClassId = Number($(this).data('class-id'));
            renderMappingTabs();
        });

        renderMappingMediumTabs();
    }

    function renderMappingMediumTabs() {
        const mediums = (config.masterData.mediums || []).filter(m => state.values.medium_ids.includes(m.id));
        if (!mediums.length) {
            dom.mediumTabs.html('<p class="text-muted">Select mediums first.</p>');
            dom.mappingSubjectGrid.html('');
            return;
        }

        if (!state.mappingMediumId || !state.values.medium_ids.includes(state.mappingMediumId)) {
            state.mappingMediumId = mediums[0].id;
        }

        const mediumHtml = mediums.map(medium => `
            <div class="mapping-medium-tab ${state.mappingMediumId === medium.id ? 'active' : ''}" data-medium-id="${medium.id}">
                ${medium.name}
            </div>
        `).join('');
        dom.mediumTabs.html(mediumHtml);

        dom.mediumTabs.off('click').on('click', '.mapping-medium-tab', function () {
            state.mappingMediumId = Number($(this).data('medium-id'));
            renderMappingMediumTabs();
        });

        renderMappingInfoBlocks();

        ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        renderMappingSubjectGrid();
    }

    function renderMappingInfoBlocks() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);

        const shifts = state.values.shifts || [];
        if (shifts.length) {
            $('#mapping-shifts-list').html(shifts.map(s => {
                const isSelected = mappingObj.shift_ids.includes(s.name);
                const icon = isSelected ? '<i class="mdi mdi-check"></i>' : '';
                return `<span class="info-tag ${isSelected ? 'selected' : ''}" data-shift-name="${s.name}">${icon}${s.name}</span>`;
            }).join(''));
        } else {
            $('#mapping-shifts-list').html('<span class="text-muted small">No shifts assigned to school</span>');
        }

        // Streams Cards with bound shift dropdowns
        const streams = (config.masterData.streams || []).filter(s => state.values.stream_ids.includes(s.id));
        if (streams.length) {
            const streamHtml = streams.map(s => {
                const isSelected = mappingObj.stream_ids.includes(s.id);
                let dropdownHtml = '';
                if (isSelected && mappingObj.shift_ids.length > 0) {
                    if (!mappingObj.stream_shifts) mappingObj.stream_shifts = {};

                    let options = `<option value="">Select Shift</option>`;
                    mappingObj.shift_ids.forEach(shift => {
                        const sel = (mappingObj.stream_shifts[s.id] === shift) ? 'selected' : '';
                        options += `<option value="${shift}" ${sel}>${shift}</option>`;
                    });
                    dropdownHtml = `
                        <span class="stream-shift-label">SHIFT:</span>
                        <select class="stream-shift-select stream-shift-select-box" data-stream-id="${s.id}">${options}</select>
                    `;
                } else if (isSelected) {
                    dropdownHtml = `<span class="text-muted small">Select assigned shift</span>`;
                }

                const checkboxIcon = isSelected ? 'mdi-checkbox-marked' : 'mdi-checkbox-blank-outline';

                return `
                    <div class="stream-card ${isSelected ? 'selected' : ''}" data-stream-id="${s.id}">
                        <div class="stream-card-left">
                            <div class="stream-checkbox-icon">
                                <i class="mdi ${checkboxIcon}"></i>
                            </div>
                            <div class="stream-card-title">${s.name}</div>
                        </div>
                        <div class="stream-card-right">
                            ${dropdownHtml}
                        </div>
                    </div>
                `;
            }).join('');
            $('#mapping-streams-list').html(streamHtml).removeClass('checkbox-list borderless d-inline-flex flex-wrap').css({ 'display': 'block', 'width': '100%' });
        } else {
            $('#mapping-streams-list').html('<span class="text-muted small">No streams selected globally</span>');
        }

        // Click handlers for shifts/streams
        $('#mapping-shifts-list').off('click').on('click', '.info-tag', function () {
            const shiftName = $(this).data('shift-name');
            toggleArraySelection(mappingObj.shift_ids, shiftName);
            renderMappingInfoBlocks();
        });

        $('#mapping-streams-list').off('click', '.stream-card').on('click', '.stream-card', function (e) {
            if ($(e.target).is('select') || $(e.target).closest('select').length) {
                return;
            }
            const streamId = Number($(this).data('stream-id'));
            toggleArraySelection(mappingObj.stream_ids, streamId);
            renderMappingInfoBlocks();
        });

        $('#mapping-streams-list').off('change', '.stream-shift-select').on('change', '.stream-shift-select', function () {
            const streamId = Number($(this).data('stream-id'));
            const shiftName = $(this).val();
            if (!mappingObj.stream_shifts) mappingObj.stream_shifts = {};

            if (shiftName) {
                mappingObj.stream_shifts[streamId] = shiftName;
            } else {
                delete mappingObj.stream_shifts[streamId];
            }
        });



        // Render Stream Isolation Tabs
        const streamTabsContainer = $('#mapping-stream-tabs-container');
        const streamTabsList = $('#mapping-stream-tabs');

        if (mappingObj.stream_ids.length > 0) {
            streamTabsContainer.show();
            if (!mappingObj.stream_ids.includes(state.mappingStreamId)) {
                state.mappingStreamId = mappingObj.stream_ids[0];
            }

            streamTabsList.html(mappingObj.stream_ids.map(streamId => {
                const sTabName = streams.find(s => s.id === streamId)?.name || 'Stream context';
                return `<div class="stream-nav-tab ${state.mappingStreamId === streamId ? 'active' : ''}" data-stream-id="${streamId}">${sTabName}</div>`;
            }).join(''));

            streamTabsList.off('click').on('click', '.stream-nav-tab', function () {
                state.mappingStreamId = Number($(this).data('stream-id'));
                renderMappingInfoBlocks();
            });

            const activeStreamName = streams.find(s => s.id === state.mappingStreamId)?.name || '';
            $('#context-stream-tag').text(activeStreamName.toUpperCase());
        } else {
            streamTabsContainer.hide();
            state.mappingStreamId = 0; // General fallback
            $('#context-stream-tag').text('CORE / GENERAL');
        }

        renderMappingStreamConfig();
    }

    function renderMappingStreamConfig() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        const semesterBlock = $('#mapping-semester-status');
        const semesterTabsBlock = $('#mapping-semester-tabs');

        // Ensure state is synced with Step 1 checkbox
        const globalEnable = $('#enable-semesters').is(':checked');
        state.values.session.enable_semesters = globalEnable;

        const showGlobalSemesters = globalEnable && (state.values.session.semesters || []).length > 0;

        if (showGlobalSemesters) {
            semesterBlock.removeClass('d-none').addClass('d-flex').show();
            const toggleIcon = $('#semester-toggle-icon');
            const toggleText = $('#semester-toggle-text');
            const toggleBtn = $('#stream-semester-toggle');

            if (streamConfig.enable_semesters) {
                semesterBlock.addClass('active');
                toggleBtn.addClass('active');
                toggleIcon.show();
                toggleText.text('SEMESTERS ACTIVE');

                semesterTabsBlock.removeClass('d-none').addClass('d-inline-flex').show();
                semesterTabsBlock.css('display', 'inline-flex');

                if (!state.mappingSemesterId || !state.values.session.semesters.find(s => s.name === state.mappingSemesterId)) {
                    state.mappingSemesterId = state.values.session.semesters[0].name;
                }

                semesterTabsBlock.html(state.values.session.semesters.map(sem => {
                    const isActive = sem.name === state.mappingSemesterId;
                    return `<div class="semester-tab ${isActive ? 'active' : ''}" data-sem-name="${sem.name}">${sem.name}</div>`;
                }).join(''));

                semesterTabsBlock.off('click').on('click', '.semester-tab', function () {
                    state.mappingSemesterId = $(this).data('sem-name');
                    renderMappingStreamConfig();
                });

                $('#context-semester-tag').removeClass('d-none').text(`SEMESTER \u2014 ${state.mappingSemesterId.toUpperCase()}`).show();
            } else {
                semesterBlock.removeClass('active');
                toggleBtn.removeClass('active');
                toggleIcon.hide();
                toggleText.text('SINGLE TERM SYSTEM');

                semesterTabsBlock.addClass('d-none').removeClass('d-inline-flex').hide();
                $('#context-semester-tag').addClass('d-none').hide();
            }

            toggleBtn.off('click').on('click', function () {
                streamConfig.enable_semesters = !streamConfig.enable_semesters;
                renderMappingStreamConfig();
            });

        } else {
            semesterBlock.addClass('d-none').removeClass('d-flex').hide();
            semesterTabsBlock.addClass('d-none').removeClass('d-inline-flex').hide();
            $('#context-semester-tag').addClass('d-none').hide();
            streamConfig.enable_semesters = false;
        }

        const mediumName = (config.masterData.mediums || []).find(m => m.id === state.mappingMediumId)?.name || '';
        $('#context-medium-tag').text(mediumName.toUpperCase());

        renderMappingSubjectGrid();
    }

    function refreshMappingState() {
        renderMappingSubjectGrid();
        // renderElectiveGroups is already called by renderMappingSubjectGrid
    }

    function renderMappingSubjectGrid() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        const subjects = (config.masterData.subjects || []).filter(s => state.values.subject_ids.includes(s.id));

        let activeSubjectsArray;
        const currentSemester = streamConfig.enable_semesters ? state.mappingSemesterId : null;
        if (streamConfig.enable_semesters) {
            if (!streamConfig.semesters[state.mappingSemesterId]) {
                streamConfig.semesters[state.mappingSemesterId] = [];
            }
            activeSubjectsArray = streamConfig.semesters[state.mappingSemesterId];
        } else {
            activeSubjectsArray = streamConfig.subjects;
        }

        const electiveSubjects = getContextElectiveSubjects(streamConfig, currentSemester);

        const html = subjects.map(subject => {
            const isElective = electiveSubjects.includes(subject.id);
            const selected = activeSubjectsArray.includes(subject.id) ? 'selected' : '';
            const conflictClass = isElective ? 'is-elective-assigned' : '';
            const title = isElective ? 'title="Already selected in Elective"' : '';

            return `
                <div class="subject-radio-item ${selected} ${conflictClass}" data-map-subject-id="${subject.id}" ${title}>
                    <span>${subject.name}</span>
                    <div class="radio-circle"></div>
                </div>
            `;
        }).join('');
        dom.mappingSubjectGrid.html(html);

        dom.mappingSubjectGrid.off('click').on('click', '.subject-radio-item', function () {
            const subjectId = Number($(this).data('map-subject-id'));

            // Re-fetch elective subjects in case of race condition or missing refresh
            const latestElectiveSubjects = getContextElectiveSubjects(streamConfig, currentSemester);
            if (latestElectiveSubjects.includes(subjectId)) {
                showError('This subject is already assigned as an Elective subject and cannot be added as Core.');
                return;
            }

            toggleArraySelection(activeSubjectsArray, subjectId);
            refreshMappingState();
        });

        renderElectiveGroups();
    }

    function getContextElectiveSubjects(streamConfig, semesterName) {
        if (!streamConfig || !streamConfig.elective_groups) return [];
        return streamConfig.elective_groups
            .filter(g => g.semester_name === semesterName)
            .reduce((acc, group) => {
                return acc.concat(group.subject_ids || []);
            }, []);
    }

    function ensureMappingObject(mediumId, classId) {
        if (!state.values.mapping[mediumId]) {
            state.values.mapping[mediumId] = {};
        }
        if (!state.values.mapping[mediumId][classId]) {
            state.values.mapping[mediumId][classId] = {
                shift_ids: [],
                stream_ids: [],
                stream_shifts: {},
                stream_configs: {}
            };
        }
        return state.values.mapping[mediumId][classId];
    }

    function ensureStreamConfig(mappingObj, streamId) {
        if (!mappingObj.stream_configs[streamId]) {
            mappingObj.stream_configs[streamId] = {
                enable_semesters: false,
                subjects: [],
                semesters: {},
                enable_elective_groups: false,
                elective_groups: []
            };
        }
        return mappingObj.stream_configs[streamId];
    }

    function checkMappingIntegrity() {
        const errors = [];
        const mediums = config.masterData.mediums || [];
        const classes = config.masterData.classes || [];
        const streams = config.masterData.streams || [];
        const allSubjects = config.masterData.subjects || [];

        Object.keys(state.values.mapping).forEach(mediumId => {
            const mObj = state.values.mapping[mediumId];
            Object.keys(mObj).forEach(classId => {
                const cObj = mObj[classId];
                Object.keys(cObj.stream_configs).forEach(streamId => {
                    const configObj = cObj.stream_configs[streamId];
                    if (!configObj) return;

                    const mediumName = mediums.find(m => m.id === parseInt(mediumId))?.name || 'Medium';
                    const className = classes.find(c => c.id === parseInt(classId))?.name || 'Class';
                    const streamName = streamId == 0 ? '' : (streams.find(s => s.id === parseInt(streamId))?.name || 'Stream');
                    const contextPrefix = `${className} ${streamName} (${mediumName})`;

                    if (configObj.enable_semesters) {
                        Object.keys(configObj.semesters).forEach(semName => {
                            const core = configObj.semesters[semName] || [];
                            const electives = [];
                            const electiveGroups = (configObj.elective_groups || []).filter(g => g.semester_name === semName);

                            if (configObj.enable_elective_groups) {
                                // Check Elective Group Size
                                electiveGroups.forEach((group, index) => {
                                    if ((group.subject_ids || []).length < 2) {
                                        errors.push(`${contextPrefix} Semester ${semName}: Elective Set ${index + 1} must have at least 2 subjects.`);
                                    }
                                });

                                // Check Cross-Elective Sets
                                const seenElectives = new Set();
                                electiveGroups.forEach(group => {
                                    (group.subject_ids || []).forEach(sid => {
                                        if (seenElectives.has(sid)) {
                                            const s = allSubjects.find(sub => sub.id === sid);
                                            errors.push(`${contextPrefix} Semester ${semName}: Subject '${s?.name || sid}' is assigned to multiple elective sets.`);
                                        }
                                        seenElectives.add(sid);
                                        electives.push(sid);
                                    });
                                });
                            }

                            // Check Core vs Elective
                            const coreIntersection = core.filter(sid => electives.includes(sid));
                            coreIntersection.forEach(sid => {
                                const s = allSubjects.find(sub => sub.id === sid);
                                errors.push(`${contextPrefix} Semester ${semName}: Subject '${s?.name || sid}' is assigned as both Core and Elective.`);
                            });
                        });
                    } else {
                        const core = configObj.subjects || [];
                        const electives = [];
                        const electiveGroups = (configObj.elective_groups || []).filter(g => !g.semester_name);

                        if (configObj.enable_elective_groups) {
                            // Check Elective Group Size
                            electiveGroups.forEach((group, index) => {
                                if ((group.subject_ids || []).length < 2) {
                                    errors.push(`${contextPrefix}: Elective Set ${index + 1} must have at least 2 subjects.`);
                                }
                            });

                            // Check Cross-Elective Sets
                            const seenElectives = new Set();
                            electiveGroups.forEach(group => {
                                (group.subject_ids || []).forEach(sid => {
                                    if (seenElectives.has(sid)) {
                                        const s = allSubjects.find(sub => sub.id === sid);
                                        errors.push(`${contextPrefix}: Subject '${s?.name || sid}' is assigned to multiple elective sets.`);
                                    }
                                    seenElectives.add(sid);
                                    electives.push(sid);
                                });
                            });
                        }

                        // Check Core vs Elective
                        const coreIntersection = core.filter(sid => electives.includes(sid));
                        coreIntersection.forEach(sid => {
                            const s = allSubjects.find(sub => sub.id === sid);
                            errors.push(`${contextPrefix}: Subject '${s?.name || sid}' is assigned as both Core and Elective.`);
                        });
                    }
                });
            });
        });
        return errors;
    }

    function hasValidMapping() {
        return state.values.medium_ids.every(mediumId => {
            return state.values.class_ids.every(classId => {
                const obj = state.values.mapping[mediumId]?.[classId];
                if (!obj || !obj.stream_configs) return false;

                const streamsToCheck = obj.stream_ids.length > 0 ? obj.stream_ids : [0];
                return streamsToCheck.every(streamId => {
                    const c = obj.stream_configs[streamId];
                    if (!c) return false;
                    if (c.enable_semesters) {
                        return Object.values(c.semesters).some(arr => arr.length > 0);
                    }
                    return c.subjects.length > 0;
                });
            });
        });
    }

    function collectPendingMappings() {
        const pending = [];
        const mediums = config.masterData.mediums || [];
        const classes = config.masterData.classes || [];
        const streams = config.masterData.streams || [];

        state.values.medium_ids.forEach(mediumId => {
            const mediumName = mediums.find(m => m.id === mediumId)?.name || `Medium #${mediumId}`;
            state.values.class_ids.forEach(classId => {
                const className = classes.find(c => c.id === classId)?.name || `Class #${classId}`;
                const obj = state.values.mapping[mediumId]?.[classId];
                if (!obj || !obj.stream_configs) {
                    pending.push(`${className} (${mediumName})`);
                    return;
                }
                const streamsToCheck = obj.stream_ids.length > 0 ? obj.stream_ids : [0];
                streamsToCheck.forEach(streamId => {
                    const c = obj.stream_configs[streamId];
                    let hasMapped = false;
                    if (c) {
                        if (c.enable_semesters) {
                            hasMapped = Object.values(c.semesters).some(arr => arr.length > 0);
                        } else {
                            hasMapped = c.subjects.length > 0;
                        }
                    }
                    if (!hasMapped) {
                        if (streamId > 0) {
                            const streamName = streams.find(s => s.id === streamId)?.name || `Stream #${streamId}`;
                            pending.push(`${className} – ${streamName} (${mediumName})`);
                        } else {
                            pending.push(`${className} (${mediumName})`);
                        }
                    }
                });
            });
        });
        return pending;
    }

    function renderSummary() {
        const summaryRows = [
            ['Session Year', state.values.session.name || '-'],
            ['Board (Optional)', (config.masterData.boards || []).find(b => b.id === state.values.session.board_id)?.name || 'Not selected'],
            ['Mediums', state.values.medium_ids.length],
            ['Sections', state.values.section_ids.length],
            ['Shifts', state.values.shifts.length],
            ['Streams', state.values.stream_ids.length],
            ['Classes', state.values.class_ids.length],
            ['Subjects', state.values.subject_ids.length]
        ];

        dom.summary.html(summaryRows.map(row => `<div class="d-flex justify-content-between border-bottom py-2"><strong>${row[0]}</strong><span>${row[1]}</span></div>`).join(''));
    }

    function submitSetup() {
        if (state.submitting) {
            return;
        }

        syncSessionValues();
        syncSemesters();
        syncShifts();

        state.values.shifts = (state.values.shifts || []).filter(s => s && s.name && s.start_time && s.end_time);
        state.values.session.semesters = (state.values.session.semesters || []).filter(s => s && s.name && s.start_date && s.end_date);

        // Mapping is optional — no blocking check needed, user confirmed via modal if incomplete.

        const payload = JSON.parse(JSON.stringify(state.values));
        state.submitting = true;
        dom.finalizeBtn.prop('disabled', true).addClass('d-none');
        dom.nextBtn.addClass('d-none');
        dom.prevBtn.addClass('d-none');
        dom.processingPanel.removeClass('d-none');

        $.ajax({
            url: config.routes.submit,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            beforeSend: function () { },
            headers: {
                'X-CSRF-TOKEN': config.csrf
            },
            success: function (response) {
                state.runId = response?.data?.run_id || state.runId;
                startLiveProgress();
            },
            error: function (xhr) {
                state.submitting = false;
                dom.finalizeBtn.prop('disabled', false).removeClass('d-none');
                dom.prevBtn.removeClass('d-none');
                const message = xhr?.responseJSON?.message || 'Failed to start setup.';
                showError(message);
            }
        });
    }

    function startLiveProgress() {
        renderProgressChecklist(config.progress || {});
        if (!state.runId) {
            startPolling();
            return;
        }
        startPusher();
        startPolling();
    }

    function startPusher() {
        if (!window.Pusher || !config.reverb || !config.reverb.key || !state.runId) {
            return;
        }

        try {
            const pusher = new Pusher(config.reverb.key, {
                wsHost: config.reverb.host,
                wsPort: Number(config.reverb.port || 80),
                wssPort: Number(config.reverb.port || 443),
                forceTLS: String(config.reverb.scheme).toLowerCase() === 'https',
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
            });

            const schoolId = Number(config.schoolId || 0);
            pusherChannel = pusher.subscribe(`academy-setup.${schoolId}.${state.runId}`);
            pusherChannel.bind('academy.setup.progress', function (eventData) {
                const progress = eventData?.progress || {};
                renderProgressChecklist(progress);
                handleProgressTerminalState(progress);
            });
        } catch (error) {
            console.error(error);
        }
    }

    function startPolling() {
        stopPolling();
        pollingTimer = setInterval(function () {
            if (!state.runId) {
                return;
            }
            $.get(`${config.routes.progress}/${state.runId}`, function (response) {
                const progress = response?.data?.progress || {};
                if (response?.data?.run_id) {
                    state.runId = response.data.run_id;
                }
                renderProgressChecklist(progress);
                handleProgressTerminalState(progress);
            });
        }, 3000);
    }

    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }

    function hydrateProgress(progress) {
        if (!progress || progress.status === 'pending') {
            return;
        }
        state.submitting = ['processing', 'completed'].includes(progress.status);
        if (state.submitting) {
            dom.processingPanel.removeClass('d-none');
            dom.finalizeBtn.addClass('d-none');
            renderProgressChecklist(progress);
            startLiveProgress();
        }
    }

    function renderProgressChecklist(progress) {
        if (!progress || !progress.checkpoints) {
            return;
        }
        const checkpoints = progress.checkpoints;
        const html = Object.keys(checkpoints).map((key) => {
            const item = checkpoints[key];
            const iconClass = item.status === 'done' ? 'done' : (item.status === 'failed' ? 'failed' : '');
            const icon = item.status === 'done' ? '<i class="mdi mdi-check text-white"></i>' : (item.status === 'failed' ? '<i class="mdi mdi-close text-white"></i>' : '');
            return `<li><span class="checkpoint-icon ${iconClass}">${icon}</span><span>${item.label}</span></li>`;
        }).join('');
        dom.processingList.html(html);
    }

    function handleProgressTerminalState(progress) {
        if (progress.status === 'completed') {
            stopPolling();
            setTimeout(() => {
                if (window.Pusher && typeof pusherChannel !== 'undefined' && pusherChannel && pusherChannel.pusher) {
                    pusherChannel.pusher.disconnect();
                }
                window.location.href = config.dashboardUrl;
            }, 1200);
        }
        if (progress.status === 'failed') {
            stopPolling();
            state.submitting = false;
            dom.finalizeBtn.prop('disabled', false).removeClass('d-none');
            dom.prevBtn.removeClass('d-none');
            showError(progress.message || 'Setup failed. Please retry.');
        }
    }

    // --- Elective Groups Functionality ---

    // Toggle Electives
    $('#btn-toggle-electives').on('click', function () {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        streamConfig.enable_elective_groups = !streamConfig.enable_elective_groups;
        refreshMappingState();
    });

    // Add New Group
    $('#btn-add-elective-group').on('click', function () {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        if (!streamConfig.elective_groups) streamConfig.elective_groups = [];

        const newGroup = {
            id: 'g_' + Date.now(),
            semester_name: streamConfig.enable_semesters ? state.mappingSemesterId : null,
            subject_ids: [],
            total_selectable_subjects: 1
        };

        streamConfig.elective_groups.push(newGroup);
        refreshMappingState();
    });

    // Add subject to group (from dropdown)
    $('#mapping-elective-groups-container').on('change', '.add-elective-subject-select', function () {
        const groupId = $(this).data('group-id');
        const subjectId = Number($(this).val());
        if (!subjectId) return;

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        const group = streamConfig.elective_groups.find(g => g.id === groupId);
        if (group && !group.subject_ids.includes(subjectId)) {
            group.subject_ids.push(subjectId);
        }
        refreshMappingState();
    });

    // Remove subject from group
    $('#mapping-elective-groups-container').on('click', '.btn-remove-subject', function () {
        const groupId = $(this).data('group-id');
        const subjectId = Number($(this).data('subject-id'));

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        const group = streamConfig.elective_groups.find(g => g.id === groupId);
        if (group) {
            const idx = group.subject_ids.indexOf(subjectId);
            if (idx > -1) group.subject_ids.splice(idx, 1);

            // Re-validate total_selectable_subjects bounds
            const maxSelectable = Math.max(1, group.subject_ids.length - 1);
            if (group.total_selectable_subjects > maxSelectable) {
                group.total_selectable_subjects = maxSelectable;
            }
        }
        refreshMappingState();
    });

    // Change total selectable subjects
    $('#mapping-elective-groups-container').on('change', '.total-selectable-input', function () {
        const groupId = $(this).data('group-id');
        let val = parseInt($(this).val()) || 1;

        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);
        const group = streamConfig.elective_groups.find(g => g.id === groupId);

        if (group) {
            const maxSelectable = Math.max(1, group.subject_ids.length - 1);
            if (val > maxSelectable) val = maxSelectable;
            if (val < 1) val = 1;
            group.total_selectable_subjects = val;
            $(this).val(val);
        }
    });

    // Delete group
    $('#mapping-elective-groups-container').on('click', '.btn-delete-elective-group', function () {
        const groupId = $(this).data('group-id');
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        streamConfig.elective_groups = streamConfig.elective_groups.filter(g => g.id !== groupId);
        refreshMappingState();
    });

    function renderElectiveGroups() {
        const mappingObj = ensureMappingObject(state.mappingMediumId, state.mappingClassId);
        const streamConfig = ensureStreamConfig(mappingObj, state.mappingStreamId);

        const toggleBtn = $('#btn-toggle-electives');
        const bodyBlock = $('#elective-groups-body');
        const listBlock = $('#elective-groups-list');

        if (!streamConfig.enable_elective_groups) {
            toggleBtn.text('+ ENABLE ELECTIVES').removeClass('btn-primary text-white').addClass('btn-outline-primary');
            bodyBlock.hide();
            return;
        }

        toggleBtn.text('DISABLE ELECTIVES').removeClass('btn-outline-primary').addClass('btn-primary text-white');
        bodyBlock.show();

        const currentSemester = streamConfig.enable_semesters ? state.mappingSemesterId : null;
        let groups = streamConfig.elective_groups || [];

        // Filter groups for current context
        const contextGroups = groups.filter(g => g.semester_name === currentSemester);

        if (!contextGroups.length) {
            listBlock.html('<div class="text-muted text-center py-4 font-italic">No elective sets configured yet. Click "+ NEW GROUP" to begin.</div>');
            return;
        }

        const allSubjects = config.masterData.subjects || [];

        const html = contextGroups.map((group, index) => {
            // Get already mapped subject IDs in THIS group
            const mappedIds = group.subject_ids || [];

            // Get ALL elective subjects in the context to find those in OTHER groups
            const allElectiveContextSubjects = getContextElectiveSubjects(streamConfig, currentSemester);
            const subjectsInOtherGroups = allElectiveContextSubjects.filter(id => !mappedIds.includes(id));

            // Get Core subjects in current context to exclude them
            const coreSubjects = streamConfig.enable_semesters
                ? (streamConfig.semesters[state.mappingSemesterId] || [])
                : (streamConfig.subjects || []);

            // Build pills
            const pillsHtml = mappedIds.map((subId, pillIndex) => {
                const subObj = allSubjects.find(s => s.id === subId);
                const subName = subObj ? subObj.name : 'Unknown';
                const pill = `
                    <div class="elective-subject-pill">
                        ${subName}
                        <i class="mdi mdi-delete-outline btn-remove-subject" data-group-id="${group.id}" data-subject-id="${subId}"></i>
                    </div>
                `;
                return pillIndex === 0 ? pill : `<span class="elective-or-divider">OR</span> ${pill}`;
            }).join('');

            // Build Dropdown
            let optionsHtml = `<option value="">+ Add Subject</option>`;
            allSubjects.forEach(s => {
                const isCore = coreSubjects.includes(s.id);
                const isSelectedInGroup = mappedIds.includes(s.id);
                const isSelectedInOtherGroup = subjectsInOtherGroups.includes(s.id);

                if (state.values.subject_ids.includes(s.id)) {
                    // Hide if Core or in another Elective set
                    if (!isCore && !isSelectedInGroup && !isSelectedInOtherGroup) {
                        optionsHtml += `<option value="${s.id}">${s.name}</option>`;
                    }
                }
            });

            const maxSelectable = Math.max(1, mappedIds.length - 1);
            let totalSelectable = group.total_selectable_subjects || 1;
            if (totalSelectable > maxSelectable) totalSelectable = maxSelectable;

            return `
                <div class="elective-group-card">
                    <div class="elective-group-header">
                        <div class="elective-group-title">Elective Set ${index + 1}</div>
                        <i class="mdi mdi-delete-outline btn-delete-elective-group" data-group-id="${group.id}"></i>
                    </div>
                    
                    <div class="elective-group-body">
                        ${pillsHtml}
                        <div class="add-elective-subject-wrapper">
                            <select class="add-elective-subject-select" data-group-id="${group.id}">
                                ${optionsHtml}
                            </select>
                            <i class="mdi mdi-chevron-down add-elective-dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="elective-group-footer">
                        <div class="w-100">
                            <label class="d-block mb-2">TOTAL SUBJECTS STUDENT MUST SELECT <i class="mdi mdi-information-outline text-muted" title="Maximum selectable is Total Subjects - 1"></i></label>
                            <input type="number" class="total-selectable-input form-control w-100" data-group-id="${group.id}" min="1" max="${maxSelectable}" value="${totalSelectable}" style="font-weight: 800; color: var(--theme-color); height: 42px;">
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        listBlock.html(html);
    }

    function toggleArraySelection(arrayRef, id) {
        const idx = arrayRef.indexOf(id);
        if (idx >= 0) {
            arrayRef.splice(idx, 1);
        } else {
            arrayRef.push(id);
        }
    }

    function showError(message) {
        $.toast({
            text: message,
            showHideTransition: 'slide',
            icon: 'error',
            loaderBg: '#f2a654',
            position: 'top-right',
            hideAfter: 5000,
        });
    }

    function escapeAttr(value) {
        return String(value || '').replace(/"/g, '&quot;');
    }
})();
