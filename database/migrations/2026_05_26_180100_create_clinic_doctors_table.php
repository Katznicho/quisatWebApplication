<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_doctors')) {
            return;
        }

        Schema::create('clinic_doctors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status'], 'clinic_doctors_business_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_doctors');
    }
};
