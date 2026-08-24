<!DOCTYPE html>
<html>
<head>
    <title>Offer Letter</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #56cc99; padding-bottom: 20px; margin-bottom: 30px; }
        .school-name { font-size: 24px; font-weight: bold; color: #56cc99; margin-bottom: 5px; }
        .title { font-size: 18px; color: #666; }
        .content { margin: 0 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; width: 35%; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $application->school->name ?? config('app.name') }}</div>
        <div class="title">Employment Offer Letter</div>
    </div>
    
    <div class="content">
        <p>Dear <strong>{{ $application->name }}</strong>,</p>
        <p>Congratulations! We are pleased to offer you the position at our school. Please find the details of your offer below:</p>

        <table>
            <tbody>
                <tr>
                    <th>Designation</th>
                    <td>{{ $offer->designation }}</td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td>{{ $offer->department ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Salary (Per Month)</th>
                    <td>Rs. {{ number_format($offer->salary, 2) }}</td>
                </tr>
                <tr>
                    <th>Joining Date</th>
                    <td>{{ \Carbon\Carbon::parse($offer->joining_date)->format('d M, Y') }}</td>
                </tr>
                <tr>
                    <th>Reporting Time</th>
                    <td>{{ \Carbon\Carbon::parse($offer->reporting_time)->format('h:i A') }}</td>
                </tr>
                <tr>
                    <th>Job Location</th>
                    <td>{{ $offer->job_location }}</td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top: 30px;">To accept this offer and complete your registration, please click the secure link provided in your email.</p>
        
        <p style="margin-top: 40px;">Sincerely,</p>
        <p><strong>Human Resources</strong><br>{{ $application->school->name ?? config('app.name') }}</p>
    </div>

    <div class="footer">
        This is an electronically generated document and does not require a physical signature.
    </div>
</body>
</html>
