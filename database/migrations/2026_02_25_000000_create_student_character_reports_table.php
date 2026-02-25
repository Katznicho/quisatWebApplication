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
        Schema::create('student_character_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();

            $table->date('record_date')->index();

            // High‑level character / progress status chosen by staff
            $table->string('status')->nullable(); // e.g. Excellent, On Track, Needs Support, At Risk

            // Short title and detailed notes shown on the parent report
            $table->string('headline')->nullable();
            $table->text('notes')->nullable();

            // Flexible JSON field for CZH/character items (e.g. respect, responsibility, etc.)
            $table->json('traits')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Short composite index name to avoid MySQL's 64‑character limit
            $table->index(['business_id', 'student_id', 'record_date'], 'scr_bus_student_date_idx');

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('term_id')->references('id')->on('terms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_character_reports');
    }
};

