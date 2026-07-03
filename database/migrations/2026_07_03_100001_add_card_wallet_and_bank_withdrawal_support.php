<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('card_available_balance', 15, 2)->default(0)->after('total_balance');
            $table->decimal('card_held_balance', 15, 2)->default(0)->after('card_available_balance');
            $table->decimal('card_total_balance', 15, 2)->default(0)->after('card_held_balance');
        });

        Schema::table('withdrawal_fee_tiers', function (Blueprint $table) {
            $table->string('channel', 32)->default('mobile_money')->after('business_id');
            $table->index(['business_id', 'channel', 'sort_order']);
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('wallet_source', 16)->default('main')->after('total_debited');
            $table->string('bank_name')->nullable()->after('phone_number');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
            $table->string('bank_branch')->nullable()->after('bank_account_name');
        });

        Schema::table('business_balance_ledgers', function (Blueprint $table) {
            $table->string('wallet_channel', 32)->default('mobile_money')->after('type');
        });

        $now = now();
        $bankTiers = [
            ['min_amount' => 2500, 'max_amount' => 250000, 'charge_amount' => 5000, 'sort_order' => 1],
            ['min_amount' => 250001, 'max_amount' => 500000, 'charge_amount' => 6000, 'sort_order' => 2],
            ['min_amount' => 500001, 'max_amount' => 1000000, 'charge_amount' => 9000, 'sort_order' => 3],
            ['min_amount' => 1000001, 'max_amount' => 2000000, 'charge_amount' => 13500, 'sort_order' => 4],
            ['min_amount' => 2000001, 'max_amount' => null, 'charge_amount' => 16500, 'sort_order' => 5],
        ];

        foreach ($bankTiers as $tier) {
            DB::table('withdrawal_fee_tiers')->insert([
                'business_id' => null,
                'channel' => 'bank_transfer',
                'min_amount' => $tier['min_amount'],
                'max_amount' => $tier['max_amount'],
                'charge_amount' => $tier['charge_amount'],
                'sort_order' => $tier['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('withdrawal_fee_tiers')->where('channel', 'bank_transfer')->delete();

        Schema::table('business_balance_ledgers', function (Blueprint $table) {
            $table->dropColumn('wallet_channel');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn([
                'wallet_source',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'bank_branch',
            ]);
        });

        Schema::table('withdrawal_fee_tiers', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'channel', 'sort_order']);
            $table->dropColumn('channel');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'card_available_balance',
                'card_held_balance',
                'card_total_balance',
            ]);
        });
    }
};
