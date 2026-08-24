<!DOCTYPE html>
<html>
<head>
    <title>Employment Offer Letter</title>
</head>
<body>
    <h2>Dear {{ $application->name }},</h2>
    
    <p>Congratulations! We are delighted to offer you the position of <strong>{{ $offer->designation }}</strong> at <strong>{{ $application->school->name ?? config('app.name') }}</strong>.</p>
    
    <p>We were very impressed with your interview and demo class, and we believe your skills and experience will be a great addition to our team.</p>

    <p>Please find your detailed offer letter attached to this email as a PDF document.</p>

    <p>To review the full details online and officially <strong>Accept</strong> or <strong>Reject</strong> this offer, please click the secure link below:</p>
    
    <p style="margin-top: 20px; margin-bottom: 20px;">
        <a href="{{ route('career.offer-letter', $offer->token) }}" style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold;">Review & Respond to Offer</a>
    </p>

    <p>If the button above does not work, you can copy and paste the following URL into your web browser:</p>
    <p><a href="{{ route('career.offer-letter', $offer->token) }}">{{ route('career.offer-letter', $offer->token) }}</a></p>

    <p>We are very excited about the prospect of you joining us. If you have any questions regarding this offer, please do not hesitate to contact us.</p>
    
    <p>Best Regards,<br>
    <strong>Human Resources</strong><br>
    {{ $application->school->name ?? config('app.name') }}</p>
</body>
</html>
