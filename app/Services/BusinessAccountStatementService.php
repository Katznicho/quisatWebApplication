<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessBalanceLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BusinessAccountStatementService
{
    public function build(Business $business, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $openingEntry = BusinessBalanceLedger::query()
            ->where('business_id', $business->id)
            ->where('created_at', '<', $from)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $openingBalance = $openingEntry
            ? (float) $openingEntry->available_balance_after
            : 0.0;

        $entries = BusinessBalanceLedger::query()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $lines = $this->mapLines($entries);

        $totalCredits = (float) $entries->whereIn('type', ['credit', 'fund_release'])->sum('amount');
        $totalDebits = (float) $entries->where('type', 'debit')->sum('amount');
        $totalFees = (float) $entries->where('type', 'withdrawal_fee')->sum('amount');
        $totalHeld = (float) $entries->where('type', 'pending_credit')->sum('amount');

        $closingBalance = $this->resolveClosingBalance($business, $entries, $openingBalance, $to);

        return [
            'statement_number' => $this->statementNumber($business, $from, $to),
            'generated_at' => now(),
            'business' => $business,
            'from' => $from,
            'to' => $to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_balance' => (float) $business->total_balance,
            'available_balance' => (float) $business->available_balance,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'total_fees' => $totalFees,
            'total_held' => $totalHeld,
            'net_movement' => $totalCredits - $totalDebits - $totalFees,
            'entries' => $entries,
            'lines' => $lines,
            'currency' => $business->currency_code ?: 'UGX',
        ];
    }

    public function pdfFilename(array $statement): string
    {
        $from = $statement['from']->format('Y-m-d');
        $to = $statement['to']->format('Y-m-d');
        $slug = Str::slug($statement['business']->name);

        return "quisat-account-statement-{$slug}-{$from}-to-{$to}.pdf";
    }

    protected function mapLines(Collection $entries): Collection
    {
        return $entries->map(function (BusinessBalanceLedger $entry) {
            $isCredit = in_array($entry->type, ['credit', 'fund_release'], true);
            $isDebit = in_array($entry->type, ['debit', 'withdrawal_fee'], true);
            $isPending = $entry->type === 'pending_credit';

            return [
                'date' => $entry->created_at,
                'reference' => $entry->uuid,
                'description' => $entry->description ?: $this->typeLabel($entry->type),
                'type' => $this->typeLabel($entry->type),
                'credit' => $isCredit ? (float) $entry->amount : null,
                'debit' => $isDebit ? (float) $entry->amount : null,
                'pending' => $isPending ? (float) $entry->amount : null,
                'balance_after' => (float) $entry->available_balance_after,
            ];
        });
    }

    protected function resolveClosingBalance(
        Business $business,
        Collection $entries,
        float $openingBalance,
        Carbon $to
    ): float {
        if ($entries->isNotEmpty()) {
            $lastInPeriod = (float) $entries->last()->available_balance_after;

            if ($to->endOfDay()->gte(now()->startOfDay())) {
                return (float) $business->available_balance;
            }

            return $lastInPeriod;
        }

        if ($to->endOfDay()->gte(now()->startOfDay())) {
            return (float) $business->available_balance;
        }

        return $openingBalance;
    }

    protected function statementNumber(Business $business, Carbon $from, Carbon $to): string
    {
        return sprintf(
            'STMT-%s-%s-%s',
            str_pad((string) $business->id, 5, '0', STR_PAD_LEFT),
            $from->format('Ymd'),
            $to->format('Ymd')
        );
    }

    protected function typeLabel(string $type): string
    {
        return match ($type) {
            'credit' => 'Credit',
            'debit' => 'Debit',
            'withdrawal_fee' => 'Withdrawal Fee',
            'pending_credit' => 'Payment Held',
            'fund_release' => 'Funds Released',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
