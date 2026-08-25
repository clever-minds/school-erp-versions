/**
 * school-provision.js
 * Real-time provisioning progress for the Manage Schools page.
 * Subscribes to school.{id} channels via Reverb/Pusher for each
 * school that is currently in provisioning state (installed == 0).
 */
(function () {
    'use strict';

    // ── Configuration injected by the Blade template ──────────────────────
    const CFG = window.schoolProvisionConfig || {};
    const REVERB = CFG.reverb || {};

    /** Map of schoolId → { progress, stepLabel, status } for installing schools */
    let installingSchools = CFG.installingSchools || {};

    let pusherClient = null;
    let activeModalSchoolId = null;

    // ── Provisioning step order (maps to the modal checklist) ─────────────
    const STEP_ORDER = [
        { key: 'job_started', label: 'Starting Setup' },
        { key: 'database_created', label: 'Database Created' },
        { key: 'migrations_completed', label: 'Migrating Tables' },
        { key: 'seeders_executed', label: 'Configuring Defaults' },
        { key: 'school_activated', label: 'Activating School' },
        { key: 'welcome_email_sent', label: 'Sending Welcome Email' },
        { key: 'verification_email_sent', label: 'Sending Verification' },
        { key: 'provisioning_completed', label: 'Setup Complete' },
    ];

    // ──────────────────────────────────────────────────────────────────────
    // Boot
    // ──────────────────────────────────────────────────────────────────────
    function init() {
        // Initial subscription for schools already known from server-side
        if (Object.keys(installingSchools).length > 0) {
            connectReverb();
        }

        // Auto-subscribe to any installing schools found in the table after render (AJAX loads/pagination)
        $(document).on('post-body.bs.table', '#table_list', function () {
            $('.school-installing-cell').each(function () {
                const id = parseInt($(this).attr('id').replace('provision-cell-', ''));
                if (id) {
                    // Register in local list if not there
                    if (!installingSchools[id]) {
                        installingSchools[id] = { status: 'installing' };
                    }
                    subscribeToSchool(id);
                }
            });
            updateBanner();
        });

        updateBanner();
    }


    // ──────────────────────────────────────────────────────────────────────
    // Reverb / Pusher Connection
    // ──────────────────────────────────────────────────────────────────────
    function connectReverb() {
        if (pusherClient) return; // Already connected

        if (typeof Pusher === 'undefined') {
            return;
        }

        const pageIsHttps = window.location.protocol === 'https:';
        let wsHost, wsPort, wssPort, isSecure;

        if (pageIsHttps) {
            wsHost = window.location.hostname;
            wssPort = 443;
            wsPort = 443;
            isSecure = true;
        } else {
            // If host is 0.0.0.0, use window.location.hostname
            wsHost = (REVERB.host && REVERB.host !== '0.0.0.0') ? REVERB.host : window.location.hostname;
            wsHost = wsHost.replace(/^(https?:\/\/|https?\/\/)/i, '').replace(/\/+$/, '');

            isSecure = (REVERB.scheme || 'http') === 'https';
            wsPort = REVERB.port || 9090;
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

        pusherClient.connection.bind('error', function (err) {
            console.error('[SchoolProvision] Pusher Connection Error:', err);
        });

        pusherClient.connection.bind('state_change', function (states) {
            console.log('[SchoolProvision] Pusher State Change:', states);
        });

        // Subscribe to one channel per provisioning school
        Object.keys(installingSchools).forEach(function (schoolId) {
            subscribeToSchool(parseInt(schoolId));
        });
    }

    function subscribeToSchool(schoolId) {
        // Ensure connection is active
        connectReverb();
        if (!pusherClient) return;

        const channelName = 'school.' + schoolId;
        // Check if already subscribed to avoid duplicate listeners
        if (pusherClient.channel(channelName)) {
            return;
        }

        const channel = pusherClient.subscribe(channelName);

        channel.bind('school.provision.progress', function (payload) {
            handleProvisionEvent(payload);
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    // Handle Incoming Event
    // ──────────────────────────────────────────────────────────────────────
    function handleProvisionEvent(payload) {
        const schoolId = parseInt(payload.school_id);

        // Update local state (keep reference to global config if possible)
        if (!installingSchools[schoolId]) {
            installingSchools[schoolId] = {};
        }
        installingSchools[schoolId].progress = payload.progress;
        installingSchools[schoolId].step_label = payload.step_label;
        installingSchools[schoolId].status = payload.status;
        installingSchools[schoolId].step = payload.step;

        // Update table row status cell
        updateRowStatusCell(schoolId, payload);

        // Update modal if it is open for this school
        if (activeModalSchoolId === schoolId) {
            updateModal(payload);
        }

        // Handle final states
        if (payload.status === 'completed') {
            onProvisionCompleted(schoolId, payload);
        } else if (payload.status === 'failed') {
            onProvisionFailed(schoolId, payload);
        }

        updateBanner();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Table Row — Status Cell
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Build the mini-progress-bar HTML for a status cell.
     */
    function buildInstallingCellHtml(schoolId, progress, stepLabel) {
        return '<div class="school-installing-cell" id="provision-cell-' + schoolId + '">'
            + '<span class="installing-label">INSTALLING...<span class="installing-pct">' + progress + '%</span></span>'
            + '<div class="mini-progress-bar"><div class="fill" style="width:' + progress + '%"></div></div>'
            + '<span class="installing-step">' + escHtml(stepLabel) + '</span>'
            + '</div>';
    }

    function updateRowStatusCell(schoolId, payload) {
        // Try the dedicated provision cell first (already rendered as installing)
        const cell = document.getElementById('provision-cell-' + schoolId);
        if (cell) {
            if (payload.status === 'completed') {
                // Replace the cell with a completed badge
                cell.outerHTML = '<span class="badge badge-success"> Active</span>';
            } else if (payload.status === 'failed') {
                cell.outerHTML = '<span class="badge badge-danger"> Failed</span>';
            } else {
                const fill = cell.querySelector('.fill');
                const pct = cell.querySelector('.installing-pct');
                const step = cell.querySelector('.installing-step');
                if (fill) fill.style.width = payload.progress + '%';
                if (pct) pct.textContent = payload.progress + '%';
                if (step) step.textContent = payload.step_label;
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Modal
    // ──────────────────────────────────────────────────────────────────────
    function openModal(schoolId) {

        // Ensure connection is active and we are subscribed
        connectReverb();
        subscribeToSchool(schoolId);

        const school = installingSchools[schoolId];
        if (!school) {
            return;
        }

        activeModalSchoolId = schoolId;

        const nameEl = document.getElementById('ipm-school-name');
        if (nameEl) nameEl.textContent = school.name || ('School #' + schoolId);

        // Rebuild step list
        renderModalSteps(school.step || 'job_started');
        renderModalRing(school.progress || 0);

        const labelEl = document.getElementById('ipm-step-label');
        if (labelEl) labelEl.textContent = school.step_label || 'Initializing...';

        $('#installProgressModal').modal('show');
    }


    function updateModal(payload) {
        renderModalRing(payload.progress);
        renderModalSteps(payload.step);

        const labelEl = document.getElementById('ipm-step-label');
        if (labelEl) labelEl.textContent = payload.step_label;
    }

    function renderModalRing(progress) {
        const circumference = 2 * Math.PI * 45; // r=45
        const offset = circumference - (progress / 100) * circumference;

        const ringFill = document.getElementById('ipm-ring-fill');
        const ringVal = document.getElementById('ipm-ring-value');

        if (ringFill) {
            ringFill.style.strokeDasharray = circumference;
            ringFill.style.strokeDashoffset = offset;
        }
        if (ringVal) ringVal.textContent = progress + '%';
    }

    function renderModalSteps(currentStep) {
        const currentIndex = STEP_ORDER.findIndex(function (s) { return s.key === currentStep; });

        STEP_ORDER.forEach(function (step, i) {
            const li = document.getElementById('ipm-step-' + step.key);
            const iconEl = li ? li.querySelector('.step-icon') : null;
            if (!li || !iconEl) return;

            li.classList.remove('done', 'active', 'failed', 'pending');
            iconEl.innerHTML = '';

            if (currentStep === 'failed' && i === currentIndex) {
                li.classList.add('failed');
                iconEl.classList.remove('pending');
                iconEl.innerHTML = '<i class="fa fa-times"></i>';
            } else if (i < currentIndex) {
                li.classList.add('done');
                iconEl.classList.remove('pending');
                iconEl.innerHTML = '<i class="fa fa-check"></i>';
            } else if (i === currentIndex) {
                li.classList.add('active');
                iconEl.classList.remove('pending');
                iconEl.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i>';
            } else {
                li.classList.add('pending');
                iconEl.classList.add('pending');
                iconEl.innerHTML = '<i class="fa fa-circle-o"></i>';
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    // Completion / Failure Handlers
    // ──────────────────────────────────────────────────────────────────────
    function onProvisionCompleted(schoolId) {
        // Remove from installing list
        delete installingSchools[schoolId];

        // Unsubscribe channel
        if (pusherClient) {
            pusherClient.unsubscribe('school.' + schoolId);
        }

        // Refresh the bootstrap table row after a short delay so the server
        // data is consistent
        setTimeout(function () {
            var $table = $('#table_list');
            if ($table.length && typeof $table.bootstrapTable === 'function') {
                $table.bootstrapTable('refresh');
            }
        }, 1500);

        // Close modal if open for this school
        if (activeModalSchoolId === schoolId) {
            setTimeout(function () {
                const $modal = $('#installProgressModal');
                if ($modal.length) {
                    $modal.modal('hide');
                }
                activeModalSchoolId = null;
            }, 2500);
        }

        updateBanner();
        showToast('School provisioning completed successfully!', 'success');
    }

    function onProvisionFailed(schoolId) {
        updateBanner();
        showToast('School provisioning failed. Please check the logs.', 'error');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Active Provisioning Banner
    // ──────────────────────────────────────────────────────────────────────
    function updateBanner() {
        const count = Object.keys(installingSchools).length;
        const banner = document.getElementById('provision-banner');
        const countEl = document.getElementById('provision-banner-count');

        if (!banner) return;

        if (count > 0) {
            banner.classList.remove('d-none');
            if (countEl) {
                countEl.textContent = 'Currently setting up ' + count + ' school instance' + (count > 1 ? 's' : '') + ' in real-time.';
            }
        } else {
            banner.classList.add('d-none');
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API — called from Blade inline
    // ──────────────────────────────────────────────────────────────────────
    window.SchoolProvision = {
        openModal: openModal,
        buildInstallingCellHtml: buildInstallingCellHtml,
    };

    // ──────────────────────────────────────────────────────────────────────
    // Utilities
    // ──────────────────────────────────────────────────────────────────────
    function showToast(msg, type) {
        if (typeof $.toast === 'function') {
            $.toast({
                text: msg,
                icon: type === 'error' ? 'error' : 'success',
                showHideTransition: 'slide',
                position: 'top-right',
                loaderBg: type === 'error' ? '#f2a654' : '#f96868',
            });
        }
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // Boot when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
})();
