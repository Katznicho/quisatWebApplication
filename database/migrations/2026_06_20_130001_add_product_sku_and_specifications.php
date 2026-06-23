<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'sku')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'name')) {
                    $table->string('sku', 64)->nullable()->after('name');
                } else {
                    $table->string('sku', 64)->nullable();
                }
            });
        }

        if (! Schema::hasColumn('products', 'key_features')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'description')) {
                    $table->text('key_features')->nullable()->after('description');
                } else {
                    $table->text('key_features')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('products', 'whats_in_box')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'key_features')) {
                    $table->text('whats_in_box')->nullable()->after('key_features');
                } else {
                    $table->text('whats_in_box')->nullable();
                }
            });
        }

        if (Schema::hasColumn('products', 'sku') && Schema::hasColumn('products', 'business_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unique(['business_id', 'sku'], 'products_business_sku_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'sku')) {
            Schema::table('products', function (Blueprint $table) {
                try {
                    $table->dropUnique('products_business_sku_unique');
                } catch (\Throwable) {
                    // Index may not exist on all environments.
                }
                $table->dropColumn('sku');
            });
        }

        if (Schema::hasColumn('products', 'key_features')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('key_features');
            });
        }

        if (Schema::hasColumn('products', 'whats_in_box')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('whats_in_box');
            });
        }
    }
};
