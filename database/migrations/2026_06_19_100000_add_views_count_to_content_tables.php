<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'businesses', 'programs', 'kids_events', 'program_events'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'views_count')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('views_count')->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['products', 'businesses', 'programs', 'kids_events', 'program_events'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'views_count')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('views_count');
                });
            }
        }
    }
};
