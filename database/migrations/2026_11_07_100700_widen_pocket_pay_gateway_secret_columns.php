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

        foreach (['marzpay_api_key', 'marzpay_api_secret', 'marzpay_webhook_secret'] as $column) {
            if (! Schema::hasColumn('pocket_pay_gateway_settings', $column)) {
                continue;
            }

            $type = Schema::getColumnType('pocket_pay_gateway_settings', $column);
            if (in_array($type, ['text', 'blob', 'mediumtext', 'longtext'], true)) {
                continue;
            }

            Schema::table('pocket_pay_gateway_settings', function (Blueprint $table) use ($column) {
                $table->longText($column)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Encrypted values do not fit back into a short string column.
    }
};
