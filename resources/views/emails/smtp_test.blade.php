<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>SMTP Connection Verified</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Base Resets */
        table, td, div, h1, p { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; }
        body { margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased; background-color: #f4f5f7; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        
        /* Responsive utilities */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 10px !important; }
            .content-box { padding: 30px 20px !important; }
            .header-title { font-size: 20px !important; }
        }
    </style>
</head>
<body style="background-color: #f4f5f7; margin: 0; padding: 0;">

    <!-- Main Wrapper -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f4f5f7; width: 100%; height: 100%;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                
                <!-- Email Container -->
                <table role="presentation" class="email-container" width="600" border="0" cellspacing="0" cellpadding="0" style="width: 600px; max-width: 600px; margin: 0 auto;">
                    
                    <!-- Header/Logo Area (Optional) -->
                    <tr>
                        <td align="center" style="padding-bottom: 20px;">
                            <h2 style="margin: 0; color: #6b7280; font-size: 18px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                {{ config('app.name', 'System Admin') }}
                            </h2>
                        </td>
                    </tr>

                    <!-- Main Content Card -->
                    <tr>
                        <td class="content-box" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); padding: 40px;">
                            
                            <!-- Success Icon -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 24px;">
                                        <div style="background-color: #d1fae5; width: 64px; height: 64px; border-radius: 50%; display: inline-block; text-align: center; line-height: 64px;">
                                            <!-- Fallback checkmark using character for max email compatibility -->
                                            <span style="color: #10b981; font-size: 32px; font-weight: bold;">&#10003;</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <h1 class="header-title" style="margin: 0 0 16px 0; color: #111827; font-size: 24px; font-weight: 700; text-align: center;">
                                Connection Verified Successfully
                            </h1>

                            <!-- Description -->
                            <p style="margin: 0 0 24px 0; color: #4b5563; font-size: 16px; line-height: 24px; text-align: center;">
                                Great news! Your SMTP credentials have been successfully configured and verified. Your system is now fully capable of sending outbound emails.
                            </p>

                            <!-- Details Box -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin: 0 0 8px 0; color: #374151; font-size: 14px;">
                                            <strong>Test Performed:</strong> SMTP Outbound Connection
                                        </p>
                                        <p style="margin: 0; color: #374151; font-size: 14px;">
                                            <strong>Timestamp:</strong> {{ now()->format('F j, Y, g:i a') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Subtext -->
                            <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 20px; text-align: center;">
                                This is a system-generated test email. You can safely ignore or delete this message. If you did not initiate this test, please check your system logs.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 24px;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 18px;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Your Company') }}. All rights reserved.<br>
                                This email was sent automatically from your application.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>