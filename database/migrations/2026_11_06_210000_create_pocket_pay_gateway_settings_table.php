<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pocket_pay_gateway_settings')) {
            return;
        }

        Schema::create('pocket_pay_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('marzpay_base_url')->nullable();
            $table->text('marzpay_api_key')->nullable();
            $table->text('marzpay_api_secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_pay_gateway_settings');
    }
};
