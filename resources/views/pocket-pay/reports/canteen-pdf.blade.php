@php
    $summary = $report['summary'];
    $school = $report['school'];
    $canteen = $report['canteen'];
    $tz = $report['timezone'];
    $money = fn ($amount) => number_format((float) $amount);
    $periodLabel = $report['from']->isSameDay($report['to'])
        ? $report['from']->format('l, d F Y')
        : $report['from']->format('d M Y').' to '.$report['to']->format('d M Y');
    $show = fn (string ...$types) => in_array($type, $types, true);
    $activeDays = collect($report['daily'])->filter(fn ($d) => $d['count'] > 0);
    $maxDay = max(1, $activeDays->max('amount') ?? 0);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} · {{ $canteen->name }}</title>
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
        {{ $canteen->name }} · {{ $title }} · {{ $periodLabel }}
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
                <div class="muted">
                    {{ collect([$school['phone'], $school['email']])->filter()->implode('  ·  ') }}
                </div>
            </td>
            <td class="right" style="width: 300px;">
                <p class="doc-title">{{ strtoupper($title) }}</p>
                <div class="muted" style="margin-top: 3px;">Quisat Pocket Pay</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr><td class="label">Canteen</td><td class="bold">{{ $canteen->name }}</td></tr>
                    <tr><td class="label">Location</td><td>{{ $canteen->location ?: '—' }}</td></tr>
                    <tr><td class="label">Status</td><td>{{ ucfirst($canteen->status) }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr><td class="label">Period</td><td class="bold">{{ $periodLabel }}</td></tr>
                    <tr><td class="label">Generated</td><td>{{ $report['generated_at']->format('d M Y, H:i') }} ({{ $tz }})</td></tr>
                    <tr><td class="label">Prepared by</td><td>{{ $generatedBy ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Summary --}}
    @if ($show('full', 'sales', 'statement'))
        <h2>Summary</h2>
        <table class="tiles">
            <tr>
                @if ($show('statement'))
                    <td><div class="tile-label">Opening balance</div><div class="tile-value">UGX {{ $money($summary['float_opening']) }}</div><div class="tile-hint">As at {{ $report['from']->format('d M Y') }}</div></td>
                    <td><div class="tile-label">Total credits</div><div class="tile-value green">UGX {{ $money($summary['float_credits']) }}</div><div class="tile-hint">Top-ups {{ $money($summary['float_topups']) }}</div></td>
                    <td><div class="tile-label">Total debits</div><div class="tile-value red">UGX {{ $money($summary['float_debits']) }}</div><div class="tile-hint">Sales paid from float</div></td>
                    <td><div class="tile-label">Closing balance</div><div class="tile-value">UGX {{ $money($summary['float_closing']) }}</div><div class="tile-hint">As at {{ $report['to']->format('d M Y') }}</div></td>
                @else
                    <td><div class="tile-label">Net sales</div><div class="tile-value">UGX {{ $money($summary['net_sales']) }}</div><div class="tile-hint">Gross {{ $money($summary['gross_sales']) }}</div></td>
                    <td><div class="tile-label">Transactions</div><div class="tile-value">{{ number_format($summary['transactions']) }}</div><div class="tile-hint">Average UGX {{ $money($summary['average_sale']) }}</div></td>
                    <td><div class="tile-label">Students served</div><div class="tile-value">{{ number_format($summary['students_served']) }}</div><div class="tile-hint">of {{ number_format($summary['cardholders']) }} cardholders</div></td>
                    <td><div class="tile-label">Reversals</div><div class="tile-value">{{ number_format($summary['reversed_count']) }}</div><div class="tile-hint">UGX {{ $money($summary['reversed_amount']) }} refunded</div></td>
                @endif
            </tr>
            @if ($show('full'))
                <tr>
                    <td><div class="tile-label">Opening float</div><div class="tile-value">UGX {{ $money($summary['float_opening']) }}</div></td>
                    <td><div class="tile-label">Float top-ups</div><div class="tile-value green">UGX {{ $money($summary['float_topups']) }}</div></td>
                    <td><div class="tile-label">Closing float</div><div class="tile-value">UGX {{ $money($summary['float_closing']) }}</div></td>
                    <td><div class="tile-label">Attendants</div><div class="tile-value">{{ $summary['active_attendants'] }} / {{ $summary['attendants'] }}</div><div class="tile-hint">Active / total</div></td>
                </tr>
            @endif
        </table>
        @if ($summary['pending_reversals'] > 0)
            <p class="note">{{ $summary['pending_reversals'] }} reversal request(s) worth UGX {{ $money($summary['pending_reversals_amount']) }} are awaiting approval and are not reflected above.</p>
        @endif
    @endif

    {{-- Daily + attendants --}}
    @if ($show('full', 'sales'))
        <table class="two-col">
            <tr>
                <td class="col">
                    <h2>Daily sales</h2>
                    <table class="grid">
                        <thead><tr><th>Date</th><th class="right">Sales</th><th class="right">Amount (UGX)</th><th style="width: 32%;"></th></tr></thead>
                        <tbody>
                            @forelse ($activeDays as $day)
                                <tr>
                                    <td>{{ $day['date']->format('D d M Y') }}</td>
                                    <td class="right">{{ number_format($day['count']) }}</td>
                                    <td class="right">{{ $money($day['amount']) }}</td>
                                    <td><div class="bar" style="width: {{ max(2, round($day['amount'] / $maxDay * 100)) }}%;"></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="center muted">No sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($activeDays->isNotEmpty())
                            <tfoot><tr><td>{{ $activeDays->count() }} trading day(s)</td><td class="right">{{ number_format($summary['transactions']) }}</td><td class="right">{{ $money($summary['net_sales']) }}</td><td></td></tr></tfoot>
                        @endif
                    </table>
                </td>
                <td class="col">
                    <h2>Attendant performance</h2>
                    <table class="grid">
                        <thead><tr><th>Attendant</th><th class="right">Sales</th><th class="right">Amount (UGX)</th><th class="right">Share</th><th class="right">Reversed</th></tr></thead>
                        <tbody>
                            @forelse ($report['attendants'] as $row)
                                <tr>
                                    <td>{{ $row['attendant']->name }}<br><span class="muted">{{ $row['attendant']->phone }}</span></td>
                                    <td class="right">{{ number_format($row['count']) }}</td>
                                    <td class="right">{{ $money($row['amount']) }}</td>
                                    <td class="right">{{ $summary['net_sales'] > 0 ? round($row['amount'] / $summary['net_sales'] * 100, 1) : 0 }}%</td>
                                    <td class="right">{{ $row['reversed'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="center muted">No attendants.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($show('full'))
                        <h2>Top students</h2>
                        <table class="grid">
                            <thead><tr><th>Student</th><th>Class</th><th class="right">Purchases</th><th class="right">Spent (UGX)</th></tr></thead>
                            <tbody>
                                @forelse ($report['top_students'] as $row)
                                    <tr>
                                        <td>{{ $row['student']?->full_name ?? 'Unknown' }}</td>
                                        <td>{{ $row['student']?->classRoom?->name ?? '—' }}</td>
                                        <td class="right">{{ number_format($row['count']) }}</td>
                                        <td class="right">{{ $money($row['amount']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="center muted">No purchases in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    {{-- Float statement --}}
    @if ($show('full', 'statement'))
        @if ($show('full'))<div class="page-break"></div>@endif
        <h2>Float statement</h2>
        <table class="grid">
            <thead><tr><th style="width: 13%;">Date</th><th style="width: 10%;">Reference</th><th>Description</th><th style="width: 9%;">Type</th><th class="right" style="width: 11%;">Debit (UGX)</th><th class="right" style="width: 11%;">Credit (UGX)</th><th class="right" style="width: 12%;">Balance (UGX)</th></tr></thead>
            <tbody>
                <tr class="band"><td>{{ $report['from']->format('d M Y') }}</td><td></td><td colspan="4">Opening balance</td><td class="right">{{ $money($summary['float_opening']) }}</td></tr>
                @forelse ($report['ledger'] as $entry)
                    <tr>
                        <td>{{ $entry->created_at->setTimezone($tz)->format('d M Y H:i') }}</td>
                        <td class="mono">{{ strtoupper(substr($entry->uuid, 0, 8)) }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>{{ ucfirst($entry->type) }}</td>
                        <td class="right red">{{ $entry->direction === 'debit' ? $money($entry->amount) : '' }}</td>
                        <td class="right green">{{ $entry->direction === 'credit' ? $money($entry->amount) : '' }}</td>
                        <td class="right">{{ $money($entry->balance_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center muted">No float movements in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td>{{ $report['to']->format('d M Y') }}</td><td></td><td colspan="2">Closing balance · {{ $report['ledger']->count() }} entries</td><td class="right red">{{ $money($summary['float_debits']) }}</td><td class="right green">{{ $money($summary['float_credits']) }}</td><td class="right">{{ $money($summary['float_closing']) }}</td></tr></tfoot>
        </table>
    @endif

    {{-- Sales register --}}
    @if ($show('full', 'sales'))
        <div class="page-break"></div>
        <h2>Sales register</h2>
        <table class="grid">
            <thead><tr><th style="width: 12%;">Date</th><th style="width: 12%;">Receipt</th><th>Student</th><th>Class</th><th style="width: 12%;">Card</th><th>Attendant</th><th>Item</th><th class="right" style="width: 10%;">Amount (UGX)</th><th style="width: 9%;">Status</th></tr></thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $sale->created_at->setTimezone($tz)->format('d M Y H:i') }}</td>
                        <td class="mono">{{ $sale->receipt_number }}</td>
                        <td>{{ $sale->student?->full_name ?? '—' }}</td>
                        <td>{{ $sale->student?->classRoom?->name ?? '—' }}</td>
                        <td class="mono">{{ $sale->card?->maskedNumber() ?? '—' }}</td>
                        <td>{{ $sale->attendant?->name ?? '—' }}</td>
                        <td>{{ $sale->description ?: '—' }}</td>
                        <td class="right">{{ $money($sale->amount) }}</td>
                        <td><span class="badge {{ $sale->status === 'completed' ? 'ok' : 'bad' }}">{{ ucfirst($sale->status) }}</span>@if ($sale->reversal_status === 'pending') <span class="badge warn">Rev. pending</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center muted">No sales in this period.</td></tr>
                @endforelse
            </tbody>
            @if ($sales->isNotEmpty())
                <tfoot><tr><td colspan="7">Net sales · {{ number_format($summary['transactions']) }} completed, {{ number_format($summary['reversed_count']) }} reversed</td><td class="right">{{ $money($summary['net_sales']) }}</td><td></td></tr></tfoot>
            @endif
        </table>
        @if ($salesTruncated)
            <p class="note">Only the first {{ number_format($sales->count()) }} sales are listed. Choose a shorter period for the full register; totals above cover every sale.</p>
        @endif
    @endif

    {{-- Students & cards --}}
    @if ($show('full', 'students'))
        @if ($show('full'))<div class="page-break"></div>@endif
        <h2>Students &amp; cards</h2>
        <table class="tiles" style="margin-bottom: 8px;">
            <tr>
                <td><div class="tile-label">Cards issued</div><div class="tile-value">{{ number_format($summary['cardholders']) }}</div></td>
                <td><div class="tile-label">Active cards</div><div class="tile-value">{{ number_format($summary['active_cards']) }}</div></td>
                <td><div class="tile-label">Students served here</div><div class="tile-value">{{ number_format($summary['students_served']) }}</div></td>
                <td><div class="tile-label">Spent here</div><div class="tile-value">UGX {{ $money($summary['net_sales']) }}</div></td>
            </tr>
        </table>
        <table class="grid">
            <thead><tr><th>#</th><th>Student</th><th>Adm. no.</th><th>Class</th><th>Card</th><th>Status</th><th class="right">Balance (UGX)</th><th class="right">Purchases</th><th class="right">Spent here (UGX)</th></tr></thead>
            <tbody>
                @forelse ($cardholders as $i => $cardholder)
                    <tr>
                        <td class="muted">{{ $i + 1 }}</td>
                        <td>{{ $cardholder->student?->full_name ?? '—' }}</td>
                        <td>{{ $cardholder->student?->student_id ?? '—' }}</td>
                        <td>{{ $cardholder->student?->classRoom?->name ?? '—' }}</td>
                        <td class="mono">{{ $cardholder->maskedNumber() }}</td>
                        <td><span class="badge {{ $cardholder->status === 'active' ? 'ok' : ($cardholder->status === 'blocked' ? 'bad' : 'warn') }}">{{ $cardholder->status === 'inactive' ? 'Not activated' : ucfirst($cardholder->status) }}</span></td>
                        <td class="right">{{ $money($cardholder->balance) }}</td>
                        <td class="right">{{ number_format($cardholder->purchases_here) }}</td>
                        <td class="right">{{ $money($cardholder->spent_here ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center muted">No cards issued at this school.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($cardholdersTruncated)
            <p class="note">Only the first {{ number_format($cardholders->count()) }} cards are listed.</p>
        @endif
    @endif

    {{-- Sign-off --}}
    <table class="signoff">
        <tr>
            <td><div class="line">Prepared by</div></td>
            <td><div class="line">Checked by (Bursar)</div></td>
            <td><div class="line">Approved by (Head teacher)</div></td>
        </tr>
    </table>

</body>
</html>
