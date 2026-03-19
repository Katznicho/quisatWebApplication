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
        Schema::create('class_assignment_parent_hidden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('class_assignments')->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'parent_guardian_id'], 'assignment_parent_hidden_unique');
            $table->index(['parent_guardian_id', 'assignment_id'], 'parent_hidden_parent_assignment_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_assignment_parent_hidden');
    }
};
