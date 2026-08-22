<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        .stats td { padding: 6px 10px; border: 1px solid #e5e7eb; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data th { background: #111827; color: #fff; text-align: left; padding: 6px; }
        table.data td { border: 1px solid #e5e7eb; padding: 5px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated {{ $generatedAt->format('d M Y H:i') }}</p>
    <table class="stats">
        <tr>
            <td>Records: {{ $stats['count'] }}</td>
            <td>Billed: {{ $currency }} {{ number_format($stats['total_billed'], 0) }}</td>
            <td>Paid: {{ $currency }} {{ number_format($stats['total_paid'], 0) }}</td>
            <td>Pending: {{ $currency }} {{ number_format($stats['total_pending'], 0) }}</td>
            <td>Arrears: {{ $currency }} {{ number_format($stats['arrears'], 0) }}</td>
            <td>Credits: {{ $currency }} {{ number_format($stats['credits'], 0) }}</td>
        </tr>
        @if(!empty($methods))
        <tr>
            @foreach($methods as $label => $total)
                <td>{{ $label }}: {{ $currency }} {{ number_format($total, 0) }}</td>
            @endforeach
        </tr>
        @endif
    </table>
    <table class="data">
        <thead>
            <tr>
                <th>Student</th>
                <th>Parent</th>
                <th>Type</th>
                <th>Term</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Method</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fees as $fee)
                <tr>
                    <td>{{ $fee->student?->full_name }}</td>
                    <td>{{ $fee->student?->parentGuardian?->full_name }}</td>
                    <td>{{ $fee->fee_type }}</td>
                    <td>{{ $fee->displayTerm() }}</td>
                    <td>{{ number_format((float) $fee->amount, 0) }}</td>
                    <td>{{ number_format((float) $fee->amount_paid, 0) }}</td>
                    <td>{{ number_format((float) $fee->balance, 0) }}</td>
                    <td>{{ $fee->payment_status }}</td>
                    <td>{{ $fee->payment_method }}</td>
                    <td>{{ $fee->receipt_number }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
