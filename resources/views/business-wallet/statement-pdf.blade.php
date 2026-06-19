<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 24px;
        }
        .header {
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #1d4ed8;
            margin: 0 0 4px;
        }
        .subtitle {
            color: #6b7280;
            margin: 0;
            font-size: 12px;
        }
        .meta-grid {
            width: 100%;
            margin-bottom: 18px;
        }
        .meta-grid td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-line { margin: 0 0 4px; }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            width: 25%;
        }
        .summary-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
        .entries-table {
            width: 100%;
            border-collapse: collapse;
        }
        .entries-table th {
            background: #111827;
            color: #fff;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .entries-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
        }
        .entries-table tr:nth-child(even) td {
            background: #f9fafb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .credit { color: #047857; font-weight: bold; }
        .debit { color: #b91c1c; font-weight: bold; }
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.5;
        }
        .empty {
            text-align: center;
            color: #6b7280;
            padding: 24px 0;
        }
    </style>
</head>
<body>
    @php
        $currency = $statement['currency'];
        $fmt = fn ($amount) => $currency.' '.number_format((float) $amount, 0);
    @endphp

    <div class="header">
        <p class="brand">{{ config('app.name') }}</p>
        <p class="subtitle">Business Account Statement</p>
    </div>

    <table class="meta-grid">
        <tr>
            <td>
                <p class="section-title">Account Holder</p>
                <p class="info-line"><strong>{{ $statement['business']->name }}</strong></p>
                <p class="info-line">{{ $statement['business']->email }}</p>
                <p class="info-line">{{ $statement['business']->phone }}</p>
                <p class="info-line">Account No: {{ $statement['business']->account_number }}</p>
            </td>
            <td>
                <p class="section-title">Statement Details</p>
                <p class="info-line"><strong>Statement No:</strong> {{ $statement['statement_number'] }}</p>
                <p class="info-line"><strong>Period:</strong> {{ $statement['from']->format('M j, Y') }} – {{ $statement['to']->format('M j, Y') }}</p>
                <p class="info-line"><strong>Generated:</strong> {{ $statement['generated_at']->format('M j, Y H:i') }}</p>
                <p class="info-line"><strong>Currency:</strong> {{ $currency }}</p>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Opening Balance</div>
                <div class="summary-value">{{ $fmt($statement['opening_balance']) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Credits</div>
                <div class="summary-value credit">{{ $fmt($statement['total_credits']) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Debits & Fees</div>
                <div class="summary-value debit">{{ $fmt($statement['total_debits'] + $statement['total_fees']) }}</div>
            </td>
            <td>
                <div class="summary-label">Closing Balance</div>
                <div class="summary-value">{{ $fmt($statement['closing_balance']) }}</div>
            </td>
        </tr>
    </table>

    <p class="section-title">Transaction History</p>

    @if ($statement['lines']->isEmpty())
        <p class="empty">No transactions recorded for this period.</p>
    @else
        <table class="entries-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Date</th>
                    <th style="width: 18%;">Reference</th>
                    <th>Description</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 12%;" class="text-right">Credit</th>
                    <th style="width: 12%;" class="text-right">Debit</th>
                    <th style="width: 14%;" class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statement['lines'] as $line)
                    <tr>
                        <td>{{ $line['date']->format('M j, Y') }}<br><span style="color:#6b7280;">{{ $line['date']->format('H:i') }}</span></td>
                        <td style="font-size:9px; word-break:break-all;">{{ $line['reference'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td>{{ $line['type'] }}</td>
                        <td class="text-right credit">{{ $line['credit'] !== null ? $fmt($line['credit']) : '—' }}</td>
                        <td class="text-right debit">{{ $line['debit'] !== null ? $fmt($line['debit']) : '—' }}</td>
                        <td class="text-right">{{ $fmt($line['balance_after']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This is a computer-generated account statement from {{ config('app.name') }} and does not require a signature.</p>
        <p>Total lifetime balance received: {{ $fmt($statement['total_balance']) }} · Current available balance: {{ $fmt($statement['available_balance']) }}</p>
        <p>For support, contact {{ config('mail.from.address', 'support@quisat.com') }}.</p>
    </div>
</body>
</html>
