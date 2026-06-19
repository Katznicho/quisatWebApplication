<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('min_amount');
            $table->unsignedBigInteger('max_amount')->nullable();
            $table->unsignedBigInteger('charge_amount');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'sort_order']);
        });

        $now = now();
        $defaults = [
            ['min_amount' => 500, 'max_amount' => 40000, 'charge_amount' => 1000, 'sort_order' => 1],
            ['min_amount' => 40001, 'max_amount' => 200000, 'charge_amount' => 1500, 'sort_order' => 2],
            ['min_amount' => 200001, 'max_amount' => 400000, 'charge_amount' => 2800, 'sort_order' => 3],
            ['min_amount' => 400001, 'max_amount' => 1000000, 'charge_amount' => 5000, 'sort_order' => 4],
            ['min_amount' => 1000001, 'max_amount' => 1500000, 'charge_amount' => 15000, 'sort_order' => 5],
            ['min_amount' => 1500001, 'max_amount' => null, 'charge_amount' => 20000, 'sort_order' => 6],
        ];

        foreach ($defaults as $tier) {
            DB::table('withdrawal_fee_tiers')->insert([
                'business_id' => null,
                'min_amount' => $tier['min_amount'],
                'max_amount' => $tier['max_amount'],
                'charge_amount' => $tier['charge_amount'],
                'sort_order' => $tier['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_fee_tiers');
    }
};
