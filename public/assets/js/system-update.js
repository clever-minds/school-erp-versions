/**
 * System Update Module — Client-Side Logic
 * Real-time progress tracking via Pusher/Reverb WebSocket
 * Server-side pagination for school tracks
 */
(function () {
    'use strict';

    const CFG = window.systemUpdateConfig || {};
    const CSRF = CFG.csrf;
    const ROUTES = CFG.routes || {};
    const REVERB = CFG.reverb || {};
    const STEPS = ['extracting', 'main_migration', 'tenant_migration', 'cache_optimization'];

    let pusherClient = null;
    let currentRunId = CFG.currentRunId || null;

    // ── Pagination State ──
    let paginationState = {
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
        search: '',
        status: 'all',
    };
    let searchDebounceTimer = null;

    // ────────────────────────────────────────────
    // Initialization
    // ────────────────────────────────────────────
    function init() {
        bindEvents();
        if (currentRunId && CFG.runStatus && !['completed', 'failed'].includes(CFG.runStatus)) {
            connectWebSocket(currentRunId);
            setDeployingState(true);
        }
        if (CFG.runStatus) {
            updateStepperFromStatus(CFG.runStatus);
            if (CFG.runProgress) updateGlobalProgress(CFG.runProgress, CFG.runStage || '');
        }
        // Load initial school tracks via AJAX
        if (currentRunId) {
            fetchSchoolTracks(1);
        } else {
            renderEmptyState();
        }
        renderLogEntries(CFG.logEntries || []);
    }

    function bindEvents() {
        // Deploy button
        const deployBtn = document.getElementById('su-deploy-btn');
        if (deployBtn) deployBtn.addEventListener('click', handleDeploy);

        // Maintenance toggle
        const maintBtn = document.getElementById('su-maintenance-btn');
        if (maintBtn) maintBtn.addEventListener('click', handleMaintenanceToggle);

        // Post-update tasks
        document.querySelectorAll('.su-task-btn').forEach(btn => {
            // btn.addEventListener('click', function () { handlePostTask(this); });
        });

        // School search (debounced, server-side)
        const searchInput = document.getElementById('su-school-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function () {
                    paginationState.search = searchInput.value.trim();
                    paginationState.currentPage = 1;
                    fetchSchoolTracks(1);
                }, 400);
            });
        }

        // Status filter
        const statusFilter = document.getElementById('su-status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
                paginationState.status = this.value;
                paginationState.currentPage = 1;
                fetchSchoolTracks(1);
            });
        }
    }

    // ────────────────────────────────────────────
    // Server-Side Pagination — Fetch Tracks
    // ────────────────────────────────────────────
    function fetchSchoolTracks(page) {
        if (!currentRunId) {
            renderEmptyState();
            return;
        }

        page = page || paginationState.currentPage;
        const tbody = document.getElementById('su-school-tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;"><span class="su-spinner" style="display:inline-block;margin-right:8px;"></span> Loading...</td></tr>';
        }

        const params = new URLSearchParams({
            run_id: currentRunId,
            page: page,
            per_page: paginationState.perPage,
        });

        if (paginationState.search) {
            params.append('search', paginationState.search);
        }
        if (paginationState.status && paginationState.status !== 'all') {
            params.append('status', paginationState.status);
        }

        $.ajax({
            url: ROUTES.schoolTracks + '?' + params.toString(),
            type: 'GET',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (response) {
                if (!response.error) {
                    const schools = response.data || [];
                    const pagination = response.pagination || {};

                    paginationState.currentPage = pagination.current_page || 1;
                    paginationState.lastPage = pagination.last_page || 1;
                    paginationState.total = pagination.total || 0;

                    renderSchoolTable(schools, pagination.from || 0);
                    renderPagination(pagination);
                } else {
                    renderEmptyState(response.message || 'Failed to load data.');
                }
            },
            error: function () {
                renderEmptyState('Failed to load school data.');
            }
        });
    }

    function renderEmptyState(message) {
        const tbody = document.getElementById('su-school-tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">' + escHtml(message || 'No deployment data yet. Initialize a deployment above.') + '</td></tr>';
        }
        const paginationEl = document.getElementById('su-pagination');
        if (paginationEl) paginationEl.innerHTML = '';
    }

    // ────────────────────────────────────────────
    // Deploy Handler
    // ────────────────────────────────────────────
    function handleDeploy(e) {
        e.preventDefault();
        const form = document.getElementById('su-update-form');
        const purchaseCode = document.getElementById('su-purchase-code');
        const fileInput = document.getElementById('su-file-input');

        if (!purchaseCode || !purchaseCode.value.trim()) {
            showToast('Please enter your purchase license code.', 'error');
            return;
        }
        if (!fileInput || !fileInput.files.length) {
            showToast('Please select a zip package file.', 'error');
            return;
        }

        Swal.fire({
            title: 'Initialize Deployment?',
            text: 'This will start the system update process. Make sure maintenance mode is enabled and backups are completed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Yes, Deploy Now',
        }).then((result) => {
            if (result.isConfirmed) {
                submitUpdate(form);
            }
        });
    }

    function submitUpdate(form) {
        const formData = new FormData(form);
        const btn = document.getElementById('su-deploy-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="su-spinner"></span> Initializing...';

        $.ajax({
            url: ROUTES.update,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (response) {
                if (!response.error && response.data && response.data.run_id) {
                    currentRunId = response.data.run_id;
                    showToast('Deployment initiated successfully!', 'success');
                    connectWebSocket(currentRunId);
                    setDeployingState(true);
                    addLogEntry('Deployment initiated. Run ID: ' + currentRunId);
                    // Fetch initial tracks
                    fetchSchoolTracks(1);
                } else {
                    showToast(response.message || 'Failed to start update.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-rocket"></i> Initialize Deployment <i class="fa fa-arrow-right"></i>';
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred.';
                showToast(msg, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rocket"></i> Initialize Deployment <i class="fa fa-arrow-right"></i>';
            }
        });
    }

    // ────────────────────────────────────────────
    // WebSocket Connection
    // ────────────────────────────────────────────
    function connectWebSocket(runId) {
        if (pusherClient) { try { pusherClient.disconnect(); } catch (e) { } }

        const pageIsHttps = window.location.protocol === 'https:';

        let wsHost, wsPort, wssPort, isSecure;

        if (pageIsHttps) {
            // Production: Reverb proxied through same domain on 443. Ignore misconfigured REVERB host/port from backend.
            wsHost = window.location.hostname;
            wssPort = 443;
            wsPort = 443;
            isSecure = true;
        } else {
            wsHost = (REVERB.host || window.location.hostname)
                .replace(/^(https?:\/\/|https?\/\/)/i, '')
                .replace(/\/+$/, '');
            isSecure = (REVERB.scheme || 'http') === 'https';
            wsPort = REVERB.port || 80;
            wssPort = REVERB.port || 443;
        }

        pusherClient = new Pusher(REVERB.key, {
            wsHost: wsHost,
            wsPort: wsPort,
            wssPort: wssPort,
            forceTLS: isSecure,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            cluster: '',
        });

        const channel = pusherClient.subscribe('system-update.' + runId);

        channel.bind('system.update.progress', function (payload) {
            if (payload.type === 'global') {
                handleGlobalProgress(payload.data);
            } else if (payload.type === 'school') {
                handleSchoolProgress(payload.data);
            }
        });

        // Update live badge
        const liveBadge = document.getElementById('su-live-badge');
        if (liveBadge) liveBadge.classList.remove('d-none');
    }

    // ────────────────────────────────────────────
    // Global Progress Handler
    // ────────────────────────────────────────────
    function handleGlobalProgress(data) {
        updateStepperFromStatus(data.status);
        updateGlobalProgress(data.progress, data.stage || '');

        if (data.log_entry) addLogEntry(data.log_entry);

        // Update school counters
        const counterEl = document.getElementById('su-school-counter');
        if (counterEl && data.total_schools) {
            counterEl.textContent = 'Managing migration status for ' + data.total_schools + ' total schools';
        }

        if (data.status === 'completed') {
            setDeployingState(false);
            showToast('System update completed successfully!', 'success');
            // Refresh school data
            setTimeout(function () { fetchSchoolTracks(paginationState.currentPage); }, 500);
        } else if (data.status === 'failed') {
            setDeployingState(false);
            showToast('System update failed. Check logs for details.', 'error');
        }
    }

    function updateStepperFromStatus(status) {
        const stepIndex = STEPS.indexOf(status);
        const stepEls = document.querySelectorAll('.su-step');
        const lineFill = document.getElementById('su-stepper-line-fill');

        stepEls.forEach((el, i) => {
            el.classList.remove('active', 'completed');
            if (i < stepIndex) el.classList.add('completed');
            else if (i === stepIndex) el.classList.add('active');
        });

        if (status === 'completed') {
            stepEls.forEach(el => el.classList.add('completed'));
            if (lineFill) lineFill.style.width = '100%';
        } else if (lineFill && stepIndex >= 0) {
            lineFill.style.width = ((stepIndex) / (STEPS.length - 1) * 100) + '%';
        }
    }

    function updateGlobalProgress(percent, stage) {
        const valueEl = document.getElementById('su-progress-value');
        const stageEl = document.getElementById('su-progress-stage');
        const barFill = document.getElementById('su-progress-bar-fill');
        const wrapper = document.getElementById('su-global-progress');

        if (wrapper) wrapper.style.display = 'block';
        if (valueEl) valueEl.textContent = percent + '%';
        if (stageEl) stageEl.textContent = stage ? 'Stage: ' + stage : '';
        if (barFill) {
            barFill.style.width = percent + '%';
            barFill.classList.toggle('completed', percent >= 100);
        }
    }

    // ────────────────────────────────────────────
    // School Progress Handler (WebSocket — live updates)
    // ────────────────────────────────────────────
    function handleSchoolProgress(data) {
        // Try to update the row in-place if visible on current page
        const row = document.getElementById('su-school-' + data.school_id);
        if (row) {
            const progressFill = row.querySelector('.su-school-progress-fill');
            const progressText = row.querySelector('.su-school-progress-text');
            const statusBadge = row.querySelector('.su-status-badge');
            const actionCell = row.querySelector('.school-action');

            if (progressFill) {
                progressFill.style.width = data.progress + '%';
                progressFill.className = 'su-school-progress-fill';
                if (data.status === 'completed') progressFill.classList.add('completed');
                if (data.status === 'failed') progressFill.classList.add('failed');
            }
            if (progressText) progressText.textContent = data.progress + '%';
            if (statusBadge) {
                const label = getStatusLabel(data.status);
                statusBadge.className = 'su-status-badge ' + data.status;
                statusBadge.textContent = label;
            }
            if (actionCell) {
                if (data.status === 'failed') {
                    actionCell.innerHTML = '<button class="su-retry-btn" onclick="window.SystemUpdate.retrySchool(' + data.school_id + ')" title="' + (data.error_message || '') + '"><i class="fa fa-refresh"></i> Retry</button>';
                } else {
                    actionCell.innerHTML = '';
                }
            }

            // Update error message cell
            const errorCell = row.querySelector('.error-message');
            if (errorCell) {
                errorCell.textContent = (data.failed_step || '') + (data.error_message ? ' # ' + data.error_message : '');
            }
        } else {
            // If row is not found, dynamically append it to support real-time population
            const tbody = document.getElementById('su-school-tbody');
            if (!tbody) return;

            // Respect current active search/status filters
            if (paginationState.search && data.school_name && !data.school_name.toLowerCase().includes(paginationState.search.toLowerCase())) return;
            if (paginationState.status && paginationState.status !== 'all' && paginationState.status !== data.status) return;

            // Clear empty/loading placeholders
            if (tbody.innerHTML.includes('su-spinner') || tbody.innerHTML.includes('No deployment') || tbody.innerHTML.includes('No schools found')) {
                tbody.innerHTML = '';
                paginationState.total = 0;
            }

            const currentRows = tbody.querySelectorAll('tr').length;
            paginationState.total++;

            // Only append to DOM if we are on the first page and haven't exceeded the perPage limit
            if (paginationState.currentPage === 1 && currentRows < paginationState.perPage) {
                const statusLabel = getStatusLabel(data.status);
                const statusClass = data.status || 'queued';
                const progressClass = data.status === 'completed' ? 'completed' : (data.status === 'failed' ? 'failed' : '');
                const retryBtn = data.status === 'failed'
                    ? '<button class="su-retry-btn" onclick="window.SystemUpdate.retrySchool(' + data.school_id + ')" title="' + (data.error_message || '').replace(/"/g, '&quot;') + '"><i class="fa fa-refresh"></i> Retry</button>'
                    : '';

                const tr = document.createElement('tr');
                tr.id = 'su-school-' + data.school_id;
                tr.className = 'su-fade-in';
                tr.setAttribute('data-name', (data.school_name || '').toLowerCase());

                const startIndex = currentRows + 1;

                tr.innerHTML = '<td class="school-index">' + startIndex + '</td>'
                    + '<td class="school-name">' + escHtml(data.school_name) + '</td>'
                    + '<td><span class="error-message">' + escHtml(data.failed_step || '') + (data.error_message ? ' # ' + escHtml(data.error_message) : '') + '</span></td>'
                    + '<td><div class="su-school-progress-bar"><div class="su-school-progress-fill ' + progressClass + '" style="width:' + (data.progress || 0) + '%"></div></div><span class="su-school-progress-text">' + (data.progress || 0) + '%</span></td>'
                    + '<td><span class="su-status-badge ' + statusClass + '">' + statusLabel + '</span></td>'
                    + '<td class="school-action">' + retryBtn + '</td>';

                tbody.appendChild(tr);
            }

            // Dynamically recalculate and render pagination
            paginationState.lastPage = Math.ceil(paginationState.total / paginationState.perPage) || 1;
            renderPagination({
                current_page: paginationState.currentPage,
                last_page: paginationState.lastPage,
                total: paginationState.total,
                from: 1,
                to: Math.min(paginationState.total, paginationState.currentPage * paginationState.perPage)
            });
        }
    }

    function getStatusLabel(status) {
        const map = {
            'queued': 'QUEUED', 'migrating': 'UPDATING', 'seeding': 'UPDATING',
            'completed': 'COMPLETED', 'failed': 'FAILED'
        };
        return map[status] || status.toUpperCase();
    }

    // ────────────────────────────────────────────
    // School Table Rendering
    // ────────────────────────────────────────────
    function renderSchoolTable(schools, startIndex) {
        const tbody = document.getElementById('su-school-tbody');
        if (!tbody) return;

        if (!schools || schools.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">No schools found matching the criteria.</td></tr>';
            return;
        }

        startIndex = startIndex || 1;

        tbody.innerHTML = schools.map((s, i) => {
            const statusLabel = getStatusLabel(s.status);
            const statusClass = s.status || 'queued';
            const progressClass = s.status === 'completed' ? 'completed' : (s.status === 'failed' ? 'failed' : '');
            const retryBtn = s.status === 'failed'
                ? '<button class="su-retry-btn" onclick="window.SystemUpdate.retrySchool(' + s.school_id + ')" title="' + (s.error_message || '').replace(/"/g, '&quot;') + '"><i class="fa fa-refresh"></i> Retry</button>'
                : '';

            return '<tr id="su-school-' + s.school_id + '" class="su-fade-in" data-name="' + (s.school_name || '').toLowerCase() + '">'
                + '<td class="school-index">' + (startIndex + i) + '</td>'
                + '<td class="school-name">' + escHtml(s.school_name) + '</td>'
                + '<td><span class="error-message">' + escHtml(s.failed_step || '') + (s.error_message ? ' # ' + escHtml(s.error_message) : '') + '</span></td>'
                + '<td><div class="su-school-progress-bar"><div class="su-school-progress-fill ' + progressClass + '" style="width:' + (s.progress || 0) + '%"></div></div><span class="su-school-progress-text">' + (s.progress || 0) + '%</span></td>'
                + '<td><span class="su-status-badge ' + statusClass + '">' + statusLabel + '</span></td>'
                + '<td class="school-action">' + retryBtn + '</td>'
                + '</tr>';
        }).join('');
    }

    // ────────────────────────────────────────────
    // Pagination Rendering
    // ────────────────────────────────────────────
    function renderPagination(pagination) {
        const container = document.getElementById('su-pagination');
        if (!container) return;

        const { current_page, last_page, total, from, to } = pagination;

        if (last_page <= 1) {
            // Show only info when there's a single page
            container.innerHTML = total > 0
                ? '<div class="su-pagination-info">Showing ' + from + '–' + to + ' of ' + total + ' schools</div>'
                : '';
            return;
        }

        let html = '<div class="su-pagination-info">Showing ' + from + '–' + to + ' of ' + total + ' schools</div>';
        html += '<div class="su-pagination-controls">';

        // Previous
        html += '<button class="su-page-btn" ' + (current_page <= 1 ? 'disabled' : '') + ' data-page="' + (current_page - 1) + '">'
            + '<i class="fa fa-chevron-left"></i></button>';

        // Page numbers
        const pages = buildPageNumbers(current_page, last_page);
        pages.forEach(function (p) {
            if (p === '...') {
                html += '<span class="su-page-ellipsis">…</span>';
            } else {
                html += '<button class="su-page-btn ' + (p === current_page ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        // Next
        html += '<button class="su-page-btn" ' + (current_page >= last_page ? 'disabled' : '') + ' data-page="' + (current_page + 1) + '">'
            + '<i class="fa fa-chevron-right"></i></button>';

        html += '</div>';
        container.innerHTML = html;

        // Bind page click events
        container.querySelectorAll('.su-page-btn:not([disabled])').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const page = parseInt(this.dataset.page);
                if (page && page !== paginationState.currentPage) {
                    fetchSchoolTracks(page);
                }
            });
        });
    }

    function buildPageNumbers(current, last) {
        const delta = 2;
        const pages = [];
        const rangeStart = Math.max(2, current - delta);
        const rangeEnd = Math.min(last - 1, current + delta);

        pages.push(1);
        if (rangeStart > 2) pages.push('...');
        for (let i = rangeStart; i <= rangeEnd; i++) {
            pages.push(i);
        }
        if (rangeEnd < last - 1) pages.push('...');
        if (last > 1) pages.push(last);

        return pages;
    }

    // ────────────────────────────────────────────
    // Log Console
    // ────────────────────────────────────────────
    function addLogEntry(text) {
        const logBody = document.getElementById('su-log-body');
        if (!logBody) return;

        const entry = document.createElement('div');
        entry.className = 'su-log-entry';

        let cssClass = 'msg-info';
        if (text.includes('FATAL') || text.includes('FAILED') || text.includes('Error')) cssClass = 'msg-error';
        else if (text.includes('completed') || text.includes('success') || text.includes('Ok.')) cssClass = 'msg-success';
        else if (text.includes('Warning') || text.includes('WARN')) cssClass = 'msg-warn';

        const hasTimestamp = /^\[[\d:]+\]/.test(text);
        if (hasTimestamp) {
            entry.innerHTML = '<span class="time">' + text.substring(0, text.indexOf(']') + 1) + '</span> <span class="' + cssClass + '">' + escHtml(text.substring(text.indexOf(']') + 2)) + '</span>';
        } else {
            const now = new Date().toLocaleTimeString('en-US', { hour12: false });
            entry.innerHTML = '<span class="time">[' + now + ']</span> <span class="' + cssClass + '">' + escHtml(text) + '</span>';
        }

        logBody.appendChild(entry);
        logBody.scrollTop = logBody.scrollHeight;

        // Show console if hidden
        const consoleEl = document.getElementById('su-log-console');
        if (consoleEl) consoleEl.style.display = 'block';
    }

    function renderLogEntries(entries) {
        if (!entries || entries.length === 0) return;
        entries.forEach(e => {
            addLogEntry('[' + (e.time || '') + '] ' + (e.message || ''));
        });
    }

    // ────────────────────────────────────────────
    // Maintenance Mode Toggle
    // ────────────────────────────────────────────
    function handleMaintenanceToggle() {
        const btn = document.getElementById('su-maintenance-btn');
        const isActive = btn.classList.contains('active');
        const newState = !isActive;

        $.ajax({
            url: ROUTES.toggleMaintenance,
            type: 'POST',
            data: { enable: newState ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (response) {
                if (!response.error) {
                    btn.classList.toggle('active', newState);
                    btn.innerHTML = newState
                        ? '<i class="fa fa-lock"></i> Maintenance Mode Active'
                        : '<i class="fa fa-shield"></i> Enable Maintenance Mode';
                    showToast(response.message, 'success');

                    // Update alert banner
                    const banner = document.getElementById('su-alert-banner');
                    if (banner) banner.classList.toggle('hidden', newState);

                    // Update pre-check
                    const checkIcon = document.getElementById('su-check-maintenance');
                    if (checkIcon) {
                        checkIcon.className = 'check-icon ' + (newState ? 'passed' : 'pending');
                        checkIcon.innerHTML = newState ? '<i class="fa fa-check"></i>' : '<i class="fa fa-circle-o"></i>';
                    }
                } else {
                    showToast(response.message || 'Error toggling maintenance mode.', 'error');
                }
            },
            error: function () { showToast('Failed to toggle maintenance mode.', 'error'); }
        });
    }

    // ────────────────────────────────────────────
    // Post-Update Tasks
    // ────────────────────────────────────────────
    function handlePostTask(btn) {
        const task = btn.dataset.task;
        if (!task || btn.disabled) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="su-spinner" style="border-color:rgba(0,0,0,0.2);border-top-color:var(--su-primary);width:14px;height:14px;"></span> Running...';

        $.ajax({
            url: ROUTES.postTask,
            type: 'POST',
            data: { task: task },
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (response) {
                if (!response.error) {
                    btn.classList.add('done');
                    btn.innerHTML = '<i class="fa fa-check-circle task-icon" style="color:var(--su-green)"></i> ' + btn.dataset.label + ' <span style="margin-left:auto;font-size:0.72rem;color:var(--su-green)">Done</span>';
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message || 'Task failed.', 'error');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            },
            error: function () {
                showToast('Failed to execute task.', 'error');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }

    // ────────────────────────────────────────────
    // Retry School
    // ────────────────────────────────────────────
    function retrySchool(schoolId) {
        if (!currentRunId) return;

        Swal.fire({
            title: 'Retry School Update?',
            text: 'This will re-run migration and seeder for this school.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Retry',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ROUTES.retrySchool,
                    type: 'POST',
                    data: { run_id: currentRunId, school_id: schoolId },
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: function (response) {
                        if (!response.error) {
                            showToast('Retry initiated.', 'success');
                        } else {
                            showToast(response.message || 'Retry failed.', 'error');
                        }
                    },
                    error: function () { showToast('Failed to retry.', 'error'); }
                });
            }
        });
    }

    // ────────────────────────────────────────────
    // UI Helpers
    // ────────────────────────────────────────────
    function setDeployingState(deploying) {
        const deployBtn = document.getElementById('su-deploy-btn');
        if (deploying) {
            if (deployBtn) { deployBtn.disabled = true; deployBtn.innerHTML = '<span class="su-spinner"></span> Deployment in progress...'; }
        } else {
            if (deployBtn) {
                deployBtn.disabled = false;
                deployBtn.innerHTML = '<i class="fa fa-rocket"></i> Initialize Deployment <i class="fa fa-arrow-right"></i>';
            }
        }
    }

    function showToast(msg, type) {
        if (typeof $.toast === 'function') {
            $.toast({ text: msg, icon: type === 'error' ? 'error' : 'success', showHideTransition: 'slide', position: 'top-right', loaderBg: type === 'error' ? '#f2a654' : '#f96868' });
        }
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // ── Public API ──
    window.SystemUpdate = { retrySchool: retrySchool };

    // Boot
    document.addEventListener('DOMContentLoaded', init);
})();
