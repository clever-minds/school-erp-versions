@extends('layouts.master')

@section('title')
    {{ __('Manual UPI Transactions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manual UPI Transactions') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('manual_upi_transactions.index') }}" method="GET" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label>{{ __('Date') }}</label>
                                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('Student Name') }}</label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ __('Search by Name') }}" value="{{ request('name') }}">
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <button type="submit" class="btn btn-theme mr-2">{{ __('Filter') }}</button>
                                    <a href="{{ route('manual_upi_transactions.index') }}" class="btn btn-secondary mr-2">{{ __('Clear') }}</a>
                                    <button type="submit" name="export" value="pdf" class="btn btn-danger mr-2"><i class="fa fa-file-pdf-o"></i> {{ __('PDF') }}</button>
                                    <button type="submit" name="export" value="csv" class="btn btn-success"><i class="fa fa-file-excel-o"></i> {{ __('CSV') }}</button>
                                </div>
                            </div>
                        </form>
                        <h4 class="card-title">{{ __('Transaction List') }}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Student Name') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Transaction ID') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td>{{ $transaction->student->user->first_name ?? '' }} {{ $transaction->student->user->last_name ?? '' }}</td>
                                        <td>{{ $transaction->amount }}</td>
                                        <td>{{ $transaction->transaction_id }}</td>
                                        <td>
                                            @if($transaction->status == 'pending')
                                                <label class="badge badge-warning">{{ __('Pending') }}</label>
                                            @elseif($transaction->status == 'success')
                                                <label class="badge badge-success">{{ __('Success') }}</label>
                                            @elseif($transaction->status == 'failed')
                                                <label class="badge badge-danger">{{ __('Failed') }}</label>
                                            @else
                                                <label class="badge badge-info">{{ ucfirst($transaction->status) }}</label>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if($transaction->status == 'pending')
                                                <form action="{{ route('manual_upi_transactions.accept', $transaction->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Accept') }}</button>
                                                </form>
                                                <form action="{{ route('manual_upi_transactions.reject', $transaction->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
                                                </form>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No transactions found.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
