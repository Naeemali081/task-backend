<!DOCTYPE html>
<html>
<head>
    <title>New Message in {{ $projectName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F9FAFB; font-family: Arial, sans-serif; color: #333;">
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #F9FAFB; padding: 20px 0;">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" border="0" width="600px" align="center" style="background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                <!-- Header -->
                <tr>
                    <td style="background-color: #FFD900; padding: 20px; text-align: center; color: #333;">
                        <h1 style="margin: 0; font-size: 20px; font-weight: 600;">New Message in {{ $projectName }}</h1>
                    </td>
                </tr>
                <!-- Content -->
                <tr>
                    <td style="padding: 20px; text-align: left;">
                        <p style="font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                            Hi <strong>{{ $recipientName }}</strong>,
                        </p>
                        <p style="font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                            You have a new message in the <strong>{{ $projectName }}</strong> project:
                        </p>
                        <div style="background-color: #F4F4F6; padding: 15px; border-radius: 8px; font-size: 14px; color: #555; margin-bottom: 20px;">
                            <strong>Message from {{ $senderName }}:</strong>
                            <p style="margin: 0; font-style: italic;">
                                "{{ $messagePreview }}"
                            </p>
                        </div>
                        <p style="font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                            To view the full message and respond, please log in to the project platform:
                        </p>
                        <p style="text-align: center;">
                            <a href="{{ $link }}" style="display: inline-block; text-decoration: none; padding: 12px 20px; background-color: #FFD900; color: #333; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                View Message
                            </a>
                        </p>
                    </td>
                </tr>
                <!-- Footer -->
                <tr>
                    <td style="background-color: #F9FAFB; padding: 20px; text-align: center; font-size: 14px; color: #888;">
                        <p style="margin: 0;">
                            If you have any questions, feel free to reach out.
                        </p>
                        <p style="margin: 10px 0 0;">Best regards,</p>
                        <p style="margin: 0;"><strong>{{ $senderName }}</strong></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
