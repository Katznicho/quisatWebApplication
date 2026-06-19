<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_collections', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('payable_id')->constrained()->nullOnDelete();
            $table->timestamp('business_credited_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_collections', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn(['business_id', 'business_credited_at']);
        });
    }
};
