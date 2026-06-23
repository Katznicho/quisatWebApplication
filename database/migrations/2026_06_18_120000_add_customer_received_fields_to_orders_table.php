<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('customer_received_at')->nullable()->after('funds_released_by');
            $table->foreignId('customer_received_by')->nullable()->after('customer_received_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_received_by']);
            $table->dropColumn(['customer_received_at', 'customer_received_by']);
        });
    }
};
