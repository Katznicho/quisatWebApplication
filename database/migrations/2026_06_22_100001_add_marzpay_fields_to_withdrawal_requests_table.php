<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('withdrawal_requests', 'marz_transaction_uuid')) {
                $table->string('marz_transaction_uuid')->nullable()->after('status');
            }
            if (! Schema::hasColumn('withdrawal_requests', 'provider_reference')) {
                $table->string('provider_reference')->nullable()->after('marz_transaction_uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawal_requests', 'provider_reference')) {
                $table->dropColumn('provider_reference');
            }
            if (Schema::hasColumn('withdrawal_requests', 'marz_transaction_uuid')) {
                $table->dropColumn('marz_transaction_uuid');
            }
        });
    }
};
