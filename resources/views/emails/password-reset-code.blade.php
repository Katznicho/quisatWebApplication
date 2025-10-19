<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #007AFF;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .code-container {
            background-color: #f8f9fa;
            border: 2px dashed #007AFF;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            color: #007AFF;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .expiry {
            font-size: 14px;
            color: #999;
            margin-top: 15px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #e1e5e9;
        }
        .footer a {
            color: #007AFF;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Code</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Hello {{ $userName }},</p>
            
            <p class="message">
                We received a request to reset your password for your Quisat account. 
                Use the verification code below to complete the password reset process.
            </p>
            
            <div class="code-container">
                <p class="code-label">Your Verification Code</p>
                <p class="code">{{ $code }}</p>
                <p class="expiry">This code will expire in 10 minutes</p>
            </div>
            
            <p class="message">
                Enter this code in the mobile app to verify your identity and set a new password.
            </p>
            
            <div class="warning">
                <p><strong>Security Notice:</strong> If you didn't request this password reset, please ignore this email or contact support if you have concerns about your account security.</p>
            </div>
        </div>
        
        <div class="footer">
            <p>
                Need help? Contact us at 
                <a href="mailto:no-reply@quisat.com">no-reply@quisat.com</a>
            </p>
            <p style="margin-top: 10px; color: #999; font-size: 12px;">
                © {{ date('Y') }} Quisat. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

