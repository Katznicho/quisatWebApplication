<?php

namespace App\Jobs;

use App\Models\PushBroadcast;
use App\Services\PushAudienceResolver;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushBroadcastJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PushBroadcast $broadcast)
    {
    }

    public function handle(PushAudienceResolver $audienceResolver, PushNotificationService $pushService): void
    {
        $broadcast = $this->broadcast->fresh();

        if (! $broadcast || $broadcast->status === PushBroadcast::STATUS_SENT) {
            return;
        }

        $broadcast->update(['status' => PushBroadcast::STATUS_SENDING]);

        $recipients = $audienceResolver->resolve($broadcast);
        $broadcast->update(['total_recipients' => $recipients->count()]);

        $pushSent = 0;
        $pushFailed = 0;
        $inAppCount = 0;

        try {
            foreach ($recipients as $recipient) {
                $owner = $recipient['owner'];
                $tokens = $recipient['tokens'];

                if ($broadcast->sendsInApp()) {
                    $audienceResolver->createInAppNotification($owner, $broadcast);
                    $inAppCount++;
                }

                if (! $broadcast->sendsPush() || $tokens->isEmpty()) {
                    continue;
                }

                foreach ($tokens as $token) {
                    $success = $pushService->sendToToken(
                        $token,
                        $broadcast->title,
                        $broadcast->body,
                        $broadcast->notificationData()
                    );

                    if ($success) {
                        $pushSent++;
                        $token->update(['last_used_at' => now()]);
                    } else {
                        $pushFailed++;
                    }
                }
            }

            $broadcast->update([
                'status' => PushBroadcast::STATUS_SENT,
                'sent_at' => now(),
                'push_sent_count' => $pushSent,
                'push_failed_count' => $pushFailed,
                'in_app_count' => $inAppCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Push broadcast failed', [
                'broadcast_id' => $broadcast->id,
                'message' => $e->getMessage(),
            ]);

            $broadcast->update([
                'status' => PushBroadcast::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'push_sent_count' => $pushSent,
                'push_failed_count' => $pushFailed,
                'in_app_count' => $inAppCount,
            ]);
        }
    }
}
