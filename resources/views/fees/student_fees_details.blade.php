@extends('layouts.master')

@section('title')
    {{ __('Student Fees Details') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Student Fees Details') }}
            </h3>
            <a href="{{ route('fees.paid.index') }}" class="btn btn-sm btn-gradient-primary">
                <i class="fa fa-arrow-left"></i> {{ __('Back') }}
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>{{ __('Student Name') }}: <span class="text-muted">{{ $student->first_name }} {{ $student->last_name }}</span></h5>
                                <h5>{{ __('Class') }}: <span class="text-muted">{{ $student->student->class_section->class->name }} - {{ $student->student->class_section->section->name }}</span></h5>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Fees Name') }}</th>
                                        <th>{{ __('Due Date') }}</th>
                                        <th>{{ __('Total Fees') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fees as $fee)
                                        @php
                                            $feesPaid = $fee->fees_paid->first();
                                            $status = '<span class="badge badge-danger">Unpaid</span>';
                                            if ($feesPaid) {
                                                if ($feesPaid->is_fully_paid) {
                                                    $status = '<span class="badge badge-success">Fully Paid</span>';
                                                } else {
                                                    $status = '<span class="badge badge-warning">Partial Paid</span>';
                                                }
                                            } else if (Carbon\Carbon::parse($fee->due_date)->lt(Carbon\Carbon::now())) {
                                                $status = '<span class="badge badge-danger">Overdue</span>';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $fee->name }}</td>
                                            <td>{{ $fee->due_date }}</td>
                                            <td>{{ $fee->total_compulsory_fees }}</td>
                                            <td>{!! $status !!}</td>
                                            <td>
                                                <a href="{{ route('fees.compulsory.index', [$fee->id, $student->id]) }}" class="btn btn-sm btn-gradient-success mb-1" title="{{ __('Compulsory Fees') }}">
                                                    <i class="fa fa-dollar"></i> {{ __('Pay Fees') }}
                                                </a>

                                                @if($feesPaid)
                                                    <a href="{{ route('fees.paid.receipt.pdf', $feesPaid->id) }}" target="_blank" class="btn btn-sm btn-gradient-primary mb-1" title="{{ __('generate_pdf') }}">
                                                        <i class="fa fa-file-pdf-o"></i> {{ __('Receipt') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if(count($fees) == 0)
                                        <tr>
                                            <td colspan="5" class="text-center">{{ __('No Fees Assigned') }}</td>
                                        </tr>

                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
