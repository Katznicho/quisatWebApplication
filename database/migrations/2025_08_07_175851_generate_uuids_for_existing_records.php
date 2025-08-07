<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate UUIDs for existing records in all tables
        
        // Users table
        $users = DB::table('users')->whereNull('uuid')->get();
        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Businesses table
        $businesses = DB::table('businesses')->whereNull('uuid')->get();
        foreach ($businesses as $business) {
            DB::table('businesses')->where('id', $business->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Currencies table
        $currencies = DB::table('currencies')->whereNull('uuid')->get();
        foreach ($currencies as $currency) {
            DB::table('currencies')->where('id', $currency->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Transactions table
        $transactions = DB::table('transactions')->whereNull('uuid')->get();
        foreach ($transactions as $transaction) {
            DB::table('transactions')->where('id', $transaction->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Activity logs table
        $activityLogs = DB::table('activity_logs')->whereNull('uuid')->get();
        foreach ($activityLogs as $activityLog) {
            DB::table('activity_logs')->where('id', $activityLog->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Business categories table
        $businessCategories = DB::table('business_categories')->whereNull('uuid')->get();
        foreach ($businessCategories as $businessCategory) {
            DB::table('business_categories')->where('id', $businessCategory->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Features table
        $features = DB::table('features')->whereNull('uuid')->get();
        foreach ($features as $feature) {
            DB::table('features')->where('id', $feature->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Programs table
        $programs = DB::table('programs')->whereNull('uuid')->get();
        foreach ($programs as $program) {
            DB::table('programs')->where('id', $program->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Program events table
        $programEvents = DB::table('program_events')->whereNull('uuid')->get();
        foreach ($programEvents as $programEvent) {
            DB::table('program_events')->where('id', $programEvent->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Event attendees table
        $eventAttendees = DB::table('event_attendees')->whereNull('uuid')->get();
        foreach ($eventAttendees as $eventAttendee) {
            DB::table('event_attendees')->where('id', $eventAttendee->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Roles table
        $roles = DB::table('roles')->whereNull('uuid')->get();
        foreach ($roles as $role) {
            DB::table('roles')->where('id', $role->id)->update(['uuid' => (string) Str::uuid()]);
        }
        
        // Payments table
        $payments = DB::table('payments')->whereNull('uuid')->get();
        foreach ($payments as $payment) {
            DB::table('payments')->where('id', $payment->id)->update(['uuid' => (string) Str::uuid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it would lose data
    }
};
