<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'school_attended')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('school_attended')->nullable()->after('gender');
            });
        }

        if (Schema::hasTable('parent_children') && ! Schema::hasColumn('parent_children', 'school_attended')) {
            Schema::table('parent_children', function (Blueprint $table) {
                $table->string('school_attended')->nullable()->after('gender');
            });
        }

        if (! Schema::hasTable('small_group_visitors')) {
            Schema::create('small_group_visitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('small_group_meeting_id')->constrained('small_group_meetings')->cascadeOnDelete();
                $table->foreignId('small_group_id')->constrained('small_groups')->cascadeOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('visitor_name');
                $table->string('guardian_name')->nullable();
                $table->string('contact')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('small_group_visitors');

        if (Schema::hasColumn('students', 'school_attended')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('school_attended');
            });
        }

        if (Schema::hasColumn('parent_children', 'school_attended')) {
            Schema::table('parent_children', function (Blueprint $table) {
                $table->dropColumn('school_attended');
            });
        }
    }
};
