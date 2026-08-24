<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manual UPI Transactions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            padding: 0;
            color: #000;
        }
        .date-range {
            text-align: center;
            margin-bottom: 20px;
            font-style: italic;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Manual UPI Transactions</h2>
    </div>

    @if(request('date') || request('name'))
        <div class="date-range">
            Filtered by: 
            @if(request('date')) Date - {{ request('date') }} @endif
            @if(request('date') && request('name')) | @endif
            @if(request('name')) Name - {{ request('name') }} @endif
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Amount</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->id }}</td>
                    <td>{{ $transaction->student->user->first_name ?? '' }} {{ $transaction->student->user->last_name ?? '' }}</td>
                    <td>{{ $transaction->amount }}</td>
                    <td>{{ $transaction->transaction_id }}</td>
                    <td>{{ ucfirst($transaction->status) }}</td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-center">No transactions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
