/**
 * Server Configuration Check — server-config.js
 */
(function ($) {
    'use strict';

    /* ── State ─────────────────────────────────────────────────── */
    const checkState = {
        db: null,    // null = untested, true = passed, false = failed
        queue: null,
        reverb: null,
    };

    const TOTAL_CHECKS = 3;

    /* ── DOM helpers ────────────────────────────────────────────── */
    const $outputBody = $('#system-output-body');
    const $healthScore = $('#health-score');
    const $healthGauge = $('#health-gauge-circle');
    const $healthDot = $('#health-dot');
    const $statusAlert = $('#setup-status-alert');
    const $btnDashboard = $('#btn-go-dashboard');

    /* ── Log to terminal ─────────────────────────────────────────── */
    function logLine(text, type /* ok|info|warn|error */) {
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

    /* ── Update System Health widget ─────────────────────────────── */
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
                    '<div><strong>Setup Complete</strong>All checks passed. You can now go to the dashboard.</div>'
                );
            $btnDashboard.prop('disabled', false);
        } else {
            $statusAlert
                .removeClass('complete')
                .addClass('incomplete')
                .html(
                    '<i class="mdi mdi-alert"></i>' +
                    '<div><strong>Setup Incomplete</strong>Please resolve critical issues to access the full dashboard.</div>'
                );
            $btnDashboard.prop('disabled', true);
        }
    }

    /* ── Update card visual state ─────────────────────────────────── */
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

    /* ── Accordion ───────────────────────────────────────────────── */
    $(document).on('click', '.requirement-card-header', function () {
        const $card = $(this).closest('.requirement-card');
        const $body = $card.find('.requirement-card-body');
        const isOpen = $body.hasClass('open');

        // Close all first
        $('.requirement-card').removeClass('is-expanded');
        $('.requirement-card-body').removeClass('open');

        if (!isOpen) {
            $card.addClass('is-expanded');
            $body.addClass('open');
        }
    });

    /* ── Test Database Privileges ─────────────────────────────────── */
    $('#btn-test-db').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-db');

        const payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            db_host: $('#db_host').val().trim(),
            db_port: $('#db_port').val().trim(),
            db_username: $('#db_username').val().trim(),
            db_password: $('#db_password').val(),
        };

        if (!payload.db_host || !payload.db_port || !payload.db_username) {
            logLine('Please fill in DB Host, Port, and Username.', 'warn');
            return;
        }

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Testing...');
        logLine('Starting database privilege test...', 'info');

        $.ajax({
            url: window.serverConfigRoutes.testDatabase,
            method: 'POST',
            data: payload,
            success: function (res) {
                logLines(res.logs || []);
                checkState.db = !!res.success;
                setCardState($card, checkState.db);
                updateHealth();
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unexpected server error.';
                logLine('ERROR: ' + msg, 'error');

                // Also log validation errors if present
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
                $btn.prop('disabled', false).html('<i class="mdi mdi-play"></i> Test Connection & Privileges');
            },
        });
    });

    /* ── Test Queue Worker ────────────────────────────────────────── */
    $('#btn-test-queue').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-queue');

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Checking...');
        logLine('Checking queue worker status...', 'info');

        $.ajax({
            url: window.serverConfigRoutes.testQueue,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                logLines(res.logs || []);
                checkState.queue = !!res.success;
                setCardState($card, checkState.queue);
                updateHealth();
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unexpected server error.';
                logLine('ERROR: ' + msg, 'error');
                checkState.queue = false;
                setCardState($card, false);
                updateHealth();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="mdi mdi-refresh"></i> Test Queue Worker');
            },
        });
    });

    /* ── Test Reverb Worker ──────────────────────────────────────────── */
    $('#btn-test-reverb').on('click', function () {
        const $btn = $(this);
        const $card = $('#card-reverb');

        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Checking...');
        logLine('Checking Reverb worker status...', 'info');

        $.ajax({
            url: window.serverConfigRoutes.testReverb,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                logLines(res.logs || []);
                checkState.reverb = !!res.success;
                setCardState($card, checkState.reverb);
                updateHealth();
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unexpected server error.';
                logLine('ERROR: ' + msg, 'error');
                checkState.reverb = false;
                setCardState($card, false);
                updateHealth();
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="mdi mdi-refresh"></i> Test Reverb Worker');
            },
        });
    });

    /* ── Clear Output ─────────────────────────────────────────────── */
    $('#btn-clear-output').on('click', function () {
        $outputBody.html('<span class="output-waiting">Waiting for verification tests...</span>');
    });

    /* ── Go to Dashboard ─────────────────────────────────────────── */
    $btnDashboard.on('click', function (e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;

        $(this).html('<i class="mdi mdi-loading mdi-spin"></i> Redirecting...');
        $(this).prop('disabled', true);

        $.ajax({
            url: window.serverConfigRoutes.markComplete,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.redirect) {
                    window.location.href = res.redirect;
                } else {
                    window.location.href = '/dashboard';
                }
            },
            error: function () {
                window.location.href = '/dashboard';
            },
        });
    });

    /* ── Init ─────────────────────────────────────────────────────── */
    // Expand first card by default
    $('#card-db').addClass('is-expanded').find('.requirement-card-body').addClass('open');

    // Restore state passed from PHP
    if (typeof window.serverConfigInitState !== 'undefined') {
        const init = window.serverConfigInitState;
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
