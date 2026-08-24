@extends('layouts.master')

@section('title')
{{ __('school_policy') }}
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('school_policy') }}
        </h3>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        {{ __('create') . ' ' . __('school_policy') }}
                    </h4>
                    <form class="create-form pt-3" id="create-form" action="{{ route('school-policy.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-6">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                <input name="title" required placeholder="{{ __('title') }}" class="form-control" type="text">
                            </div>
                            <div class="form-group col-sm-12 col-md-6">
                                <label>{{ __('file') }}</label>
                                <input type="file" name="file" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label>{{ __('description') }}</label>
                                {!! Form::textarea('description', null, ['rows' => '2', 'placeholder' => __('description'), 'class' => 'form-control']) !!}
                            </div>
                        </div>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        {{ __('list') . ' ' . __('school_policy') }}
                    </h4>
                    <div class="row">
                        <div class="col-12">
                            <table class="table" id="table_list" data-toggle="table" data-url="{{ route('school-policy.list') }}" data-pagination="true" data-search="true" data-side-pagination="server" data-show-columns="true" data-show-refresh="true" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                        <th data-field="no">{{ __('no.') }}</th>
                                        <th data-field="title">{{ __('title') }}</th>
                                        <th data-field="description" data-formatter="descriptionFormatter">{{ __('description') }}</th>
                                        <th data-field="file_url" data-formatter="fileFormatter">{{ __('file') }}</th>
                                        <th data-field="operate" data-events="actionEvents">{{ __('action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('school_policy') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="edit-form" class="edit-form" action="{{ url('school-policy') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="form-group">
                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                        <input name="title" required placeholder="{{ __('title') }}" class="form-control" type="text" id="edit-title">
                    </div>
                    <div class="form-group">
                        <label>{{ __('description') }}</label>
                        {!! Form::textarea('description', null, ['rows' => '2', 'placeholder' => __('description'), 'class' => 'form-control', 'id' => 'edit-description']) !!}
                    </div>
                    <div class="form-group">
                        <label>{{ __('file') }}</label>
                        <input type="file" name="file" class="form-control">
                        <small class="text-info">{{ __('leave_empty_if_no_change') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                    <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    window.actionEvents = {
        'click .edit-data': function (e, value, row, index) {
            $('#edit-id').val(row.id);
            $('#edit-title').val(row.title);
            $('#edit-description').val(row.description);
            $('#edit-form').attr('action', baseUrl + '/school-policy/' + row.id);
        }
    };

    function fileFormatter(value, row) {
        if (value) {
            return '<a href="' + value + '" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-download"></i> View</a>';
        }
        return '-';
    }
</script>
@endsection
