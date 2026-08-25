$(document).ready(function () {
    var currentType = 'boards';

    function updateStats(stats) {
        if (!stats) return;
        Object.keys(stats).forEach(type => {
            // Update tab badges
            $(`#tab-count-${type}`).text(stats[type]);

            // Update header dots
            const dot = $(`#header-dot-${type}`);
            if (stats[type] > 0) {
                dot.addClass('active');
            } else {
                dot.removeClass('active');
            }
        });
    }

    function updateMasterStatusUI(isEnabled) {
        var text = isEnabled ? window.trans['enabled'] : window.trans['disabled'];
        $('#masterStatusText').text(text);

        if (isEnabled) {
            $('.academy-master-header').removeClass('draft').addClass('published');
            $('.ms-status').css('color', '#2ecc71');
            $('.badge-mode').removeClass('draft-badge').addClass('published-badge').text(window.trans['published_to_school']);
            $('.academy-master-header h2').text(window.trans['academy_master_setup']);
            $('.academy-master-header p').text(window.trans['the_system_is_currently_live_schools_can_now_use_this_data_for_their_academy_configuration']);
            $('.internal-alert').slideUp();
        } else {
            $('.academy-master-header').removeClass('published').addClass('draft');
            $('.ms-status').css('color', '#a4adc1');
            $('.badge-mode').removeClass('published-badge').addClass('draft-badge').text(window.trans['draft_mode_internal']);
            $('.academy-master-header h2').text(window.trans['academy_master_setup']);
            $('.academy-master-header p').text(window.trans['the_system_is_in_draft_you_can_add_and_manage_all_categories_below_all_categories_must_have_data_before_publishing']);
            $('.internal-alert').slideDown();
        }
    }

    // Master Status Toggle
    $('#masterStatusToggle').change(function () {
        var isChecked = $(this).is(':checked');
        var status = isChecked ? 1 : 0;

        // Immediate UI update
        updateMasterStatusUI(isChecked);

        let data = new FormData();
        data.append('status', status);
        data.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: masterStatusUrl,
            type: "POST",
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.error) {
                    // Revert toggle and UI
                    $('#masterStatusToggle').prop('checked', !isChecked);
                    updateMasterStatusUI(!isChecked);
                    showErrorToast(response.message);
                } else {
                    showSuccessToast(response.message);
                }
            },
            error: function () {
                // Revert on system error too
                $('#masterStatusToggle').prop('checked', !isChecked);
                updateMasterStatusUI(!isChecked);
                showErrorToast('Failed to change master status');
            }
        });
    });

    // Custom Search
    $('#customSearch').on('keyup', function () {
        $('#table_list').bootstrapTable('refresh', {
            query: { search: $(this).val() }
        });
    });

    // Refresh Btn
    $('#refreshTable').click(function () {
        $('#table_list').bootstrapTable('refresh');
    });

    // Action Events (Defined early so they're available for initial table load)
    function handleEdit(row) {
        $('#academyForm')[0].reset();

        // Populate form
        $('#editId').val(row.id);
        $('#name').val(row.name);
        $('#code').val(row.code);

        if (row.status == 1) {
            $('#status').prop('checked', true);
        } else {
            $('#status').prop('checked', false);
        }

        if (currentType === 'subjects') {
            if (row.bg_color) {
                $('#bg_color').asColorPicker('val', row.bg_color);
            } else {
                $('#bg_color').asColorPicker('val', '#000000');
            }
            $('#subject_type').val(row.type);
        }

        let singular = currentType.slice(0, -1);
        if (currentType === 'classes') singular = 'class';

        $('#academyModalLabel').text(window.trans['edit'] + ' ' + singular.charAt(0).toUpperCase() + singular.slice(1));
        $('#academyModal').modal('show');
    }

    function handleDelete(id, message, url) {
        Swal.fire({
            title: window.trans['are_you_sure'],
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: window.trans['yes_delete']
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        '_method': 'DELETE'
                    },
                    success: function (response) {
                        if (response.error) {
                            showErrorToast(response.message);
                        } else {
                            showSuccessToast(response.message);
                            $('#table_list').bootstrapTable('refresh');
                            updateStats(response.stats);
                        }
                    },
                    error: function () {
                        showErrorToast('Something went wrong');
                    }
                });
            }
        });
    }

    // Action Events
    window.actionEvents = {
        'click .edit-data': function (e, value, row, index) {
            e.preventDefault();
            handleEdit(row);
        },
        'click .academy-delete-btn': function (e, value, row, index) {
            e.preventDefault();
            let message = $(e.currentTarget).data('message') || window.trans['You wont be able to revert this'];
            let url = $(e.currentTarget).attr('href');
            handleDelete(row.id, message, url);
        }
    };

    // Global Event Delegation for Mobile/Reorder cases
    $(document).on('click', '.edit-data', function (e) {
        let id = $(this).data('id');
        if (!id) return;

        e.preventDefault();
        let row = $('#table_list').bootstrapTable('getRowByUniqueId', id);
        if (!row) {
            // Fallback: search in all loaded data
            let data = $('#table_list').bootstrapTable('getData');
            row = data.find(item => item.id == id);
        }

        if (row) {
            handleEdit(row);
        }
    });

    $(document).on('click', '.academy-delete-btn', function (e) {
        let id = $(this).data('id');
        if (!id) return;

        e.preventDefault();
        let message = $(this).data('message') || window.trans['You wont be able to revert this'];
        let url = $(this).attr('href');
        handleDelete(id, message, url);
    });

    // Add New Btn
    $('#addNewBtn').click(function () {
        $('#academyForm')[0].reset();
        $('#editId').val('');
        $('#formType').val(currentType);
        $('#bg_color').asColorPicker('val', '#000000');

        let singular = currentType.slice(0, -1);
        if (currentType === 'classes') singular = 'class';

        $('#academyModalLabel').text(window.trans['add_new'] + singular.charAt(0).toUpperCase() + singular.slice(1));
        $('#academyModal').modal('show');
    });

    // Tab Switching
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        currentType = $(e.target).data('type');
        let singular = currentType.slice(0, -1);
        if (currentType === 'classes') singular = 'class';

        $('#addBtnText').text(window.trans['add_new'] + singular);
        $('#formType').val(currentType);

        // Adjust form fields
        if (currentType === 'boards' || currentType === 'subjects') {
            $('.code-group').removeClass('d-none');
            $('#code').prop('required', true);
        } else {
            $('.code-group').addClass('d-none');
            $('#code').prop('required', false);
        }

        if (currentType === 'subjects') {
            $('.subject-extra-fields').removeClass('d-none');
        } else {
            $('.subject-extra-fields').addClass('d-none');
        }

        initTable(currentType);
    });

    // Init Table function
    function initTable(type) {
        $('#customSearch').val('');
        $('#table_list').bootstrapTable('destroy');

        let columns = [];

        let isMobile = window.innerWidth <= 767;

        // Add drag handle for classes and sections (PC only)
        if ((type === 'classes' || type === 'sections') && !isMobile) {
            columns.push({
                field: 'reorder',
                title: '',
                align: 'center',
                valign: 'middle',
                width: '40px',
                class: 'reorder-column',
                formatter: function () {
                    return '<i class="fa fa-bars drag-handle"></i>';
                }
            });
        }

        columns.push({ field: 'id', title: window.trans['id'], sortable: true });
        columns.push({
            field: 'name',
            title: window.trans['name'],
            sortable: true,
            formatter: function (value, row) {
                // Apply custom UI only for subjects
                if (type == 'subjects') {
                    let html = '<div class="d-flex align-items-center"> ' + imageFormatter(row.image) + ' <div class="ms-3"> <h6 class="mb-0">' + row.name + '</h6> <small class="text-muted text-overflow-any"> ' + row.code + ' </small> </div> </div>';

                    return html;
                }

                // Default for other types
                return value;
            }
        });

        if (type === 'boards') {
            columns.push({ field: 'code', title: window.trans['code'], sortable: true });
        }

        if (type === 'subjects') {
            columns.push({ field: 'type', title: window.trans['type'] });
            columns.push({
                field: 'bg_color',
                title: window.trans['color'],
                escape: false,
                formatter: function (value) {
                    if (value) {
                        return '<div style="width:25px; height:25px; border-radius:4px; background-color:' + value + ';"></div>';
                    }
                    return '-';
                }
            });
        }

        columns.push({ field: 'formatted_status', title: window.trans['status'], escape: false });
        columns.push({ field: 'operate', title: window.trans['action'], align: 'center', events: window.actionEvents, escape: false });

        $('#customSearch').attr('placeholder', window.trans['search_in'] + currentType + '...');

        let tableOptions = {
            url: baseUrl + '/academy-setup/' + type + '/show',
            columns: columns,
            uniqueId: 'id',
            sortName: (type === 'classes' || type === 'sections') ? 'sort_order' : 'id',
            sortOrder: (type === 'classes' || type === 'sections') ? 'asc' : 'desc',
            mobileResponsive: true,
            checkOnInit: true
        };

        // Enable reordering for specific types (PC only)
        if ((type === 'classes' || type === 'sections') && !isMobile) {
            tableOptions.reorderableRows = true;
            tableOptions.useRowAttrFunc = true;
            tableOptions.onReorderRow = function (newData) {
                let ids = newData.map(row => row.id);

                $.ajax({
                    url: baseUrl + '/academy-setup/' + type + '/reorder',
                    type: 'POST',
                    data: {
                        ids: ids,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.error) {
                            showErrorToast(response.message);
                            $('#table_list').bootstrapTable('refresh');
                        } else {
                            showSuccessToast(response.message);
                            $('#table_list').bootstrapTable('refresh');
                        }
                    },
                    error: function () {
                        showErrorToast('Failed to update order');
                        $('#table_list').bootstrapTable('refresh');
                    }
                });
            };
        }

        $('#table_list').bootstrapTable(tableOptions);
    }

    // Call init for first tab
    initTable('boards');

    // Create / Update form submission
    $('#academyForm').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        let id = $('#editId').val();
        let type = $('#formType').val();

        let actionUrl = baseUrl + '/academy-setup/' + type;
        if (id) {
            actionUrl = baseUrl + '/academy-setup/' + type + '/' + id;
            formData.append('_method', 'PUT');
        }

        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text(window.trans['Please wait']);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.error) {
                    showErrorToast(response.message);
                } else {
                    showSuccessToast(response.message);
                    $('#academyModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    updateStats(response.stats);
                }
            },
            error: function () {
                showErrorToast('Something went wrong');
            },
            complete: function () {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
