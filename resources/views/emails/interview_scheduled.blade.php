<!DOCTYPE html>
<html>
<head>
    <title>Interview Scheduled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
        <h2>Interview Invitation</h2>
        <p>Dear {{ $application->name }},</p>
        <p>Thank you for applying for the Teacher position at {{ env('APP_NAME', 'our school') }}.</p>
        <p>We are pleased to invite you for an interview. Please find the details below:</p>
        
        <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Date</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ date('d M Y', strtotime($interview->interview_date)) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Time</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ date('h:i A', strtotime($interview->time)) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Venue</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $interview->location }}</td>
            </tr>
            @if(!empty($interview->instructions))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Instructions</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{!! nl2br(e($interview->instructions)) !!}</td>
            </tr>
            @endif
        </table>

        <p>Please confirm your availability. We look forward to meeting you.</p>
        <p>Best regards,<br>{{ env('APP_NAME', 'School Administration') }}</p>
    </div>
</body>
</html>
