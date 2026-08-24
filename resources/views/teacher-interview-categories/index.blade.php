@extends('layouts.master')

@section('title')
    {{ __('Teacher Interview Categories') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interview Categories') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Create New Category') }}
                        </h4>
                        
                        <form action="{{ route('teacher-interview-categories.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Category Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ __('Category Name') }}" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Description') }}</label>
                                    <input type="text" name="description" class="form-control" placeholder="{{ __('Description') }}">
                                </div>
                                <div class="form-group col-sm-12 col-md-2">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control" required>
                                        <option value="1">{{ __('Active') }}</option>
                                        <option value="0">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-2 d-flex align-items-center mt-3">
                                    <button type="submit" class="btn btn-primary theme-btn btn-block">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Categories List') }}
                        </h4>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('teacher-interview-categories.index') }}"
                                       data-click-to-select="true" data-side-pagination="server"
                                       data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                       data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                                       data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2"
                                       data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                                       data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                       data-export-data-type='all' data-export-options='{ "fileName": "teacher-interview-categories-list-<?= date('d-m-y') ?>" }'
                                       data-query-params="queryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"> {{ __('id') }} </th>
                                        <th scope="col" data-field="no"> {{ __('no.') }} </th>
                                        <th scope="col" data-field="name" data-sortable="true">{{ __('Category Name') }} </th>
                                        <th scope="col" data-field="description" data-sortable="true">{{ __('Description') }} </th>
                                        <th scope="col" data-field="status_badge" data-escape="false">{{ __('Status') }} </th>
                                        <th data-events="categoryEvents" data-width="150" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- Edit Modals (Iterated for forms to work) -->
                        @foreach($categories as $category)
                            <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('Edit Category') }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('teacher-interview-categories.update', $category->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>{{ __('Category Name') }} <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ __('Description') }}</label>
                                                    <input type="text" name="description" class="form-control" value="{{ $category->description }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ __('Status') }}</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="1" {{ $category->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                                        <option value="0" {{ $category->status == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                                                <button type="submit" class="btn btn-primary theme-btn">{{ __('Update') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
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
    
    window.categoryEvents = {
        'click .edit-btn': function (e, value, row, index) {
            let id = row.id;
            $('#editModal' + id).modal('show');
        }
    };
</script>
@endsection
