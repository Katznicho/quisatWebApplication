@php
    $formatDate = function ($value) {
        if (! $value) {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $th) {
            return $value;
        }
    };

    $sales = (float) ($invoice['salesAmount'] ?? 0);
    $discount = (float) ($invoice['discountAmount'] ?? 0);
    $total = (float) ($invoice['totalAmount'] ?? 0);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['invoiceNo'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; margin: 0; padding: 32px; }
        .header { width: 100%; border-bottom: 3px solid #322f90; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 22px; font-weight: bold; color: #322f90; letter-spacing: 0.5px; }
        .muted { color: #6b7280; font-size: 11px; }
        .title { font-size: 18px; font-weight: bold; color: #322f90; text-align: right; }
        .meta { margin-top: 24px; width: 100%; }
        .box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 14px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        .value { font-size: 13px; font-weight: bold; color: #322f90; margin-top: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 28px; }
        table.items th { background: #322f90; color: #ffffff; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items td { border-bottom: 1px solid #e5e7eb; padding: 10px; }
        .totals { width: 280px; margin-left: auto; margin-top: 18px; }
        .totals td { padding: 6px 0; }
        .totals .grand { border-top: 2px solid #322f90; font-weight: bold; color: #322f90; font-size: 14px; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 10px; }
        img.logo { height: 42px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                @if(!empty($logoPath))
                    <img class="logo" src="{{ $logoPath }}" alt="Stabex">
                @else
                    <div class="brand">STABEX</div>
                @endif
                <div class="muted" style="margin-top: 6px;">Stabex Customer Portal</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="title">TAX INVOICE</div>
                <div class="muted">Invoice No. {{ $invoice['invoiceNo'] }}</div>
            </td>
        </tr>
    </table>

    <table class="meta" cellpadding="0" cellspacing="0">
        <tr>
            <td width="48%" class="box">
                <div class="label">Bill To</div>
                <div class="value">{{ $customerName }}</div>
                <div class="muted" style="margin-top: 4px;">Customer No: {{ $invoice['customerNo'] }}</div>
                @if(!empty($customerEmail))
                    <div class="muted">{{ $customerEmail }}</div>
                @endif
            </td>
            <td width="4%"></td>
            <td width="48%" class="box">
                <div class="label">Posting Date</div>
                <div class="value">{{ $formatDate($invoice['postingDate'] ?? null) }}</div>
                <div class="muted" style="margin-top: 8px;">Period: {{ $formatDate($invoice['startingDate'] ?? null) }} — {{ $formatDate($invoice['endingDate'] ?? null) }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Sales Amount</th>
                <th class="num">Discount</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ($invoice['description'] ?? '') !== '' ? $invoice['description'] : 'Customer invoice for the period shown' }}</td>
                <td class="num">{{ number_format($sales, 2) }}</td>
                <td class="num">{{ number_format($discount, 2) }}</td>
                <td class="num">{{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Sales Amount</td>
            <td style="text-align: right;">{{ number_format($sales, 2) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td style="text-align: right;">{{ number_format($discount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total Amount</td>
            <td style="text-align: right;">{{ number_format($total, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        Generated from Stabex Customer Portal on {{ now()->format('d M Y H:i') }}.
    </div>
</body>
</html>
