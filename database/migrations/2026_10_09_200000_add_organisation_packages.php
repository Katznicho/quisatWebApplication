<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'package')) {
                $table->string('package', 20)->default('free')->after('type');
            }
            if (! Schema::hasColumn('businesses', 'package_services')) {
                $table->json('package_services')->nullable()->after('package');
            }
        });

        Schema::create('package_plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency_code', 8)->default('UGX');
            $table->timestamps();
        });

        DB::table('package_plans')->insert([
            ['key' => 'gold', 'name' => 'Gold', 'price' => null, 'currency_code' => 'UGX', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'silver', 'name' => 'Silver', 'price' => null, 'currency_code' => 'UGX', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'free', 'name' => 'Free', 'price' => 0, 'currency_code' => 'UGX', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('package_plans');
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'package_services')) {
                $table->dropColumn('package_services');
            }
            if (Schema::hasColumn('businesses', 'package')) {
                $table->dropColumn('package');
            }
        });
    }
};
