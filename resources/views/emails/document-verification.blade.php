<!DOCTYPE html>
<html>
<head>
    <title>Document Upload for Verification</title>
</head>
<body>
    <h2>Hello {{ $application->name }},</h2>
    <p>Congratulations! You have successfully cleared the interview and demo class for the Teacher position.</p>
    
    <p>As the next step in our recruitment process, we require you to upload the following documents for verification:</p>
    <ul>
        <li>Identity Proof (Aadhaar/PAN)</li>
        <li>Experience Letter(s)</li>
        <li>Degree / Final Year Mark Sheet (Higher Education)</li>
    </ul>

    <p>Please click the link below to access the secure document upload portal. This link is unique to you and does not require a login.</p>
    
    <p>
        <a href="{{ url('/career/document-upload/' . $application->document_upload_token) }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Upload Documents</a>
    </p>

    <p>If the button above doesn't work, copy and paste this URL into your browser:<br>
    {{ url('/career/document-upload/' . $application->document_upload_token) }}</p>

    <p><strong>Important:</strong> You must upload these documents online before <strong>{{ \Carbon\Carbon::parse($application->document_verification_date)->subDay()->format('d M, Y') }}</strong>. <br>
    Additionally, please visit the school campus on <strong>{{ \Carbon\Carbon::parse($application->document_verification_date)->format('d M, Y') }} at {{ \Carbon\Carbon::parse($application->document_verification_time)->format('h:i A') }}</strong> for physical cross-verification of your original documents.</p>
    
    <p>Best Regards,<br>
    {{ env('APP_NAME', 'School Administration') }}</p>
</body>
</html>
