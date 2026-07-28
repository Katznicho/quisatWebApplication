<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\DeviceToken;
use App\Models\Message;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConversationMessageNotificationService
{
    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notify(Conversation $conversation, Message $message, User $sender): void
    {
        $conversation->loadMissing('users');

        $recipients = $this->resolveRecipients($conversation, $sender);

        if ($recipients->isEmpty()) {
            return;
        }

        $body = $this->buildBody($message);
        $data = [
            'type' => 'message',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'title' => $sender->name ?? 'New message',
        ];

        $title = $sender->name ?? 'New message';

        foreach ($recipients as $recipient) {
            UserNotification::create([
                'notifiable_type' => $recipient::class,
                'notifiable_id' => $recipient->getKey(),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        $tokens = $this->resolveDeviceTokens($recipients);

        if ($tokens->isEmpty()) {
            return;
        }

        $this->pushService->sendExpoBatch($tokens, $title, $body, $data);
    }

    protected function buildBody(Message $message): string
    {
        $content = trim((string) $message->content);

        if ($content !== '') {
            return Str::limit(strip_tags($content), 240);
        }

        return match ($message->type) {
            'image' => 'Sent an image',
            'file' => 'Sent a file',
            default => 'Sent a message',
        };
    }

    /**
     * @return Collection<int, Model>
     */
    protected function resolveRecipients(Conversation $conversation, User $sender): Collection
    {
        $owners = collect();

        foreach ($conversation->users as $participant) {
            if ($participant->id === $sender->id) {
                continue;
            }

            $owners->push($participant);

            $email = strtolower(trim((string) $participant->email));
            if ($email === '') {
                continue;
            }

            $parent = ParentGuardian::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->first();

            if ($parent) {
                $owners->push($parent);
            }
        }

        return $owners
            ->unique(fn (Model $owner) => $owner::class.'#'.$owner->getKey())
            ->values();
    }

    /**
     * @param  Collection<int, Model>  $recipients
     * @return Collection<int, DeviceToken>
     */
    protected function resolveDeviceTokens(Collection $recipients): Collection
    {
        return DeviceToken::query()
            ->where('is_active', true)
            ->where(function ($query) use ($recipients) {
                foreach ($recipients as $recipient) {
                    $query->orWhere(function ($ownerQuery) use ($recipient) {
                        $ownerQuery
                            ->where('tokenable_type', $recipient::class)
                            ->where('tokenable_id', $recipient->getKey());
                    });
                }
            })
            ->get();
    }
}
