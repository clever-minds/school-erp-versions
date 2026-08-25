/* ==================================================
   Certificate Assignment Module - JavaScript
   Routes injected via window.CA_CONFIG from Blade
   ================================================== */

$(function () {

    const CFG = window.CA_CONFIG || {};

    // ---- State ----
    let currentTab = 'student';
    let selectedUserIds = new Set();
    let historySelectedUserIds = new Set();

    // ---- Tab switching ----
    $('.ca-tab-btn').on('click', function () {
        currentTab = $(this).data('tab');
        $('.ca-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.ca-tab-panel').hide();
        $(`#tab-${currentTab}`).show();

        selectedUserIds.clear();
        updateSelectionBar(0);

        historySelectedUserIds.clear();
        updateHistorySelectionBar(0);

        if (currentTab === 'history') {
            reloadHistory();
        } else {
            reloadUserTable();
        }
    });

    // ---- Reload User Table (students or staff) ----
    function reloadUserTable() {
        if (currentTab === 'student') {
            $('#student-table').bootstrapTable('refresh', { pageNumber: 1 });
        } else if (currentTab === 'staff') {
            $('#staff-table').bootstrapTable('refresh', { pageNumber: 1 });
        }
    }

    function reloadHistory() {
        $('#history-table').bootstrapTable('refresh', { pageNumber: 1 });
    }

    // ---- Filter change triggers ----
    $('#cert_template_id, #cert_template_id_staff, #filter_class_section, #filter_exam').on('change', function () {
        selectedUserIds.clear();
        updateSelectionBar(0);
        reloadUserTable();
    });

    // ---- Query param builders ----
    window.studentQueryParams = function (params) {
        params.certificate_template_id = $('#cert_template_id').val();
        params.class_section_id = $('#filter_class_section').val();
        params.exam_id = $('#filter_exam').val();
        return params;
    };

    window.staffQueryParams = function (params) {
        params.certificate_template_id = $('#cert_template_id_staff').val();
        return params;
    };

    window.historyQueryParams = function (params) {
        params.certificate_template_id = $('#history_cert_template_id').val();
        return params;
    };

    // ---- Selection tracking ----
    function handleTableSelection(tableId, stateSet, idKey) {
        let isProgrammatic = false;

        $(`#${tableId}`).on(
            'check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table',
            function (e, rowsAfter, rowsBefore) {
                if (isProgrammatic) return; // skip updating set when script checks rows

                let rows = rowsAfter;
                let isAdd = ['check', 'check-all'].includes(e.type);

                if (e.type === 'uncheck-all') rows = rowsBefore;

                let rowArray = $.isArray(rows) ? rows : [rows];

                rowArray.forEach(r => {
                    let id = r[idKey];
                    if (id !== undefined && id !== null) {
                        if (isAdd) {
                            stateSet.add(id);
                        } else {
                            stateSet.delete(id);
                        }
                    }
                });

                if (tableId === 'history-table') {
                    updateHistorySelectionBar(stateSet.size);
                } else {
                    updateSelectionBar(stateSet.size);
                }
            }
        );

        $(`#${tableId}`).on('post-body.bs.table', function () {
            let tableData = $(`#${tableId}`).bootstrapTable('getData');
            let idsToCheck = tableData.filter(r => stateSet.has(r[idKey])).map(r => r[idKey]);
            if (idsToCheck.length > 0) {
                isProgrammatic = true;
                $(`#${tableId}`).bootstrapTable('checkBy', { field: idKey, values: idsToCheck });
                isProgrammatic = false;
            }
        });
    }

    handleTableSelection('student-table', selectedUserIds, 'user_id');
    handleTableSelection('staff-table', selectedUserIds, 'user_id');
    handleTableSelection('history-table', historySelectedUserIds, 'id');

    function updateSelectionBar(count) {
        if (count > 0) {
            if (currentTab !== 'history') {
                $('.ca-assign-bar').not('#history-action-bar').show();
            }
            $('.ca-selection-info').text(`${count} user(s) selected`);
        } else {
            $('.ca-assign-bar').not('#history-action-bar').hide();
        }
    }

    function updateHistorySelectionBar(count) {
        if (count > 0) {
            $('#history-action-bar').css('display', 'flex');
            $('.history-selection-info').text(`${count} record(s) selected`);
        } else {
            $('#history-action-bar').hide();
        }
    }

    // ---- Formatters ----

    window.caStatusFormatter = function (value, row) {
        if (row.already_assigned) {
            return '<span class="badge-assigned">✓ Assigned</span>';
        }
        return '<span class="badge-pending">Pending</span>';
    };

    window.historyAvatarFormatter = function (value, row) {
        const img = row.user_image || CFG.defaultAvatar;
        return `<img src="${img}" class="history-avatar" onerror="this.src='${CFG.defaultAvatar}'">`;
    };

    window.historyRevokeFormatter = function (value, row) {
        return `<button class="btn-action-hard-delete btn-xs btn-rounded btn" onclick="revokeAssignment(${row.template_id}, ${row.user_id})">
                    <i class="fa fa-trash"></i> 
                </button>`;
    };

    // ---- Assign button click ----
    $('.btn-assign-submit').on('click', function () {
        if (selectedUserIds.size === 0) {
            showErrorToast('Please select at least one user.');
            return;
        }

        const templateId = currentTab === 'student'
            ? $('#cert_template_id').val()
            : $('#cert_template_id_staff').val();

        if (!templateId) {
            showErrorToast('Please select a certificate template first.');
            return;
        }

        const examId = currentTab === 'student' ? ($('#filter_exam').val() || '') : '';
        const issuedAt = $('#issued_at').val() || '';
        const userType = currentTab === 'student' ? 'Student' : 'Staff';

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Assigning...');

        $.ajax({
            url: CFG.assignUrl,
            type: 'POST',
            data: {
                _token: CFG.csrfToken,
                certificate_template_id: templateId,
                user_ids: Array.from(selectedUserIds).join(','),
                user_type: userType,
                exam_id: examId,
                issued_at: issuedAt,
            },
            success: function (res) {
                showSuccessToast(res.message || 'Certificates assigned!');
                selectedUserIds.clear();
                updateSelectionBar(0);
                reloadUserTable();
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'An error occurred.';
                showSuccessToast(msg, 'danger');
            },
            complete: function () {
                $('.btn-assign-submit').prop('disabled', false).html('<i class="fa fa-check"></i> Assign Certificate');
            }
        });
    });

    window.revokeAssignment = function (templateId, userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to revoke this certificate.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, revoke it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: CFG.revokeUrl,
                    type: 'POST',
                    data: {
                        _token: CFG.csrfToken,
                        certificate_template_id: templateId,
                        user_id: userId,
                    },
                    success: function (res) {
                        showSuccessToast(res.message || 'Assignment revoked.');
                        reloadHistory();
                        reloadUserTable();
                    },
                    error: function () {
                        showErrorToast('Failed to revoke assignment.');
                    }
                });
            }
        });
    };

    // ---- Generate Certificate Action ----
    $('#btn-generate-history').on('click', function () {
        const templateId = $('#history_cert_template_id').val();

        if (!templateId) {
            showErrorToast('Please select a Certificate Template from the filter first.');
            return;
        }

        if (historySelectedUserIds.size === 0) {
            showErrorToast('Please select at least one record to generate certificates.');
            return;
        }

        const postUrl = CFG.generateStudentUrl; // Single generation endpoint for assignments

        const form = $('<form>', {
            target: '_blank',
            method: 'POST',
            action: postUrl
        }).append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: CFG.csrfToken
        })).append($('<input>', {
            type: 'hidden',
            name: 'certificate_template_id',
            value: templateId
        })).append($('<input>', {
            type: 'hidden',
            name: 'ids',
            value: Array.from(historySelectedUserIds).join(',')
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    });

    // ---- History filter ----
    $('#history_cert_template_id').on('change', function () {
        historySelectedUserIds.clear();
        updateHistorySelectionBar(0);
        reloadHistory();
    });

    // ---- Initialise: show first tab ----
    $('.ca-assign-bar').hide();
    $(`#tab-student`).show();
    $('#tab-staff, #tab-history').hide();
});
