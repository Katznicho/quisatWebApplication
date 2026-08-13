<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_children')) {
            return;
        }

        Schema::create('parent_children', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_guardian_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('allergies')->nullable();
            $table->timestamps();

            $table->index(['parent_guardian_id', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_children');
    }
};
