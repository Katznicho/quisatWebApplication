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
        Schema::create('support_children', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('child_name');
            $table->unsignedInteger('age')->nullable();
            $table->decimal('monthly_fee', 10, 2)->nullable();
            $table->string('currency', 10)->default('UGX');
            $table->text('story')->nullable();
            $table->string('organisation_name')->nullable();
            $table->string('organisation_email')->nullable();
            $table->string('organisation_phone')->nullable();
            $table->string('organisation_website')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_children');
    }
};
