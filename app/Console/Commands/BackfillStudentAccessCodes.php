<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class BackfillStudentAccessCodes extends Command
{
    protected $signature = 'clinic:backfill-student-access-codes';

    protected $description = 'Generate CHD access codes for all students missing one (Kids Clinics linking)';

    public function handle(): int
    {
        $count = 0;

        Student::whereNull('access_code')
            ->orWhere('access_code', '')
            ->orderBy('id')
            ->chunkById(200, function ($students) use (&$count) {
                foreach ($students as $student) {
                    $student->ensureAccessCode();
                    $count++;
                }
            });

        $this->info("Access codes ensured for {$count} student(s).");

        return self::SUCCESS;
    }
}
