<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marzpay_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('mobile_money_charge_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('mobile_money_charge_value', 12, 2)->default(0);
            $table->enum('card_charge_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('card_charge_value', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::table('marzpay_settings')->insert([
            'mobile_money_charge_type' => 'fixed',
            'mobile_money_charge_value' => 0,
            'card_charge_type' => 'fixed',
            'card_charge_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('marzpay_settings');
    }
};
