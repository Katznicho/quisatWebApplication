<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'grade_levels')) {
                $table->dropColumn('grade_levels');
            }

            if (! Schema::hasColumn('products', 'is_on_sale')) {
                $table->boolean('is_on_sale')->default(false);
            }
            if (! Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('products', 'promotion_label')) {
                $table->string('promotion_label')->nullable();
            }
            if (! Schema::hasColumn('products', 'promotion_starts_at')) {
                $table->timestamp('promotion_starts_at')->nullable();
            }
            if (! Schema::hasColumn('products', 'promotion_ends_at')) {
                $table->timestamp('promotion_ends_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['is_on_sale', 'sale_price', 'promotion_label', 'promotion_starts_at', 'promotion_ends_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (! Schema::hasColumn('products', 'grade_levels')) {
                $table->json('grade_levels')->nullable();
            }
        });
    }
};
