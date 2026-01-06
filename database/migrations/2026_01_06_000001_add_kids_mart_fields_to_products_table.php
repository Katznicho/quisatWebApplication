<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'uuid')) {
                $table->uuid('uuid')->nullable()->index();
            }
            if (!Schema::hasColumn('products', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->index();
            }
            if (!Schema::hasColumn('products', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->index();
            }
            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('products', 'sizes')) {
                $table->json('sizes')->nullable();
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0);
            }
            if (!Schema::hasColumn('products', 'is_available')) {
                $table->boolean('is_available')->default(true)->index();
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status')->default('active')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Keep existing legacy table intact; only drop columns we added if they exist
            foreach (['uuid', 'business_id', 'name', 'description', 'price', 'category', 'image_path', 'sizes', 'stock_quantity', 'is_available', 'status'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};


