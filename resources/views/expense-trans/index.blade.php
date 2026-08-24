@extends('layouts.master')

@section('title')
    {{ __('manage_expense') }}
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('expense') }}
        </h3>
    </div>

    <div class="row">
        {{-- ================= Today’s Summary Cards ================= --}}
        <!-- <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('today_summary') }}</h4>

                    <div class="row text-center">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="p-3 bg-success text-white rounded">
                                <p class="mb-1">{{ __('opening_balance') }}</p>
                                <h4>₹{{ number_format($openingBalance, 2) }}</h4>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="p-3 bg-primary text-white rounded">
                                <p class="mb-1">{{ __('total_credit') }}</p>
                                <h4>₹{{ number_format($todayCredits, 2) }}</h4>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="p-3 bg-danger text-white rounded">
                                <p class="mb-1">{{ __('total_debit') }}</p>
                                <h4>₹{{ number_format($todayDebits, 2) }}</h4>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="p-3 bg-warning text-dark rounded">
                                <p class="mb-1">{{ __('closing_balance') }}</p>
                                <h4>₹{{ number_format($closingBalance, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">{{ __('today_summary') }}</h4>

                    <input type="date" id="filter-date" class="form-control w-auto"
                        value="{{ now()->toDateString() }}">
                </div>

                <div class="row text-center" id="summary-section">
                    <div class="col-md-12 text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"></span>
                    </div>
                    <p class="mt-2 mb-0"></p>
                    </div>
                </div>
                </div>
            </div>
            </div>


        {{-- ================= Add Transaction Form ================= --}}
        @canany(['manage-expense-add'])
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('add_amount') }}</h4>

                        <form class="pt-3" id="add-amount-form" 
                            action="{{ route('expense-trans.store') }}" 
                            method="POST" 
                            novalidate="novalidate" 
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">

                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="title">{{ __('title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="{{ __('title') }}" required>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="amount">{{ __('amount') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" class="form-control" placeholder="{{ __('amount') }}" step="0.01" required>
                                </div>
                                 <div class="form-group col-sm-12 col-md-4">
                                <label for="date">{{ __('date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('date', null, ['required', 'placeholder' => __('date'), 'class' => 'datepicker-popup form-control', 'id' => 'date','data-date-end-date'=>"0d"]) !!}
                                    <span class="input-group-addon input-group-append"></span>
                                </div>
                                <div class="form-group col-sm-12 col-md-12">
                                    <label for="description">{{ __('description') }}</label>
                                    <textarea name="description" id="description" class="form-control" placeholder="{{ __('description') }}"></textarea>
                                </div>
                               

                            </div>

                            <div class="form-actions float-right">
                                <input type="submit" id="add-btn" class="btn btn-theme ml-3" value="{{ __('submit') }}">
                                <input type="reset" class="btn btn-secondary" value="{{ __('reset') }}">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcanany
     <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('expense') }}
                        </h4>
                        <form class="pt-3" id="create-form" action="{{ route('expense.store') }}" method="POST" novalidate="novalidate" enctype="multipart/form-data">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('select') }} {{ __('category') }} <span class="text-danger">*</span></label>
                                    {!! Form::select('category_id', $expenseCategory, null, ['required','class' => 'form-control','placeholder' => __('select') .' '. __('category')]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="title">{{ __('title') }} <span class="text-danger">*</span></label>
                                    <input name="title" id="title" type="text" placeholder="{{ __('title') }}" class="form-control" required/>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="amount">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                    <input name="amount" id="amount" type="number" placeholder="{{ __('Amount') }}" class="form-control" required/>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="date">{{ __('date') }} <span class="text-danger">*</span></label>
                                    <input name="date" id="date" type="text" placeholder="{{ __('date') }}" class="datepicker-popup-no-future form-control" autocomplete="off" required/>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <label for="ref_no">{{ __('reference_no') }}</label>
                                    <input name="ref_no" id="ref_no" type="text" placeholder="{{ __('reference_no') }}" class="form-control"/>
                                </div>
                                  <div class="form-group col-sm-12 col-md-3">
                                    <label for="">{{ __('select') }} {{ __('session_year') }}</label>
                                    {!! Form::select('session_year_id', $sessionYear, $current_session_year->id, ['required','class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="description">{{ __('description') }} </label>
                                    <textarea name="description" id="description" placeholder="{{ __('description') }}" class="form-control"></textarea>
                                </div>

                              

                            </div>
                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                                <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>
        {{-- ================= Transactions List ================= --}}
        @canany(['manage-expense-list'])
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('total_transactions') }}</h4>
                          <div class="row mb-3" id="toolbar">

                                <!-- FROM DATE -->
                                <div class="form-group col-sm-12 col-md-4">
                                    <input type="date"
                                        id="filter-date-trans"
                                        class="form-control"
                                        placeholder="From Date">
                                </div>
                                <!-- TO DATE -->
                                <div class="form-group col-sm-12 col-md-4">
                                    <input type="date"
                                        id="filter-date-trans-to"
                                        class="form-control"
                                        placeholder="To Date">
                                </div>
                                <!-- CATEGORY -->
                                <div class="form-group col-sm-12 col-md-4">
                                    {!! Form::select(
                                        'filter-cate',
                                        $expenseCategory,
                                        null,
                                        [
                                            'id' => 'filter-cate',
                                            'class' => 'form-control',
                                            'placeholder' => __('select').' '.__('category')
                                        ]
                                    ) !!}
                                </div>
                            </div>
                    <table aria-describedby="mydesc" class='table' id='table_list'
                        data-toggle="table"
                        data-url="{{ route('expense-trans.today') }}"
                        data-side-pagination="server"
                        data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100,  500, 1000, 2000, 5000, 10000]"
                        data-search="true"
                        data-show-refresh="true"
                        data-show-columns="true"
                        data-toolbar="#toolbar"
                        data-sort-name="transaction_date"
                        data-sort-order="desc"
                        data-export-data-type='all'
                        data-export-options='{ "fileName": "transactions-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                        data-show-export="true"
                        data-show-footer="true"
                        data-escape="true"
                        data-detail-view="true"            
                        data-detail-formatter="detailFormatter">  
                        <thead>
                            <tr>
                                <th data-field="no">{{ __('no.') }}</th>
                                <th data-field="title">{{ __('title') }}</th>
                                <th data-field="category">{{ __('category') }}</th>
                                <th data-field="credit" data-footer-formatter="creditFooter">{{ __('Credit') }}</th>
                                 <th data-field="debit"  data-footer-formatter="debitFooter">{{ __('Debit') }}</th>
                                <th data-field="transaction_date">{{ __('date') }}</th>
                                <th data-field="operate" data-formatter="operateFormatter" data-events="operateEvents">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="edit-form">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="form-group">
            <label>{{ __('Title') }}</label>
            <input type="text" id="edit_title" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{ __('Amount') }}</label>
            <input type="number" id="edit_amount" name="amount"  class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{ __('Description') }}</label>
            <textarea id="edit_description" name="description" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
    @endcanany

@section('js')
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>

    // ✅ Define show_toastr globally
    function show_toastr(type, message, title = '') {
        toastr[type](message, title, {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        });
    }

    // AJAX form submission
    $('#add-amount-form').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                show_toastr('success', '{{ __("amount_added_successfully") }}');
                $('#table_list').bootstrapTable('refresh');
                loadExpenseSummary();  
                form.trigger('reset');
            },
            error: function() {
                show_toastr('error', '{{ __("something_went_wrong") }}');
            }
        });
    });
    function operateFormatter(value, row, index) {
        return `
            <button class="btn btn-sm btn-primary edit"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete"><i class="fa fa-trash"></i></button>
        `;
    
    return '-';
}
window.operateEvents = {
    'click .edit': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_title').val(row.title);
        $('#edit_amount').val( row.amount.replace(/,/g, '') );
        $('#edit_description').val(row.description || '');
        $('#editModal').modal('show');
    },
    'click .delete': function (e, value, row, index) {
        if(confirm('Are you sure?')) {
            $.ajax({
                url:'/expense-trans/delete/' + row.id,
                type:'DELETE',
                headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success:function(){                 
                 loadExpenseSummary();  
                 $('#table_list').bootstrapTable('refresh'); }
            });
        }
    }
};
$('#edit-form').on('submit', function(e) {
    e.preventDefault();

    let id = $('#edit_id').val();
    let title = $('#edit_title').val().trim();
    let amount = $('#edit_amount').val().trim();
    let description = $('#edit_description').val().trim();

    // ✅ Frontend validation
    if (title === '') {
        show_toastr('error', 'Please enter a title');
        $('#edit_title').focus();
        return false;
    }

    if (amount === '' || isNaN(amount)) {
        show_toastr('error', 'Please enter a valid amount');
        $('#edit_amount').focus();
        return false;
    }

    if (description === '') {
        show_toastr('error', 'Please enter a description');
        $('#edit_description').focus();
        return false;
    }

    let formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        _method: 'PUT',
        title: title,
        amount: amount,
        description: description
    };

    $.ajax({
        url: '/expense-trans/update/' + id,
        type: 'POST', // ✅ Use POST when sending _method: 'PUT'
        data: formData,
        success: function(response) {
            show_toastr('success', 'Transaction updated successfully');
            loadExpenseSummary();  
            $('#editModal').modal('hide');
            $('#table_list').bootstrapTable('refresh');
        },
        error: function(xhr) {
            show_toastr('error', 'Something went wrong');
            console.error(xhr.responseText);
        }
    });
});

$('#add-amount-form').on('submit', function(e){
    e.preventDefault();
    
    let id = $('#edit_id').val();
    let formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        title: $('#title').val(),
        amount: $('#amount').val(),
        description: $('#description').val()
    };

$.ajax({
    url: '/expense-trans/store/' + id, // URL route ke hisab se sahi hona chahiye
    type: 'Post',
    data: formData,
    success: function(response){
        show_toastr('success', 'Transaction updated successfully');
        loadExpenseSummary();       
        $('#editModal').modal('hide');
        $('#table_list').bootstrapTable('refresh');
    },
    error: function(){
        show_toastr('error', 'Something went wrong');
    }
});
});
// Bootstrap Table row detail formatter
function detailFormatter(index, row) {
    if (!row.logs || row.logs.length === 0) {
        return '<p class="text-center text-muted">No history available</p>';
    }

    let html = `
       <div class="col-md-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Transection Log</h4>

                <table class="table table-bordered  text-center mb-0"> <!-- Center text -->
                    <thead>
                        <tr>
                            <th class="th-inner">#</th>
                            <th class="th-inner">Updated By</th>
                            <th class="th-inner">Old Amount</th>
                            <th class="th-inner">Description</th>
                            <th class="th-inner">Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

                        row.logs.forEach(function(log, index) {
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${log.updated_by_name || 'System'}</td>
                                    <td>₹${parseFloat(log.amount).toFixed(2)}</td>
                                    <td>${log.description || '-'}</td>
                                    <td>${log.updated_at}</td>
                                </tr>
                            `;
                        });

                        html += `
                    </tbody>
                </table>
            </div></div>
        </div>
    `;

    return html;
}


function changeLogFormatter(value, row, index) {
    if(!row.logs || row.logs.length === 0) return '-';
    return `<button class="btn btn-sm btn-info" data-toggle="collapse" data-target=".detail-view-${row.id}">View</button>`;
}

$('#table_list').bootstrapTable({
      queryParamsType: 'limit',
    queryParams: function (params) {
        params.from_date = $('#filter-date-trans').val();
        params.to_date = $('#filter-date-trans-to').val();
         params.category_id = $('#filter-cate').val();
          var pageSize = $('#table_list').bootstrapTable('getOptions').pageSize;

        // 👇 Export ke time wahi limit bhejo
       if (params.limit === undefined || params.offset === undefined) {
            const opts = $('#table_list').bootstrapTable('getOptions');

            params.limit  = opts.pageSize;                      // selected limit
            params.offset = (opts.pageNumber - 1) * opts.pageSize; // correct offset
        }

        console.log('LIMIT:', params.limit);
        console.log('OFFSET:', params.offset);



        return params;
    }
});

function loadExpenseSummary(date = null) {
    const selectedDate = date || $('#filter-date').val();

    $.ajax({
        url: "{{ route('expenses.ajax-summary') }}",
        type: "GET",
        data: { date: selectedDate },
        beforeSend: function() {
            $('#summary-section').html(`
                <div class="col-md-12 text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"></span>
                    </div>
                </div>
            `);
        },
        success: function(res) {
            if (res.status) {
                $('#summary-section').html(`
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="p-3 bg-success text-white rounded">
                            <p class="mb-1">{{ __('opening_balance') }}</p>
                            <h4>₹${parseFloat(res.openingBalance).toFixed(2)}</h4>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="p-3 bg-primary text-white rounded">
                            <p class="mb-1">{{ __('total_credit') }}</p>
                            <h4>₹${parseFloat(res.todayCredits).toFixed(2)}</h4>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="p-3 bg-danger text-white rounded">
                            <p class="mb-1">{{ __('total_debit') }}</p>
                            <h4>₹${parseFloat(res.todayDebits).toFixed(2)}</h4>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="p-3 bg-warning text-dark rounded">
                            <p class="mb-1">{{ __('closing_balance') }}</p>
                            <h4>₹${parseFloat(res.closingBalance).toFixed(2)}</h4>
                        </div>
                    </div>
                `);
            } else {
                $('#summary-section').html(`<div class="col-md-12"><div class="alert alert-danger">Failed to load data.</div></div>`);
            }
        },
        error: function() {
            $('#summary-section').html(`<div class="col-md-12"><div class="alert alert-danger">Error loading summary.</div></div>`);
        }
    });
}

$(document).ready(function() {
    // Load summary initially
    loadExpenseSummary();

    // Refresh summary when date changes
    $('#filter-date').on('change', function() {
        loadExpenseSummary(this.value);
    });
});
$('#create-form').on('submit', function (e) {
    e.preventDefault();
    loadExpenseSummary();
});
$('#filter-date-trans').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
});
$('#filter-date-trans-to').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
});
$('#filter-date-cate').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
});
</script>
<script>
function creditFooter(data) {
    let total = 0;

    data.forEach(row => {
        total += parseFloat(row.credit.replace(/,/g, '')) || 0;
    });

    return total.toFixed(2);
}

function debitFooter(data) {
    let total = 0;

    data.forEach(row => {
        total += parseFloat(row.debit.replace(/,/g, '')) || 0;
    });

    return  total.toFixed(2) ;
}
$('#exportBtn').on('click', function () {

    let params = $('#table_list').bootstrapTable('getOptions');

    let query = $.param({
        search: params.searchText,
        sort: params.sortName,
        order: params.sortOrder,
        from_date: $('#from_date').val() ?? '',
        to_date: $('#to_date').val() ?? ''
    });

    window.location.href = "{{ route('expense-trans.export') }}?" + query;
});

</script>

@endsection
