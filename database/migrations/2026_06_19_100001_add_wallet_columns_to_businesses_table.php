<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('available_balance', 15, 2)->default(0)->after('account_balance');
            $table->decimal('total_balance', 15, 2)->default(0)->after('available_balance');
            $table->string('withdrawal_pin')->nullable()->after('total_balance');
            $table->boolean('use_custom_withdrawal_tiers')->default(false)->after('withdrawal_pin');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'available_balance',
                'total_balance',
                'withdrawal_pin',
                'use_custom_withdrawal_tiers',
            ]);
        });
    }
};
