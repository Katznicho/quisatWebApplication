<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Balance Alert - GreensWell</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .alert-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .balance-info {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .balance-amount {
            font-size: 32px;
            font-weight: bold;
            color: #ef4444;
            margin: 10px 0;
        }
        .threshold-amount {
            font-size: 18px;
            color: #6b7280;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
        }
        .test-badge {
            background-color: #ef4444;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GreensWell</h1>
        @if($isTest)
        <div class="test-badge">TEST ALERT</div>
        @endif
    </div>
    
    <div class="content">
        <h2>Low Balance Alert</h2>
        
        <p>Hello,</p>
        
        <p>This is a notification that your GreensWell account balance has fallen below your set threshold.</p>
        
        <div class="alert-box">
            <strong>⚠️ Action Required:</strong> Your account balance is low. Please top up to continue sending SMS messages.
        </div>
        
        <div class="balance-info">
            <div style="color: #6b7280; font-size: 14px;">Current Balance</div>
            <div class="balance-amount">{{ number_format($currentBalance, 0) }} UGX</div>
            <div class="threshold-amount">Alert Threshold: {{ number_format($threshold, 0) }} UGX</div>
        </div>
        
        <p><strong>Business:</strong> {{ $business->name }}</p>
        
        @if(!$isTest)
        <p>To continue sending SMS messages, please top up your account balance.</p>
        
        <a href="{{ route('transactions.topup') }}" class="button">Top Up Now</a>
        @else
        <p style="color: #6b7280; font-style: italic;">This is a test alert. Your actual balance is {{ number_format($currentBalance, 0) }} UGX.</p>
        @endif
        
        <p style="margin-top: 30px;">
            If you have any questions or need assistance, please contact our support team.
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from GreensWell.</p>
        <p>You are receiving this because balance alerts are enabled for your account.</p>
    </div>
</body>
</html>



