@extends('layouts.master')

@section('title')
    {{ __('certificate') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_certificate') . ' ' . __('template') }}
            </h3>
        </div>
        <div class="grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <h4 class="card-title">{{ __('list') . ' ' . __('certificate') }} {{ __('template') }}</h4>
                            
                        </div>
                        <div class="col-sm-12 col-md-6 text-right">
                            <a href="{{ route('certificate-template.create') }}" class="btn btn-theme">{{ __('create_certificate') }}</a>
                        </div>
                            
                    </div>
                    
                    <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table" data-url="{{ route('certificate-template.show',[1]) }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-fixed-columns="false" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-data-type='all' data-query-params="certificateTemplateQueryParams" data-toolbar="#toolbar" data-export-options='{ "fileName": "subject-list-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}' data-show-export="true" data-escape="true">
                        <thead>
                        <tr>
                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                            <th scope="col" data-field="no">{{ __('no.') }}</th>
                            <th scope="col" data-field="name">{{ __('name') }}</th>
                            <th scope="col" data-field="type">{{ __('type') }}</th>
                            <th scope="col" data-field="layout">{{ __('layout') }}</th>
                            <th scope="col" data-field="background_image" data-formatter="imageFormatter">{{ __('background_image') }}</th>
                            <th scope="col" data-field="operate" data-events="certificateTemplateEvents" data-escape="false">{{ __('action') }}</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
