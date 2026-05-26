<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_family_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('clinic_family_id')->constrained('clinic_families')->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->string('relationship', 30)->default('guardian');
            $table->json('permissions')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['clinic_family_id', 'parent_guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_family_members');
    }
};
