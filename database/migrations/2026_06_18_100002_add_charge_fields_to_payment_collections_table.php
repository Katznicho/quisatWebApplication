<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_collections', 'base_amount')) {
                $table->unsignedInteger('base_amount')->nullable()->after('payable_id');
            }
            if (! Schema::hasColumn('payment_collections', 'platform_charge')) {
                $table->unsignedInteger('platform_charge')->default(0)->after('base_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_collections', function (Blueprint $table) {
            if (Schema::hasColumn('payment_collections', 'platform_charge')) {
                $table->dropColumn('platform_charge');
            }
            if (Schema::hasColumn('payment_collections', 'base_amount')) {
                $table->dropColumn('base_amount');
            }
        });
    }
};
