<?php

use App\Services\WithdrawalFeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdrawal_fee_tiers') && ! Schema::hasColumn('withdrawal_fee_tiers', 'currency_code')) {
            Schema::table('withdrawal_fee_tiers', function (Blueprint $table) {
                $table->string('currency_code', 10)->default('UGX')->after('channel');
                $table->index(['business_id', 'channel', 'currency_code', 'sort_order'], 'withdrawal_fee_tiers_currency_idx');
            });
        }

        if (! Schema::hasTable('withdrawal_fee_tiers')) {
            return;
        }

        DB::table('withdrawal_fee_tiers')
            ->where(function ($query) {
                $query->whereNull('currency_code')->orWhere('currency_code', '');
            })
            ->update(['currency_code' => 'UGX']);

        $now = now();
        $schedules = WithdrawalFeeService::defaultSchedules()['KSH'] ?? [];

        foreach ($schedules as $channel => $tiers) {
            $exists = DB::table('withdrawal_fee_tiers')
                ->whereNull('business_id')
                ->where('channel', $channel)
                ->where('currency_code', 'KSH')
                ->exists();

            if ($exists) {
                continue;
            }

            foreach ($tiers as $tier) {
                DB::table('withdrawal_fee_tiers')->insert([
                    'business_id' => null,
                    'channel' => $channel,
                    'currency_code' => 'KSH',
                    'min_amount' => $tier['min_amount'],
                    'max_amount' => $tier['max_amount'],
                    'charge_amount' => $tier['charge_amount'],
                    'sort_order' => $tier['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdrawal_fee_tiers')) {
            return;
        }

        DB::table('withdrawal_fee_tiers')
            ->whereNull('business_id')
            ->where('currency_code', 'KSH')
            ->delete();

        if (Schema::hasColumn('withdrawal_fee_tiers', 'currency_code')) {
            Schema::table('withdrawal_fee_tiers', function (Blueprint $table) {
                $table->dropIndex('withdrawal_fee_tiers_currency_idx');
                $table->dropColumn('currency_code');
            });
        }
    }
};
