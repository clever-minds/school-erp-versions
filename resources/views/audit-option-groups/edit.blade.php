@extends('layouts.master')

@section('title', 'Edit Option Group')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title"> Edit Option Group </h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('audit-option-groups.index') }}">Audit Option Groups</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Edit Option Group</h4>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="pt-3" action="{{ route('audit-option-groups.update', $auditOptionGroup->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-6">
                                <label>Group Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $auditOptionGroup->name) }}" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-12">
                                <label>Options (JSON format) <span class="text-danger">*</span></label>
                                <textarea name="option_values" class="form-control" rows="10" required>{{ old('option_values', json_encode($auditOptionGroup->option_values, JSON_PRETTY_PRINT)) }}</textarea>
                                <small class="text-muted">Must be valid JSON array.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-theme">Submit</button>
                        <a href="{{ route('audit-option-groups.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
