@extends('layouts.master')

@section('title', 'Audit Option Groups')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title"> Audit Option Groups </h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Audit Option Groups</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Option Groups
                        @can('audit-option-group-create')
                            <a href="{{ route('audit-option-groups.create') }}" class="btn btn-theme btn-sm float-right">Add New</a>
                        @endcan
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Options</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($optionGroups as $group)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $group->name }}</td>
                                    <td>
                                        <pre style="max-width: 300px; white-space: pre-wrap; font-size: 11px;">{{ json_encode($group->option_values, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                    <td>
                                        @can('audit-option-group-edit')
                                        <a href="{{ route('audit-option-groups.edit', $group->id) }}" class="btn btn-info btn-sm">Edit</a>
                                        @endcan
                                        @can('audit-option-group-delete')
                                        <form action="{{ route('audit-option-groups.destroy', $group->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this option group?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
