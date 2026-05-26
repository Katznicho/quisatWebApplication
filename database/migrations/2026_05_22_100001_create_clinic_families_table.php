<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_families', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('access_code', 20);
            $table->string('family_name')->nullable();
            $table->foreignId('primary_parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['business_id', 'access_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_families');
    }
};
