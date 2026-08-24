<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ __('Offer Letter') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; padding-top: 40px; }
        .offer-card { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .header { text-align: center; border-bottom: 2px solid #56cc99; padding-bottom: 20px; margin-bottom: 30px; }
        .details-table th { width: 35%; background: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="offer-card">
                <div class="header">
                    <h2>{{ $application->school->name ?? config('app.name') }}</h2>
                    <h4 class="text-muted">{{ __('Employment Offer Letter') }}</h4>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <p>Dear <strong>{{ $application->name }}</strong>,</p>
                <p>We are thrilled to offer you the position at our school. Please review the details of your offer below:</p>

                <table class="table table-bordered details-table mt-4 mb-4">
                    <tbody>
                        <tr><th>{{ __('Designation') }}</th><td>{{ $offer->designation }}</td></tr>
                        <tr><th>{{ __('Department') }}</th><td>{{ $offer->department ?? 'N/A' }}</td></tr>
                        <tr><th>{{ __('Salary') }}</th><td>{{ number_format($offer->salary, 2) }} / Month</td></tr>
                        <tr><th>{{ __('Joining Date') }}</th><td>{{ \Carbon\Carbon::parse($offer->joining_date)->format('d M, Y') }}</td></tr>
                        <tr><th>{{ __('Reporting Time') }}</th><td>{{ \Carbon\Carbon::parse($offer->reporting_time)->format('h:i A') }}</td></tr>
                        <tr><th>{{ __('Job Location') }}</th><td>{{ $offer->job_location }}</td></tr>
                    </tbody>
                </table>

                <p>If you accept this offer, please click the button below to complete your registration and set up your teacher account.</p>

                @if(in_array($offer->status, ['Sent', 'Prepared']))
                    <form action="{{ route('career.offer-letter.action', $token) }}" method="POST" class="d-flex justify-content-center mt-5">
                        @csrf
                        <button type="submit" name="action" value="accept" class="btn btn-success btn-lg me-3 px-5">{{ __('Accept Offer') }}</button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-lg px-5" onclick="return confirm('Are you sure you want to reject this offer?');">{{ __('Reject Offer') }}</button>
                    </form>
                @else
                    <div class="alert alert-info text-center mt-4">
                        {{ __('You have already :status this offer.', ['status' => strtolower($offer->status)]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
