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
            $table->decimal('held_balance', 15, 2)->default(0)->after('available_balance');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('wallet_credit_amount', 15, 2)->nullable()->after('payment_status');
            $table->timestamp('funds_released_at')->nullable()->after('wallet_credit_amount');
            $table->foreignId('funds_released_by')->nullable()->after('funds_released_at')->constrained('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE business_balance_ledgers MODIFY COLUMN type ENUM('credit', 'debit', 'withdrawal_fee', 'pending_credit', 'fund_release') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['funds_released_by']);
            $table->dropColumn(['wallet_credit_amount', 'funds_released_at', 'funds_released_by']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('held_balance');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE business_balance_ledgers MODIFY COLUMN type ENUM('credit', 'debit', 'withdrawal_fee') NOT NULL");
        }
    }
};
