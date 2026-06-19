<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'hub')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'business_id')) {
                    $table->string('hub', 32)->default('kidz_mart')->after('business_id')->index();
                } else {
                    $table->string('hub', 32)->default('kidz_mart')->index();
                }
            });
        }

        if (! Schema::hasColumn('products', 'grade_levels')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'category')) {
                    $table->json('grade_levels')->nullable()->after('category');
                } else {
                    $table->json('grade_levels')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('products', 'delivery_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedSmallInteger('delivery_days')->default(3);
            });
        }

        if (! Schema::hasColumn('products', 'quality_grade')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('quality_grade', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'low_stock_threshold')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $table->unsignedInteger('low_stock_threshold')->default(15)->after('stock_quantity');
                } else {
                    $table->unsignedInteger('low_stock_threshold')->default(15);
                }
            });
        }

        if (! Schema::hasColumn('orders', 'hub')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'business_id')) {
                    $table->string('hub', 32)->default('kidz_mart')->after('business_id')->index();
                } else {
                    $table->string('hub', 32)->default('kidz_mart')->index();
                }
            });
        }

        if (! Schema::hasColumn('orders', 'fulfillment_status')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'status')) {
                    $table->string('fulfillment_status', 32)->default('new')->after('status');
                } else {
                    $table->string('fulfillment_status', 32)->default('new');
                }
            });
        }

        if (! Schema::hasColumn('businesses', 'accepting_stationery_orders')) {
            Schema::table('businesses', function (Blueprint $table) {
                if (Schema::hasColumn('businesses', 'use_custom_withdrawal_tiers')) {
                    $table->boolean('accepting_stationery_orders')->default(true)->after('use_custom_withdrawal_tiers');
                } else {
                    $table->boolean('accepting_stationery_orders')->default(true);
                }
            });
        }

        if (! Schema::hasColumn('businesses', 'stationery_verified_at')) {
            Schema::table('businesses', function (Blueprint $table) {
                if (Schema::hasColumn('businesses', 'accepting_stationery_orders')) {
                    $table->timestamp('stationery_verified_at')->nullable()->after('accepting_stationery_orders');
                } else {
                    $table->timestamp('stationery_verified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('businesses', 'accepting_stationery_orders') ? 'accepting_stationery_orders' : null,
                Schema::hasColumn('businesses', 'stationery_verified_at') ? 'stationery_verified_at' : null,
            ]);
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('orders', 'hub') ? 'hub' : null,
                Schema::hasColumn('orders', 'fulfillment_status') ? 'fulfillment_status' : null,
            ]);
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('products', 'hub') ? 'hub' : null,
                Schema::hasColumn('products', 'grade_levels') ? 'grade_levels' : null,
                Schema::hasColumn('products', 'delivery_days') ? 'delivery_days' : null,
                Schema::hasColumn('products', 'quality_grade') ? 'quality_grade' : null,
                Schema::hasColumn('products', 'low_stock_threshold') ? 'low_stock_threshold' : null,
            ]);
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
