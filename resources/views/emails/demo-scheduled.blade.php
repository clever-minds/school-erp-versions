<!DOCTYPE html>
<html>
<head>
    <title>Demo Class Scheduled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
        <h2>Demo Class Invitation</h2>
        <p>Dear {{ $application->name }},</p>
        <p>Thank you for your interest in the Teacher position at {{ env('APP_NAME', 'our school') }}.</p>
        <p>We are pleased to invite you to conduct a Demo Class. Please find the details below:</p>
        
        <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
            @if(!empty($demoClass->subject))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $demoClass->subject }}</td>
            </tr>
            @endif
            @if(!empty($demoClass->class_name))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Class</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $demoClass->class_name }}</td>
            </tr>
            @endif
            @if(!empty($demoClass->date))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Date</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ date('d M Y', strtotime($demoClass->date)) }}</td>
            </tr>
            @endif
            @if(!empty($demoClass->time))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Time</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ date('h:i A', strtotime($demoClass->time)) }}</td>
            </tr>
            @endif
            @if(!empty($demoClass->location))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Venue</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $demoClass->location }}</td>
            </tr>
            @endif
            @if(!empty($demoClass->instructions))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Instructions</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{!! nl2br(e($demoClass->instructions)) !!}</td>
            </tr>
            @endif
        </table>

        <p>Please confirm your availability and come prepared. We look forward to your demo session.</p>
        <p>Best regards,<br>{{ env('APP_NAME', 'School Administration') }}</p>
    </div>
</body>
</html>
