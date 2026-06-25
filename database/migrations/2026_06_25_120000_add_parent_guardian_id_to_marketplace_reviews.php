<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->foreignId('parent_guardian_id')
                ->nullable()
                ->after('user_id')
                ->constrained('parent_guardians')
                ->nullOnDelete();
        });

        Schema::table('business_reviews', function (Blueprint $table) {
            $table->foreignId('parent_guardian_id')
                ->nullable()
                ->after('user_id')
                ->constrained('parent_guardians')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropForeign(['parent_guardian_id']);
            $table->dropColumn('parent_guardian_id');
        });

        Schema::table('business_reviews', function (Blueprint $table) {
            $table->dropForeign(['parent_guardian_id']);
            $table->dropColumn('parent_guardian_id');
        });
    }
};
