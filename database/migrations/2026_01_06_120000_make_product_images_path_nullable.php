<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make the 'path' column nullable if it exists
        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'path')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->string('path')->nullable()->change();
            });
        }
        
        // Make the 'image_path' column nullable if it exists
        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'image_path')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->string('image_path')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to revert - nullable is fine
    }
};

