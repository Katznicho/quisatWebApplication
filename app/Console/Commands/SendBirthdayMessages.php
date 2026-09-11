<?php

namespace App\Console\Commands;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendBirthdayMessages extends Command
{
    use ResolvesPushDeviceTokens;

    protected $signature = 'quisat:send-birthday-messages';

    protected $description = 'Send birthday messages to parents of children whose birthday is today';

    public function handle(PushNotificationService $pushService): int
    {
        $today = Carbon::now(config('app.timezone', 'Africa/Nairobi'));
        $month = $today->month;
        $day = $today->day;

        $students = Student::query()
            ->with('parentGuardian')
            ->whereNotNull('date_of_birth')
            ->whereNotNull('parent_guardian_id')
            ->whereMonth('date_of_birth', $month)
            ->whereDay('date_of_birth', $day)
            ->where('status', 'active')
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            $parent = $student->parentGuardian;
            if (! $parent instanceof ParentGuardian) {
                continue;
            }

            $age = optional($student->date_of_birth)->age;
            $title = 'Happy Birthday, '.$student->first_name.'!';
            $body = $age
                ? "{$student->full_name} turns {$age} today. Celebrate with them!"
                : "Today is {$student->full_name}'s birthday. Celebrate with them!";

            $data = [
                'type' => 'birthday',
                'screen' => 'StudentProfile',
                'student_id' => (string) $student->id,
            ];

            UserNotification::create([
                'notifiable_type' => $parent::class,
                'notifiable_id' => $parent->getKey(),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            $tokens = $this->resolveDeviceTokens(collect([$parent]));
            if ($tokens->isNotEmpty()) {
                $pushService->sendExpoBatch($tokens, $title, $body, $data);
            }

            $sent++;
        }

        $this->info("Birthday messages sent for {$sent} child(ren).");

        return self::SUCCESS;
    }
}
