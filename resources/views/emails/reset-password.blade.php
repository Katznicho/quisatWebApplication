<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Stabex Customer Portal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h1 {
            color: #0e2382;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #0e2382;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #0e2382;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Your Password</h1>
        </div>
        
        <p>Hello {{ $user->name }},</p>
        
        <p>We received a request to reset your password for your Stabex Customer Portal account. Click the button below to reset your password:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </div>
        
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #0e2382;">{{ $resetUrl }}</p>
        
        <div class="info-box">
            <strong>Note:</strong> This password reset link will expire in 60 minutes. If you did not request a password reset, please ignore this email.
        </div>
        
        <p>If you have any questions, please contact our support team.</p>
        
        <p>Best regards,<br>Stabex Customer Portal Team</p>
        
        <div class="footer">
            <p>This is an automated message from Stabex Customer Portal. Please do not reply to this email.</p>
            <p>© {{ date('Y') }} Stabex. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

