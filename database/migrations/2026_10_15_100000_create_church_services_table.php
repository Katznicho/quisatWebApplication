<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('church_services')) {
            Schema::create('church_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('schedule_type', 20);
                $table->unsignedTinyInteger('weekday')->nullable();
                $table->date('service_date')->nullable();
                $table->time('starts_at');
                $table->time('ends_at');
                $table->boolean('is_active')->default(true);
                $table->string('timezone', 64)->default('Africa/Nairobi');
                $table->timestamps();
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'church_service_id')) {
                $table->foreignId('church_service_id')->nullable()->after('class_room_id')->constrained('church_services')->nullOnDelete();
            }
            if (! Schema::hasColumn('attendances', 'service_slot')) {
                $table->unsignedBigInteger('service_slot')->default(0)->after('church_service_id');
            }
            if (! Schema::hasColumn('attendances', 'service_name')) {
                $table->string('service_name')->nullable()->after('service_slot');
            }
            if (! Schema::hasColumn('attendances', 'service_starts_at')) {
                $table->time('service_starts_at')->nullable()->after('service_name');
            }
            if (! Schema::hasColumn('attendances', 'service_ends_at')) {
                $table->time('service_ends_at')->nullable()->after('service_starts_at');
            }
        });

        $this->replaceAttendanceUnique();
    }

    /**
     * The student foreign key is using unique_attendance as its index.
     * MySQL will not drop that index until another student_id index exists.
     */
    private function replaceAttendanceUnique(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM attendances'));
        $names = $indexes->pluck('Key_name')->unique();

        if ($names->contains('unique_attendance')) {
            $studentIndexExists = $indexes->contains(function ($index) {
                return $index->Key_name !== 'unique_attendance'
                    && (int) $index->Seq_in_index === 1
                    && $index->Column_name === 'student_id';
            });

            if (! $studentIndexExists) {
                DB::statement('ALTER TABLE attendances ADD INDEX attendances_student_id_fk_support (student_id)');
            }

            DB::statement('ALTER TABLE attendances DROP INDEX unique_attendance');
        }

        $names = collect(DB::select('SHOW INDEX FROM attendances'))->pluck('Key_name')->unique();
        if (! $names->contains('unique_attendance_service')) {
            DB::statement('ALTER TABLE attendances ADD UNIQUE unique_attendance_service (student_id, attendance_date, class_room_id, service_slot)');
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('unique_attendance_service');
            $table->dropConstrainedForeignId('church_service_id');
            $table->dropColumn(['service_slot', 'service_name', 'service_starts_at', 'service_ends_at']);
            $table->unique(['student_id', 'attendance_date', 'class_room_id'], 'unique_attendance');
        });

        Schema::dropIfExists('church_services');
    }
};
