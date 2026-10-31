<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pocket_pay_card_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->string('batch', 50)->nullable();
            $table->string('status', 20)->default('available');
            $table->foreignId('pocket_pay_card_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_pay_card_numbers');
    }
};
