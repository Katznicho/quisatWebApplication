<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('account_balance', 15, 2)->default(0);
            $table->string('mode')->default('live');
            $table->decimal('percentage_charge', 5, 2)->nullable();
            $table->decimal('minimum_amount', 15, 2)->nullable();
            $table->string('type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['account_balance', 'mode', 'percentage_charge', 'minimum_amount', 'type']);
        });
    }
};
