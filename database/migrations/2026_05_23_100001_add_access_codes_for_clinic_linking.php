<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('access_code', 20)->nullable()->unique()->after('student_id');
        });

        Schema::table('clinic_patients', function (Blueprint $table) {
            $table->string('school_access_code', 20)->nullable()->after('student_id');
            $table->unique(['business_id', 'student_id'], 'clinic_patients_business_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_patients', function (Blueprint $table) {
            $table->dropUnique('clinic_patients_business_student_unique');
            $table->dropColumn('school_access_code');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};
