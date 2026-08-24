@extends('layouts.master')

@section('title')
    {{ __('My Assigned Interviews') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('My Assigned Interviews') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Applications Assigned to Me') }}
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('School') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Applied On') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interviews as $key => $interview)
                                        @php
                                            $application = $interview->application;
                                        @endphp
                                        @if($application)
                                            <tr>
                                                <td>{{ $interviews->firstItem() + $key }}</td>
                                                <td>{{ $application->school->name ?? '-' }}</td>
                                                <td>{{ $application->name }}</td>
                                                <td>{{ $application->email }}</td>
                                                <td>{{ $application->phone }}</td>
                                                <td>{{ $application->created_at->format('d M, Y') }}</td>
                                                <td>
                                                    @if($application->status == 'Pending')
                                                        <span class="badge badge-warning">{{ $application->status }}</span>
                                                    @elseif($application->status == 'Rejected')
                                                        <span class="badge badge-danger">{{ $application->status }}</span>
                                                    @elseif($application->status == 'Hired')
                                                        <span class="badge badge-success">{{ $application->status }}</span>
                                                    @else
                                                        <span class="badge badge-info">{{ $application->status }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('teacher-interviews.show', $application->id) }}" class="btn btn-sm btn-info btn-rounded btn-icon" title="{{ __('View Details & Fill Form') }}">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    @if($application->resume_path)
                                                        <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn btn-sm btn-primary btn-rounded btn-icon" title="{{ __('Download Resume') }}">
                                                            <i class="fa fa-download"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $interviews->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
