@php
    $summary = $report['summary'];
    $card = $report['card'];
    $student = $card->student;
    $parent = $student?->parentGuardian;
    $tz = $report['timezone'];
    $money = fn ($amount) => number_format((float) $amount);
    $local = fn ($date, string $format = 'd M Y, H:i') => $date ? \Illuminate\Support\Carbon::parse($date)->setTimezone($tz)->format($format) : '—';
    $periodLabel = $report['from']->isSameDay($report['to'])
        ? $report['from']->format('l, d F Y')
        : $report['from']->format('d M Y').' to '.$report['to']->format('d M Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Card Statement · {{ $student?->full_name ?? $card->card_number }}</title>
    <style>
        @page { margin: 28px 32px 46px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .center { text-align: center; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .bold { font-weight: bold; }
        .green { color: #047857; }
        .red { color: #be123c; }

        .header td { vertical-align: top; }
        .logo { width: 54px; height: 54px; border-radius: 8px; }
        .initials { width: 54px; height: 38px; padding-top: 16px; background: #1d4ed8; color: #fff; font-size: 18px; font-weight: bold; text-align: center; border-radius: 8px; }
        .school-name { font-size: 15px; font-weight: bold; color: #111827; margin: 0 0 3px; }
        .doc-title { font-size: 18px; font-weight: bold; color: #1d4ed8; margin: 0; letter-spacing: 0.02em; }
        .rule { border-bottom: 3px solid #1d4ed8; margin: 12px 0 14px; }

        .meta td { padding: 2px 0; }
        .meta .label { color: #6b7280; width: 90px; }

        .tiles td { border: 1px solid #e5e7eb; padding: 8px 10px; width: 25%; vertical-align: top; }
        .tile-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
        .tile-value { font-size: 13px; font-weight: bold; color: #111827; margin-top: 3px; }
        .tile-hint { font-size: 8px; color: #6b7280; margin-top: 2px; }

        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: #111827; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #d1d5db; }
        .grid th { background: #111827; color: #fff; text-align: left; padding: 6px 7px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.04em; }
        .grid th.right { text-align: right; }
        .grid td { padding: 5px 7px; border-bottom: 1px solid #eef0f3; vertical-align: top; }
        .grid tr:nth-child(even) td { background: #f9fafb; }
        .grid tr.band td { background: #eff6ff; font-weight: bold; }
        .grid tfoot td { background: #f3f4f6; font-weight: bold; border-top: 1.5px solid #111827; }
        .bar { height: 7px; background: #3b82f6; }
        .badge { padding: 1px 5px; border-radius: 3px; font-size: 8px; background: #f3f4f6; color: #374151; }
        .badge.ok { background: #d1fae5; color: #065f46; }
        .badge.bad { background: #ffe4e6; color: #9f1239; }
        .badge.warn { background: #fef3c7; color: #92400e; }
        .note { margin-top: 6px; font-size: 8px; color: #92400e; }
        .two-col td.col { width: 50%; vertical-align: top; }
        .two-col td.col:first-child { padding-right: 10px; }
        .two-col td.col:last-child { padding-left: 10px; }
        .signoff td { padding-top: 34px; width: 33%; }
        .signoff .line { border-top: 1px solid #9ca3af; padding-top: 4px; color: #6b7280; margin-right: 24px; }
        .page-break { page-break-before: always; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 7.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $student?->full_name }} · Card Statement · {{ $periodLabel }}
    </div>

    {{-- Header --}}
    <table class="header">
        <tr>
            <td style="width: 64px;">
                @if ($school['logo_path'])
                    <img class="logo" src="{{ $school['logo_path'] }}" alt="">
                @else
                    <div class="initials">{{ str($school['name'])->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
                @endif
            </td>
            <td>
                <p class="school-name">{{ $school['name'] }}</p>
                @if ($school['address'])<div class="muted">{{ $school['address'] }}</div>@endif
                <div class="muted">{{ collect([$school['phone'], $school['email']])->filter()->implode('  ·  ') }}</div>
            </td>
            <td class="right" style="width: 220px;">
                <p class="doc-title">CARD STATEMENT</p>
                <div class="muted" style="margin-top: 3px;">Quisat Pocket Pay</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr><td class="label">Student</td><td class="bold">{{ $student?->full_name ?? '—' }}</td></tr>
                    <tr><td class="label">Class</td><td>{{ $student?->classRoom?->name ?? '—' }}{{ $student?->student_id ? ' · Adm. '.$student->student_id : '' }}</td></tr>
                    <tr><td class="label">Card</td><td class="mono">{{ $card->maskedNumber() }}</td></tr>
                    <tr><td class="label">Status</td><td>{{ \App\Models\PocketPayCard::statusLabel($card->status) }}</td></tr>
                    <tr><td class="label">Parent</td><td>{{ $parent ? trim($parent->full_name.' '.($parent->phone ? '· '.$parent->phone : '')) : '—' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr><td class="label">Period</td><td class="bold">{{ $periodLabel }}</td></tr>
                    <tr><td class="label">Daily limit</td><td>{{ $card->daily_limit !== null ? 'UGX '.$money($card->daily_limit) : 'No limit' }}</td></tr>
                    <tr><td class="label">Generated</td><td>{{ $report['generated_at']->format('d M Y, H:i') }} ({{ $tz }})</td></tr>
                    <tr><td class="label">Prepared by</td><td>{{ $generatedBy ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <h2>Summary (UGX)</h2>
    <table class="tiles">
        <tr>
            <td><div class="tile-label">Opening balance</div><div class="tile-value">{{ $money($summary['opening']) }}</div><div class="tile-hint">{{ $report['from']->format('d M Y') }}</div></td>
            <td><div class="tile-label">Top-ups</div><div class="tile-value green">+{{ $money($summary['topups']) }}</div><div class="tile-hint">{{ $summary['topup_count'] }} mobile money</div></td>
            <td><div class="tile-label">Purchases</div><div class="tile-value red">−{{ $money($summary['debits']) }}</div><div class="tile-hint">{{ $summary['purchases'] }} completed</div></td>
            <td><div class="tile-label">Closing balance</div><div class="tile-value">{{ $money($summary['closing']) }}</div><div class="tile-hint">{{ $report['to']->format('d M Y') }}</div></td>
        </tr>
    </table>
    @if ($summary['refunds'] > 0)
        <p class="note">Includes {{ $money($summary['refunds']) }} refunded from {{ $summary['reversed_count'] }} reversed {{ str('sale')->plural($summary['reversed_count']) }}.</p>
    @endif

    @if ($report['canteens']->isNotEmpty())
        <h2>Spending by canteen</h2>
        <table class="grid">
            <thead><tr><th>Canteen</th><th class="right">Purchases</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach ($report['canteens'] as $row)
                    <tr><td>{{ $row['name'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ $money($row['amount']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Statement</h2>
    <table class="grid">
        <thead><tr><th style="width: 90px;">Date</th><th>Description</th><th class="right">Debit</th><th class="right">Credit</th><th class="right">Balance</th></tr></thead>
        <tbody>
            <tr class="band"><td>{{ $report['from']->format('d M Y') }}</td><td>Opening balance</td><td></td><td></td><td class="right">{{ $money($summary['opening']) }}</td></tr>
            @forelse ($report['ledger'] as $entry)
                <tr>
                    <td>{{ $local($entry->created_at, 'd M Y H:i') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="right red">{{ $entry->direction === 'debit' ? $money($entry->amount) : '' }}</td>
                    <td class="right green">{{ $entry->direction === 'credit' ? $money($entry->amount) : '' }}</td>
                    <td class="right">{{ $money($entry->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted">No movements in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td>{{ $report['to']->format('d M Y') }}</td><td>Closing balance</td><td class="right">{{ $money($summary['debits']) }}</td><td class="right">{{ $money($summary['credits']) }}</td><td class="right">{{ $money($summary['closing']) }}</td></tr>
        </tfoot>
    </table>

    @if ($sales->isNotEmpty())
        <h2>Purchases</h2>
        <table class="grid">
            <thead><tr><th style="width: 90px;">Date</th><th>Receipt</th><th>Canteen</th><th>Attendant</th><th>Item</th><th class="right">Amount</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($sales as $sale)
                    <tr>
                        <td>{{ $local($sale->created_at, 'd M Y H:i') }}</td>
                        <td class="mono">{{ $sale->receipt_number }}</td>
                        <td>{{ $sale->canteen?->name ?? '—' }}</td>
                        <td>{{ $sale->attendant?->name ?? '—' }}</td>
                        <td>{{ $sale->description ?: '—' }}</td>
                        <td class="right">{{ $money($sale->amount) }}</td>
                        <td><span class="badge {{ $sale->status === 'completed' ? 'ok' : 'bad' }}">{{ ucfirst($sale->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($salesTruncated)
            <p class="note">Only the first {{ $sales->count() }} purchases are listed. Choose a shorter period for the full list.</p>
        @endif
    @endif
</body>
</html>
