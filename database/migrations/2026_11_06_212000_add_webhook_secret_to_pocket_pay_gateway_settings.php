<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pocket_pay_gateway_settings')) {
            return;
        }

        if (Schema::hasColumn('pocket_pay_gateway_settings', 'marzpay_webhook_secret')) {
            return;
        }

        Schema::table('pocket_pay_gateway_settings', function (Blueprint $table) {
            $table->text('marzpay_webhook_secret')->nullable()->after('marzpay_api_secret');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pocket_pay_gateway_settings', 'marzpay_webhook_secret')) {
            return;
        }

        Schema::table('pocket_pay_gateway_settings', function (Blueprint $table) {
            $table->dropColumn('marzpay_webhook_secret');
        });
    }
};
