<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? 'Payment successful' : 'Payment status' }} · Quisat</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(180deg, #0b1a3a 0%, #132a57 100%);
            color: #f5f7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            color: #111827;
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
        }
        .icon.success { background: #e8f8ee; color: #16a34a; }
        .icon.pending { background: #fff7e6; color: #d97706; }
        .icon.failed { background: #feecec; color: #dc2626; }
        h1 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        p {
            margin: 0;
            color: #4b5563;
            line-height: 1.55;
            font-size: 15px;
        }
        .meta {
            margin-top: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #f8fafc;
            text-align: left;
            font-size: 13px;
            color: #374151;
        }
        .meta div + div { margin-top: 8px; }
        .meta strong { color: #111827; }
        .hint {
            margin-top: 18px;
            font-size: 13px;
            color: #6b7280;
        }
        .brand {
            margin-top: 22px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="card">
        @if ($success)
            <div class="icon success">✓</div>
            <h1>Payment successful</h1>
            <p>Your card payment was received. You can close this page and return to the Quisat app.</p>
        @elseif ($pending)
            <div class="icon pending">…</div>
            <h1>Payment received</h1>
            <p>We are confirming your payment. Please return to the Quisat app and check your order or registration shortly.</p>
        @else
            <div class="icon failed">!</div>
            <h1>Payment not completed</h1>
            <p>{{ $message ?: 'Your card payment could not be completed. No charge was confirmed on Quisat.' }}</p>
        @endif

        @if ($reference || $amount || $tranId)
            <div class="meta">
                @if ($amount)
                    <div><strong>Amount:</strong> {{ number_format($amount) }} {{ $currency }}</div>
                @endif
                @if ($reference)
                    <div><strong>Reference:</strong> {{ $reference }}</div>
                @endif
                @if ($tranId)
                    <div><strong>Transaction ID:</strong> {{ $tranId }}</div>
                @endif
            </div>
        @endif

        <p class="hint">
            If the app still shows payment as pending after a few minutes, contact support with the reference above.
        </p>

        <div class="brand">Quisat</div>
    </div>
</body>
</html>
