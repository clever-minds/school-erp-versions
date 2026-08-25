/**
 * Subscription Detail Page — JS
 * Handles: table formatters, generate-bill AJAX, delete-payment confirmation.
 */

'use strict';

/* ──────────────────────────────────────────────────────
   Bootstrap Table Formatters
────────────────────────────────────────────────────── */

/**
 * Format the amount column by prepending the currency symbol.
 * The symbol is injected into the page via a hidden span.
 */
function amountFormatter(value) {
    var symbol = $('#currency-symbol').val() || '';
    return symbol + value;
}

/**
 * Return a coloured badge for each subscription status.
 */
function invoiceStatusFormatter(value) {
    var statusMap = {
        'Paid': ['sub-badge-success', value],
        'Current Cycle': ['sub-badge-primary', value],
        'Next Billing Cycle': ['sub-badge-info', value],
        'Over Due': ['sub-badge-danger', value],
        'Failed': ['sub-badge-danger', value],
        'Unpaid': ['sub-badge-danger', value],
        'Pending': ['sub-badge-warning', value],
        'Bill Not Generated': ['sub-badge-not-generated', value],
    };
    var entry = statusMap[value];
    if (entry) {
        return '<span class="sub-badge badge ' + entry[0] + '">' + entry[1] + '</span>';
    }
    return '<span class="sub-badge badge sub-badge-secondary">' + (value || '—') + '</span>';
}

/**
 * Render the operate column (HTML from server) without escaping.
 */
function operateFormatter(value) {
    return value || '';
}

/* ──────────────────────────────────────────────────────
   Generate Bill — AJAX handler
────────────────────────────────────────────────────── */

$(document).on('click', '.generate-bill', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');

    Swal.fire({
        title: window.langGenerateBill || 'Generate Bill',
        text: window.langGenerateBillConfirm || 'Generate the bill for this subscription?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: window.langYes || 'Yes, Generate',
        cancelButtonText: window.langCancel || 'Cancel',
        confirmButtonColor: '#5144f8',
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    if (res && res.error === false) {
                        showSuccessToast(res.message);
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                    }
                },
                error: function () {
                    showErrorToast('Server error. Please try again.');
                }
            });
        }
    });
});

/* ──────────────────────────────────────────────────────
   Delete Payment — confirmation for Cash/Cheque rows
────────────────────────────────────────────────────── */

$(document).on('click', '.delete-form', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');

    Swal.fire({
        title: window.langDeletePayment || 'Delete Payment',
        text: window.langDeletePaymentConfirm || 'This will permanently remove the recorded payment. Continue?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: window.langDelete || 'Delete',
        cancelButtonText: window.langCancel || 'Cancel',
        confirmButtonColor: '#dc3545',
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res && res.error === false) {
                        showSuccessToast(res.message || 'Payment deleted successfully.');
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                    }
                },
                error: function () {
                    showErrorToast('Server error. Please try again.');
                }
            });
        }
    });
});

/* ──────────────────────────────────────────────────────
   Helpers — toast wrappers
   (falls back to alert if the global showToast is absent)
────────────────────────────────────────────────────── */

// function showSuccessToast(msg) {
//     if (typeof showSuccessMessage === 'function') {
//         showSuccessMessage(msg);
//     } else if (typeof toastr !== 'undefined') {
//         toastr.success(msg);
//     } else {
//         alert(msg);
//     }
// }

// function showErrorToast(msg) {
//     if (typeof showErrorMessage === 'function') {
//         showErrorMessage(msg);
//     } else if (typeof toastr !== 'undefined') {
//         toastr.error(msg);
//     } else {
//         alert(msg);
//     }
// }

/* ──────────────────────────────────────────────────────
   View Subscription Details — AJAX handler
────────────────────────────────────────────────────── */

window.availableFeatures = [];
window.availableAddons = [];
window.currentSubscriptionId = null;

function renderAvailableFeatures(search = '') {
    var html = '';
    var filtered = window.availableFeatures.filter(function (f) {
        return f.name.toLowerCase().includes(search.toLowerCase());
    });
    if (filtered.length > 0) {
        $.each(filtered, function (i, f) {
            html += '<div class="vs-dropdown-item vs-add-feature-btn" data-id="' + f.id + '">';
            html += '<div class="vs-dropdown-item-title">' + f.name + '</div>';
            html += '<div class="vs-dropdown-item-subtitle">ADD FEATURE</div>';
            html += '</div>';
        });
    } else {
        html = '<div class="p-3 text-center text-muted small">No features found</div>';
    }
    $('#available-features-list').html(html);
}

function renderAvailableAddons(search = '') {
    var html = '';
    var symbol = $('#currency-symbol').val() || '';
    var filtered = window.availableAddons.filter(function (a) {
        var name = a.feature ? a.feature.name : '';
        return name.toLowerCase().includes(search.toLowerCase());
    });
    if (filtered.length > 0) {
        $.each(filtered, function (i, a) {
            var name = a.feature ? a.feature.name : '';
            html += '<div class="vs-dropdown-item vs-add-addon-btn" data-id="' + a.id + '">';
            html += '<div class="vs-dropdown-item-title">' + name + '</div>';
            html += '<div class="vs-dropdown-item-subtitle">PRICE: ' + symbol + a.price + ' </div>';
            html += '</div>';
        });
    } else {
        html = '<div class="p-3 text-center text-muted small">No addons found</div>';
    }
    $('#available-addons-list').html(html);
}

function loadSubscriptionDetails(id) {
    window.currentSubscriptionId = id;
    var url = baseUrl + '/subscriptions/report/school/subscription/' + id + '/details';

    // Reset modal state
    $('#view-subscription-loader').removeClass('d-none');
    $('#view-subscription-content').addClass('d-none');
    $('#search-feature-input').val('');
    $('#search-addon-input').val('');

    $.ajax({
        url: url,
        type: 'GET',
        success: function (res) {
            if (res && res.error === false) {
                var data = res.data;
                var symbol = $('#currency-symbol').val() || '';

                $('#vs-plan-name').text(data.name || '-');
                $('#vs-status').text(data.status || '-');
                $('#vs-billing-type').text(data.package_type == 0 ? 'Prepaid' : 'Postpaid');
                $('#vs-start-date').text(data.start_date_format || '-');
                $('#vs-end-date').text(data.end_date_format || '-');

                // Render features
                var featuresHtml = '<div class="row">';
                var featuresCount = 0;
                if (data.subscription_feature && data.subscription_feature.length > 0) {
                    featuresCount = data.subscription_feature.length;
                    $.each(data.subscription_feature, function (index, sf) {
                        featuresHtml += '<div class="col-sm-12 col-md-6 col-lg-3 mb-3" id="feature-card-' + sf.id + '">';
                        featuresHtml += '<div class="vs-feature-card h-100">';
                        featuresHtml += '<div class="d-flex align-items-center flex-grow-1">';
                        featuresHtml += '<div class="vs-feature-icon"><i class="fa fa-check-circle"></i></div>';
                        featuresHtml += '<div class="vs-feature-name">' + (sf.feature ? sf.feature.name : '-') + '</div>';
                        featuresHtml += '</div>';
                        featuresHtml += '<button type="button" class="vs-btn-delete remove-subscription-feature" data-id="' + sf.id + '" title="Remove"><i class="fa fa-trash"></i></button>';
                        featuresHtml += '</div></div>';
                    });
                } else {
                    featuresHtml += '<div class="col-12 text-center text-muted py-4">No features found.</div>';
                }
                featuresHtml += '</div>';
                $('#vs-features-grid').html(featuresHtml);
                $('#included-features-count').text(featuresCount);

                // Render addons
                var addonsHtml = '<div class="row">';
                var addonsCount = 0;
                if (data.addons && data.addons.length > 0) {
                    addonsCount = data.addons.length;
                    $.each(data.addons, function (index, addonObj) {
                        addonsHtml += '<div class="col-sm-12 col-md-6 col-lg-3 mb-3" id="addon-row-' + addonObj.id + '">';
                        addonsHtml += '<div class="vs-feature-card h-100">';
                        addonsHtml += '<div class="d-flex flex-column flex-grow-1">';
                        addonsHtml += '<div class="font-weight-bold mb-1">' + (addonObj.addon && addonObj.addon.feature ? addonObj.addon.feature.name : (addonObj.feature ? addonObj.feature.name : '-')) + '</div>';
                        addonsHtml += '<div class="text-muted">' + symbol + addonObj.price + ' </div>';
                        addonsHtml += '</div>';
                        addonsHtml += '<button type="button" class="vs-btn-delete remove-subscription-addon" data-id="' + addonObj.id + '" title="Remove"><i class="fa fa-trash"></i></button>';
                        addonsHtml += '</div></div>';
                    });
                } else {
                    addonsHtml += '<div class="col-12 text-center text-muted py-4">No addons found.</div>';
                }
                addonsHtml += '</div>';
                $('#vs-addons-grid').html(addonsHtml);
                $('#addons-count').text(addonsCount);

                // Setup available dropdowns
                var activeFeatureIds = (data.subscription_feature || []).map(function (sf) { return sf.feature_id; });
                window.availableFeatures = (data.all_features || []).filter(function (f) {
                    return !activeFeatureIds.includes(f.id);
                });
                renderAvailableFeatures();

                var activeAddonFeatureIds = (data.addons || []).map(function (a) { return a.feature_id; });
                window.availableAddons = (data.all_addons || []).filter(function (a) {
                    return !activeAddonFeatureIds.includes(a.feature_id);
                });
                renderAvailableAddons();

                $('#view-subscription-loader').addClass('d-none');
                $('#view-subscription-content').removeClass('d-none');
            } else {
                showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                $('#view-subscription-modal').modal('hide');
            }
        },
        error: function () {
            showErrorToast('Server error. Please try again.');
            $('#view-subscription-modal').modal('hide');
        }
    });
}

$(document).on('click', '.view-subscription-details', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    loadSubscriptionDetails(id);
});

/* ──────────────────────────────────────────────────────
   Add Search Logic
────────────────────────────────────────────────────── */
$(document).on('keyup', '#search-feature-input', function () {
    renderAvailableFeatures($(this).val());
});
$(document).on('keyup', '#search-addon-input', function () {
    renderAvailableAddons($(this).val());
});

/* ──────────────────────────────────────────────────────
   Remove Subscription Feature
────────────────────────────────────────────────────── */
$(document).on('click', '.remove-subscription-feature', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var url = baseUrl + '/subscriptions/report/school/subscription/feature/' + id;

    Swal.fire({
        title: window.langDeleteFeature || 'Remove Feature',
        text: window.langDeleteFeatureConfirm || 'Are you sure you want to remove this feature from the current subscription?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: window.langDelete || 'Remove',
        cancelButtonText: window.langCancel || 'Cancel',
        confirmButtonColor: '#dc3545',
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res && res.error === false) {
                        showSuccessToast(res.message || 'Feature removed successfully.');
                        $('#feature-card-' + id).remove();

                        var newCount = parseInt($('#included-features-count').text()) - 1;
                        $('#included-features-count').text(newCount >= 0 ? newCount : 0);

                        if ($('#vs-features-grid .vs-feature-card').length === 0) {
                            $('#vs-features-grid').html('<div class="row"><div class="col-12 text-center text-muted py-4">No features found.</div></div>');
                        }

                        // Re-fetch after removal to update available lists
                        if (window.currentSubscriptionId) {
                            loadSubscriptionDetails(window.currentSubscriptionId);
                        }
                    } else {
                        showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                    }
                },
                error: function () {
                    showErrorToast('Server error. Please try again.');
                }
            });
        }
    });
});

/* ──────────────────────────────────────────────────────
   Remove Subscription Addon
────────────────────────────────────────────────────── */
$(document).on('click', '.remove-subscription-addon', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var url = baseUrl + '/subscriptions/report/school/subscription/addon/' + id;

    Swal.fire({
        title: window.langDeleteAddon || 'Remove Add-on',
        text: window.langDeleteAddonConfirm || 'Are you sure you want to remove this add-on from the current subscription?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: window.langDelete || 'Remove',
        cancelButtonText: window.langCancel || 'Cancel',
        confirmButtonColor: '#dc3545',
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res && res.error === false) {
                        showSuccessToast(res.message || 'Add-on removed successfully.');
                        $('#addon-row-' + id).remove();

                        var newCount = parseInt($('#addons-count').text()) - 1;
                        $('#addons-count').text(newCount >= 0 ? newCount : 0);

                        if ($('#vs-addons-grid .vs-feature-card').length === 0) {
                            $('#vs-addons-grid').html('<div class="row"><div class="col-12 text-center text-muted py-4">No addons found.</div></div>');
                        }

                        // Re-fetch after removal to update available lists
                        if (window.currentSubscriptionId) {
                            loadSubscriptionDetails(window.currentSubscriptionId);
                        }
                    } else {
                        showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                    }
                },
                error: function () {
                    showErrorToast('Server error. Please try again.');
                }
            });
        }
    });
});

/* ──────────────────────────────────────────────────────
   Add Subscription Feature
────────────────────────────────────────────────────── */
$(document).on('click', '.vs-add-feature-btn', function (e) {
    e.preventDefault();
    if (!window.currentSubscriptionId) return;

    var featureId = $(this).data('id');
    var url = baseUrl + '/subscriptions/report/school/subscription/' + window.currentSubscriptionId + '/feature';

    // Close dropdown
    $(this).closest('.dropdown-menu').removeClass('show');
    $('#view-subscription-loader').removeClass('d-none');
    $('#view-subscription-content').addClass('d-none');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            feature_id: featureId
        },
        success: function (res) {
            if (res && res.error === false) {
                showSuccessToast(res.message || 'Feature added successfully.');
                loadSubscriptionDetails(window.currentSubscriptionId);
            } else {
                showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                loadSubscriptionDetails(window.currentSubscriptionId);
            }
        },
        error: function () {
            showErrorToast('Server error. Please try again.');
            loadSubscriptionDetails(window.currentSubscriptionId);
        }
    });
});

/* ──────────────────────────────────────────────────────
   Add Subscription Addon
────────────────────────────────────────────────────── */
$(document).on('click', '.vs-add-addon-btn', function (e) {
    e.preventDefault();
    if (!window.currentSubscriptionId) return;

    var addonId = $(this).data('id');
    var url = baseUrl + '/subscriptions/report/school/subscription/' + window.currentSubscriptionId + '/addon';

    // Close dropdown
    $(this).closest('.dropdown-menu').removeClass('show');
    $('#view-subscription-loader').removeClass('d-none');
    $('#view-subscription-content').addClass('d-none');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            addon_id: addonId
        },
        success: function (res) {
            if (res && res.error === false) {
                showSuccessToast(res.message || 'Addon added successfully.');
                loadSubscriptionDetails(window.currentSubscriptionId);
            } else {
                showErrorToast((res && res.message) ? res.message : 'Something went wrong.');
                loadSubscriptionDetails(window.currentSubscriptionId);
            }
        },
        error: function () {
            showErrorToast('Server error. Please try again.');
            loadSubscriptionDetails(window.currentSubscriptionId);
        }
    });
});
