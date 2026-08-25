/**
 * System Health Check — health.js
 * Reuses the server-config UI pattern but targets the system-settings/health routes.
 */
(function ($) {
    'use strict';

    /* ── AJAX defaults: always ask for JSON so Laravel returns JSON errors ── */
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    });

    /* ── State ─────────────────────────────────────────────────── */
    const checkState = {
        db: null,
        queue: null,
        reverb: null,
    };

    const TOTAL_CHECKS = 3;

    /* ── DOM helpers ─────────────────────────────────────────── */
    const $outputBody = $('#health-system-output-body');
    const $healthScore = $('#health-score');
    const $healthGauge = $('#health-gauge-circle');
    const $healthDot = $('#health-dot');
    const $statusAlert = $('#health-setup-status-alert');

    /* ── Log to terminal ─────────────────────────────────────── */
    function logLine(text, type) {
        const cls = type || 'info';
        const time = new Date().toLocaleTimeString();
        $outputBody.find('.output-waiting').remove();
        $outputBody.append(
            $('<span>').addClass('output-line ' + cls).text('[' + time + '] ' + text)
        );
        $outputBody.scrollTop($outputBody[0].scrollHeight);
    }

    function logLines(lines) {
        lines.forEach(function (line) {
            let type = 'info';
            if (line.startsWith('[OK]')) type = 'ok';
            else if (line.startsWith('[WARN]')) type = 'warn';
            else if (line.startsWith('[ERROR]')) type = 'error';
            logLine(line, type);
        });
    }

    /* ── Extract error message from xhr (handles JSON and HTML responses) ── */
    function extractError(xhr) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) return xhr.responseJSON.message;
            if (xhr.responseJSON.error) return xhr.responseJSON.error;
        }
        // Show HTTP status for non-JSON responses (e.g. 500 HTML page)
        return 'Server error (HTTP ' + xhr.status + '). Check Laravel logs.';
    }

    /* ── Update System Health widget ─────────────────────────── */
    function updateHealth() {
        const passed = [checkState.db, checkState.queue, checkState.reverb].filter(v => v === true).length;
        $healthScore.text(passed + '/' + TOTAL_CHECKS);

        $healthGauge.removeClass('some-passed all-passed');
        $healthDot.removeClass('all-passed');

        if (passed > 0 && passed < TOTAL_CHECKS) {
            $healthGauge.addClass('some-passed');
        } else if (passed === TOTAL_CHECKS) {
            $healthGauge.addClass('all-passed');
            $healthDot.addClass('all-passed');
        }

        if (passed === TOTAL_CHECKS) {
            $statusAlert
                .removeClass('incomplete')
                .addClass('complete')
                .html(
                    '<i class="mdi mdi-check-circle"></i>' +
                    '<div><strong>Setup Complete</strong>All checks passed.</div>'
                );
        } else {
            $statusAlert
                .removeClass('complete')
                .addClass('incomplete')
                .html(
                    '<i class="mdi mdi-alert"></i>' +
                    '<div><strong>Setup Incomplete</strong>Please resolve the critical issues above.</div>'
                );
        }
    }

    /* ── Update card visual state ────────────────────────────── */
    function setCardState($card, passed) {
        const $circle = $card.find('.check-circle');
        $card.removeClass('check-passed check-failed');
        $circle.removeClass('passed failed').html('');

        if (passed === true) {
            $card.addClass('check-passed');
            $circle.addClass('passed').html('<i class="mdi mdi-check"></i>');
        } else if (passed === false) {
            $card.addClass('check-failed');
            $circle.addClass('failed').html('<i class="mdi mdi-close"></i>');
        }
    }

    /* ── Accordion ───────────────────────────────────────────── */
    $(document).on('click', '#card-db .requirement-card-header, #card-queue .requirement-card-header, #card-reverb .requirement-card-header', function () {
        const $card = $(this).closest('.requirement-card');
        const $body = $card.find('.requirement-card-body');
        const isOpen = $body.hasClass('open');

        $('#card-db, #card-queue, #card-reverb').removeClass('is-expanded');
        $('#card-db .requirement-card-body, #card-queue .requirement-card-body, #card-reverb .requirement-card-body').removeClass('open');

        if (!isOpen) {
            $card.addClass('is-expanded');
            $body.addClass('open');
        }
    });

    /* ── Test Database Privileges ────────────────────────────── */
    $('#health-btn-test-db').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-db');

        const payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            db_host: $('#health_db_host').val().trim(),
            db_port: $('#health_db_port').val().trim(),
            db_username: $('#health_db_username').val().trim(),
            db_password: $('#health_db_password').val(),
        };

        if (!payload.db_host || !payload.db_port || !payload.db_username) {
            logLine('Please fill in DB Host, Port, and Username.', 'warn');
            return;
        }

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Testing...');
        logLine('Starting database privilege test...', 'info');

        $.ajax({
            url: window.healthCheckRoutes.testDatabase,
            method: 'POST',
            data: payload,
            success: function (res) {
                logLines(res.logs || []);
                checkState.db = !!res.success;
                setCardState($card, checkState.db);
                updateHealth();
            },
            error: function (xhr) {
                logLine('ERROR: ' + extractError(xhr), 'error');
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, errs) {
                        errs.forEach(function (e) { logLine('  ' + e, 'error'); });
                    });
                }
                checkState.db = false;
                setCardState($card, false);
                updateHealth();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="mdi mdi-play"></i> Test Connection &amp; Update .env');
            },
        });
    });

    /* ── Test Queue Worker ───────────────────────────────────── */
    $('#health-btn-test-queue').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-queue');

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Checking...');
        logLine('Checking queue worker status...', 'info');

        $.ajax({
            url: window.healthCheckRoutes.testQueue,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                logLines(res.logs || []);
                checkState.queue = !!res.success;
                setCardState($card, checkState.queue);
                updateHealth();
            },
            error: function (xhr) {
                logLine('ERROR: ' + extractError(xhr), 'error');
                checkState.queue = false;
                setCardState($card, false);
                updateHealth();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="mdi mdi-refresh"></i> Re-test Queue Worker');
            },
        });
    });

    /* ── Test Reverb Worker ─────────────────────────────────── */
    $('#health-btn-test-reverb').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-reverb');

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Checking...');
        logLine('Checking Reverb worker status...', 'info');

        $.ajax({
            url: window.healthCheckRoutes.testReverb,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                logLines(res.logs || []);
                checkState.reverb = !!res.success;
                setCardState($card, checkState.reverb);
                updateHealth();
            },
            error: function (xhr) {
                logLine('ERROR: ' + extractError(xhr), 'error');
                checkState.reverb = false;
                setCardState($card, false);
                updateHealth();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="mdi mdi-refresh"></i> Test Reverb Worker');
            },
        });
    });

    /* ── Re-run All Checks (queue + reverb only; DB modifies .env) ── */
    $('#btn-rerun-check').on('click', function () {
        logLine('Re-running all service checks...', 'info');
        $('#health-btn-test-queue').trigger('click');
        setTimeout(function () {
            $('#health-btn-test-reverb').trigger('click');
        }, 800);
    });

    /* ── Clear Output ────────────────────────────────────────── */
    $('#health-btn-clear-output').on('click', function () {
        $outputBody.html('<span class="output-waiting">Waiting for verification tests...</span>');
    });

    /* ── Init ───────────────────────────────────────────────── */
    $('#card-db').addClass('is-expanded').find('.requirement-card-body').addClass('open');

    if (typeof window.healthInitState !== 'undefined') {
        const init = window.healthInitState;
        if (init.dbPassed !== null) {
            checkState.db = init.dbPassed;
            setCardState($('#card-db'), checkState.db);
        }
        if (init.queuePassed !== null) {
            checkState.queue = init.queuePassed;
            setCardState($('#card-queue'), checkState.queue);
        }
        if (init.reverbPassed !== null) {
            checkState.reverb = init.reverbPassed;
            setCardState($('#card-reverb'), checkState.reverb);
        }
        updateHealth();
    }

}(jQuery));
