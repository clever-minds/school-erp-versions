/**
 * Subscription Page Functions
 */

function subscriptionQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}

function packageTypeFormatter(value, row) {
    if (value == 1) {
        return '<span class="badge badge-info">' + 'Postpaid' + '</span>';
    }
    return '<span class="badge badge-primary">' + 'Prepaid' + '</span>';
}

function transactionPaymentStatus(value, row) {
    if (value == "succeed" || value == "success") {
        return '<span class="badge badge-success">' + 'Success' + '</span>';
    } else if (value == "failed") {
        return '<span class="badge badge-danger">' + 'Failed' + '</span>';
    } else if (value == "pending") {
        return '<span class="badge badge-warning">' + 'Pending' + '</span>';
    }
    return '<span class="badge badge-secondary">' + value + '</span>';
}

window.subscriptionEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('.billing_cycle').html(row.start_date + ' To ' + row.end_date);
        $('.package-type').html(row.subscription.package_type == 1 ? 'Postpaid' : 'Prepaid');
        $('.package_id').val(row.package_id);
        $('.bill_amount').val(row.amount);
        $('.subscription_id').val(row.id);

        $('#edit_id').val(row.id);

        let totalAddons = 0;
        if (row.subscription.addons && row.subscription.addons.length > 0) {
            totalAddons = row.subscription.addons.reduce((acc, curr) => acc + parseFloat(curr.price), 0);
        }

        let userCharges = 0;
        if (row.subscription.package_type == 1) {
            $('.postpaid-package-info').show();
            $('.prepaid-package-info').hide();
            $('.postpaid-table').show();
            $('.prepaid-table').hide();

            $('.plan-name').html(row.name);
            $('.student-charge').html(row.subscription.student_charge);
            $('.total-student').html(row.total_student);
            let totalStudentCharge = parseFloat(row.subscription.student_charge) * parseFloat(row.total_student);
            $('.total-student-charge').html(totalStudentCharge.toFixed(2));

            $('.staff-charge').html(row.subscription.staff_charge);
            $('.total-staff').html(row.total_staff);
            let totalStaffCharge = parseFloat(row.subscription.staff_charge) * parseFloat(row.total_staff);
            $('.total-staff-charge').html(totalStaffCharge.toFixed(2));

            userCharges = totalStudentCharge + totalStaffCharge;
            $('.total-user-charges').html(userCharges.toFixed(2));
        } else {
            $('.postpaid-package-info').hide();
            $('.prepaid-package-info').show();
            $('.postpaid-table').hide();
            $('.prepaid-table').show();

            $('.prepaid-student-usage').html(`<strong>${row.total_student || 0}</strong> / ${row.subscription.no_of_students}`);
            $('.prepaid-staff-usage').html(`<strong>${row.total_staff || 0}</strong> / ${row.subscription.no_of_staffs}`);

            userCharges = parseFloat(row.subscription.charges);
            $('.package_amount').html(userCharges.toFixed(2));
        }

        // Addons logic
        let postpaid_addon_html = '';
        let prepaid_addon_html = '';

        if (row.subscription.addons && row.subscription.addons.length > 0) {
            row.subscription.addons.forEach(addon => {
                if (row.subscription.package_type == 1) {
                    postpaid_addon_html += `<tr>
                        <td class="pl-4" colspan="3">${addon.feature.name}</td>
                        <td class="text-right pr-4 font-weight-bold">${parseFloat(addon.price).toFixed(2)}</td>
                    </tr>`;
                } else {
                    prepaid_addon_html += `<tr>
                        <td class="pl-4">${addon.feature.name}</td>
                        <td class="text-center">${addon.order_id || '-'}</td>
                        <td class="text-center"><span class="badge badge-sm ${addon.status ? 'badge-success' : 'badge-warning'}">${addon.status ? 'Paid' : 'Pending'}</span></td>
                        <td class="text-right pr-4 font-weight-bold">${parseFloat(addon.price).toFixed(2)}</td>
                    </tr>`;
                }
            });
        } else {
            let empty_row = `<tr><td colspan="4" class="text-center py-3 text-muted">No Add-ons found for this period</td></tr>`;
            postpaid_addon_html = empty_row;
            prepaid_addon_html = empty_row;
        }

        $('.postpaid-addon-charges').html(postpaid_addon_html);
        $('.prepaid-addon-charges').html(prepaid_addon_html);

        // Summary Totals
        $('.total-addon-charges').html(totalAddons.toFixed(2));
        let grandTotal = userCharges + totalAddons;
        $('.grand-total').html(grandTotal.toFixed(2));
        $('.bill_amount').val(grandTotal.toFixed(2));

        if (row.payment_status == 'succeed' || row.payment_status == 'success') {
            $('.payment-status').hide();
        } else {
            $('.payment-status').show();
        }
    }
};

$(document).ready(function () {
    $('.cancel-upcoming-plan').click(function () {
        let package_id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to cancel upcoming plan changes!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "subscriptions/cancel_upcoming",
                    type: "POST",
                    data: {
                        package_id: package_id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.error) {
                            showErrorToast(response.message);
                        } else {
                            showSuccessToast(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '.discontinue_addon', function () {
        let addon_id = $(this).data('id');
        let url = baseUrl + '/addons/discontinue/' + addon_id;
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to discontinue this addon from the next billing cycle!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, discontinue it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "get",
                    data: {
                        addon_id: addon_id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.error) {
                            showErrorToast(response.message);
                        } else {
                            showSuccessToast(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    }
                });
            }
        });
    });
});
