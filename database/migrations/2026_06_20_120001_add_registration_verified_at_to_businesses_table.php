<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('businesses', 'registration_verified_at')) {
            Schema::table('businesses', function (Blueprint $table) {
                if (Schema::hasColumn('businesses', 'stationery_verified_at')) {
                    $table->timestamp('registration_verified_at')->nullable()->after('stationery_verified_at');
                } else {
                    $table->timestamp('registration_verified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'registration_verified_at')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('registration_verified_at');
            });
        }
    }
};
