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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Term basic information
            $table->string('name'); // e.g., "First Term 2024", "Second Term 2024"
            $table->string('code')->unique(); // e.g., "T1-2024", "T2-2024"
            $table->text('description')->nullable();
            
            // Academic year
            $table->string('academic_year'); // e.g., "2024-2025"
            $table->integer('academic_year_start');
            $table->integer('academic_year_end');
            
            // Term dates
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_start_date')->nullable();
            $table->date('registration_end_date')->nullable();
            
            // Term settings
            $table->enum('term_type', ['first', 'second', 'third', 'summer', 'holiday', 'other']);
            $table->integer('duration_weeks')->default(12);
            $table->integer('total_instructional_days')->nullable();
            $table->integer('total_instructional_hours')->nullable();
            
            // Grading and assessment
            $table->boolean('is_grading_period')->default(true);
            $table->boolean('is_exam_period')->default(true);
            $table->date('mid_term_start_date')->nullable();
            $table->date('mid_term_end_date')->nullable();
            $table->date('final_exam_start_date')->nullable();
            $table->date('final_exam_end_date')->nullable();
            
            // Term status
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_current_term')->default(false);
            $table->boolean('is_next_term')->default(false);
            
            // Financial settings
            $table->decimal('tuition_fee', 15, 2)->nullable();
            $table->decimal('other_fees', 15, 2)->nullable();
            $table->date('fee_due_date')->nullable();
            $table->boolean('late_fee_applicable')->default(true);
            $table->decimal('late_fee_amount', 15, 2)->nullable();
            $table->integer('late_fee_days')->nullable();
            
            // Additional settings
            $table->json('holidays')->nullable(); // Array of holiday dates
            $table->json('special_events')->nullable(); // Array of special events
            $table->text('notes')->nullable();
            $table->text('announcements')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['business_id', 'academic_year']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'is_current_term']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
