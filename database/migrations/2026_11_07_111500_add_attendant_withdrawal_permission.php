<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteen_attendants', function (Blueprint $table) {
            $table->boolean('can_withdraw')->default(false)->after('status');
        });

        if (Schema::hasTable('pocket_pay_withdrawals') && ! Schema::hasColumn('pocket_pay_withdrawals', 'canteen_attendant_id')) {
            Schema::table('pocket_pay_withdrawals', function (Blueprint $table) {
                $table->foreignId('canteen_attendant_id')->nullable()->after('canteen_id')->constrained('canteen_attendants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pocket_pay_withdrawals', 'canteen_attendant_id')) {
            Schema::table('pocket_pay_withdrawals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('canteen_attendant_id');
            });
        }

        Schema::table('canteen_attendants', function (Blueprint $table) {
            $table->dropColumn('can_withdraw');
        });
    }
};
