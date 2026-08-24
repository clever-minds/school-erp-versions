<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Document Rejected') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>{{ __('Dear') }} {{ $application->first_name }} {{ $application->last_name }},</h2>
    <p>{{ __('Unfortunately, your uploaded document') }} (<strong>{{ $documentName }}</strong>) {{ __('has been rejected during the verification process.') }}</p>
    
    <p><strong>{{ __('Reason / Remarks:') }}</strong></p>
    <blockquote style="border-left: 4px solid #f44336; padding-left: 10px; color: #555; background: #f9f9f9; padding: 10px;">
        {{ $remarks ?? __('No specific remarks provided.') }}
    </blockquote>
    
    <p>{{ __('Please re-upload the correct document by clicking the link below:') }}</p>
    <p style="margin: 20px 0;">
        <a href="{{ route('teacher-interviews.document-upload', $application->document_upload_token) }}" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
            {{ __('Re-upload Document') }}
        </a>
    </p>

    <p>{{ __('Note: This link is valid until') }} {{ \Carbon\Carbon::parse($application->document_upload_token_expires_at)->format('d M, Y H:i') }}.</p>
    
    <p>{{ __('Best Regards,') }}<br>{{ env('APP_NAME', 'School Team') }}</p>
</body>
</html>
