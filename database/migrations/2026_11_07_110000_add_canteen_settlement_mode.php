<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->string('settlement_mode', 20)->default('float')->after('status');
            $table->foreignId('withdrawer_id')->nullable()->after('settlement_mode')->constrained('users')->nullOnDelete();
        });

        if (Schema::hasTable('pocket_pay_withdrawals') && ! Schema::hasColumn('pocket_pay_withdrawals', 'canteen_id')) {
            Schema::table('pocket_pay_withdrawals', function (Blueprint $table) {
                $table->foreignId('canteen_id')->nullable()->after('requested_by')->constrained('canteens')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pocket_pay_withdrawals', 'canteen_id')) {
            Schema::table('pocket_pay_withdrawals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('canteen_id');
            });
        }

        Schema::table('canteens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawer_id');
            $table->dropColumn('settlement_mode');
        });
    }
};
