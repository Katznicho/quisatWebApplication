<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            if (! Schema::hasColumn('calendar_events', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->default(0)->after('location');
            }
            if (! Schema::hasColumn('calendar_events', 'accepts_registrations')) {
                $table->boolean('accepts_registrations')->default(true)->after('price');
            }
            if (! Schema::hasColumn('calendar_events', 'max_participants')) {
                $table->unsignedInteger('max_participants')->nullable()->after('accepts_registrations');
            }
            if (! Schema::hasColumn('calendar_events', 'current_participants')) {
                $table->unsignedInteger('current_participants')->default(0)->after('max_participants');
            }
        });

        if (! Schema::hasTable('calendar_event_registrations')) {
            Schema::create('calendar_event_registrations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();

                $table->string('child_name');
                $table->integer('child_age');
                $table->string('parent_name');
                $table->string('parent_email');
                $table->string('parent_phone');
                $table->string('emergency_contact')->nullable();
                $table->text('medical_conditions')->nullable();
                $table->text('dietary_restrictions')->nullable();
                $table->text('notes')->nullable();

                $table->string('payment_method')->default('cash');
                $table->string('payment_status')->default('pending');
                $table->string('registration_status')->default('confirmed');

                $table->timestamps();

                $table->index(['calendar_event_id', 'registration_status'], 'cal_event_reg_status_idx');
                $table->index('parent_email', 'cal_event_reg_email_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_registrations');

        Schema::table('calendar_events', function (Blueprint $table) {
            $columns = collect([
                'price',
                'accepts_registrations',
                'max_participants',
                'current_participants',
            ])->filter(fn (string $column) => Schema::hasColumn('calendar_events', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
