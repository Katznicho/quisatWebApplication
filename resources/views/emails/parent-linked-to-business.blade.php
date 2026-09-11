<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linked to {{ $businessName }}</title>
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
            background-color: #011478;
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
            margin-bottom: 20px;
        }
        .business {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 16px 20px;
            font-size: 18px;
            font-weight: 600;
            color: #011478;
            text-align: center;
            margin: 24px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #e1e5e9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Linked to {{ $businessName }}</h1>
        </div>
        <div class="content">
            <p class="greeting">Hello {{ $parentName }},</p>
            <p class="message">
                You have been linked to this school on Quisat. You can now use the app to view fees, messages, events, and updates for your child.
            </p>
            <div class="business">{{ $businessName }}</div>
            <p class="message">
                Open the Quisat app and sign in with the email address this message was sent to.
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Quisat. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
