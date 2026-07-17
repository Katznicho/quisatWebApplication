<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('clinic_appointments', 'clinic_service_id')) {
                $table->foreignId('clinic_service_id')
                    ->nullable()
                    ->after('clinic_patient_id')
                    ->constrained('clinic_services')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('clinic_appointments', 'amount')) {
                $table->unsignedInteger('amount')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('clinic_appointments', 'currency')) {
                $table->string('currency', 3)->default('UGX')->after('amount');
            }

            if (! Schema::hasColumn('clinic_appointments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('currency');
            }

            if (! Schema::hasColumn('clinic_appointments', 'payment_status')) {
                $table->string('payment_status')->default('not_required')->after('payment_method');
            }

            if (! Schema::hasColumn('clinic_appointments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('clinic_appointments', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('paid_at');
            }
        });

        // Existing rows: free / historical bookings are outside MarzPay billing.
        DB::table('clinic_appointments')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '');
            })
            ->update(['payment_status' => 'not_required']);
    }

    public function down(): void
    {
        Schema::table('clinic_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_appointments', 'clinic_service_id')) {
                $table->dropConstrainedForeignId('clinic_service_id');
            }

            $columns = collect([
                'amount',
                'currency',
                'payment_method',
                'payment_status',
                'paid_at',
                'payment_notes',
            ])->filter(fn (string $column) => Schema::hasColumn('clinic_appointments', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
