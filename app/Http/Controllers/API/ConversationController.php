<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $authenticatedUser = $request->get('authenticated_user');
        
        // Get the User record - if authenticated user is ParentGuardian, find/create corresponding User
        $user = $this->getUserForConversation($authenticatedUser, $businessId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find user account for conversations.',
            ], 404);
        }

        $perPage = (int) $request->query('per_page', 25);
        $perPage = $perPage > 0 ? min($perPage, 100) : 25;

        $conversations = Conversation::query()
            ->with([
                'users:id,name,email,profile_photo_path',
                'latestMessage' => function ($query) {
                    $query->with('sender:id,name,email,profile_photo_path');
                },
            ])
            ->where('business_id', $businessId)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        $conversations->getCollection()->transform(function (Conversation $conversation) use ($user) {
            return $this->transformConversation($conversation, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Conversations fetched successfully.',
            'data' => [
                'conversations' => $conversations->items(),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage(),
                    'has_more' => $conversations->hasMorePages(),
                ],
            ],
        ]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $businessId = $request->get('business_id');
        $authenticatedUser = $request->get('authenticated_user');
        
        // Get the User record - if authenticated user is ParentGuardian, find/create corresponding User
        $user = $this->getUserForConversation($authenticatedUser, $businessId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find user account for conversations.',
            ], 404);
        }

        if ($conversation->business_id !== $businessId || !$this->userInConversation($conversation, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 50;

        $messages = $conversation->messages()
            ->with('sender:id,name,email,profile_photo_path')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $messageItems = collect($messages->items())
            ->map(fn (Message $message) => $this->transformMessage($message, $user))
            ->values()
            ->all();

        // Mark messages as read for this user
        $this->markMessagesAsRead($conversation, $user);

        return response()->json([
            'success' => true,
            'message' => 'Messages fetched successfully.',
            'data' => [
                'messages' => $messageItems,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                    'has_more' => $messages->hasMorePages(),
                ],
            ],
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation)
    {
        $businessId = $request->get('business_id');
        $authenticatedUser = $request->get('authenticated_user');
        
        // Get the User record - if authenticated user is ParentGuardian, find/create corresponding User
        $user = $this->getUserForConversation($authenticatedUser, $businessId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find user account for conversations.',
            ], 404);
        }

        if ($conversation->business_id !== $businessId || !$this->userInConversation($conversation, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:2000',
            'type' => 'nullable|string|in:text,image,file',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,txt|max:10240', // 10MB max
        ]);

        // Ensure either content or attachment is provided
        if (empty($validated['content']) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Either message content or attachment is required.',
            ], 422);
        }

        $message = null;
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSize = null;

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentSize = $file->getSize();
            
            // Determine file type
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $validated['type'] = 'image';
                $attachmentPath = $file->store('messages/images', 'public');
            } else {
                $validated['type'] = 'file';
                $attachmentPath = $file->store('messages/files', 'public');
            }
        }

        DB::transaction(function () use ($conversation, $user, &$message, $validated, $attachmentPath, $attachmentName, $attachmentSize) {
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'content' => $validated['content'] ?? ($attachmentName ? "Sent {$attachmentName}" : ''),
                'type' => $validated['type'] ?? 'text',
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
                'is_read' => false,
            ]);

            $conversation->update(['last_message_at' => now()]);

            // Update sender participant last_read_at so their message shows as read immediately
            $participant = $conversation->participants()->where('user_id', $user->id)->first();
            if ($participant) {
                $participant->update(['last_read_at' => now()]);
            }
        });

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create message.',
            ], 500);
        }

        $message->load('sender:id,name,email,profile_photo_path');

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'message' => $this->transformMessage($message, $user),
                'conversation' => $this->transformConversation($conversation->fresh(['latestMessage.sender', 'users']), $user),
            ],
        ], 201);
    }

    public function store(Request $request)
    {
        try {
            $businessId = $request->get('business_id');
            $authenticatedUser = $request->get('authenticated_user');
            
            // Get the User record - if authenticated user is ParentGuardian, find/create corresponding User
            $user = $this->getUserForConversation($authenticatedUser, $businessId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to find user account for conversations.',
                ], 404);
            }

            $validated = $request->validate([
                'participant_ids' => 'nullable|array|min:1',
                'participant_ids.*' => 'required|integer|exists:users,id',
                'parent_email' => 'nullable|email',
                'type' => 'nullable|string|in:direct,group',
                'title' => 'nullable|string|max:255',
                'message_content' => 'nullable|string|max:2000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in ConversationController@store: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the conversation.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $participantIds = $validated['participant_ids'] ?? [];

        // If parent_email is provided, find or create user for that parent
        if (!empty($validated['parent_email'])) {
            $parent = ParentGuardian::where('email', $validated['parent_email'])
                ->where('business_id', $businessId)
                ->first();

            if (!$parent) {
                // If parent not found, try to find a user with that email (could be staff)
                $parentUser = User::where('email', $validated['parent_email'])
                    ->where('business_id', $businessId)
                    ->first();

                if ($parentUser) {
                    $participantIds[] = $parentUser->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found with the provided email.',
                    ], 404);
                }
            } else {
                // Find user with same email, or create one
                $parentUser = User::where('email', $parent->email)
                    ->where('business_id', $businessId)
                    ->first();

                if (!$parentUser) {
                    // Create a user account for the parent
                    $parentUser = User::create([
                        'name' => $parent->full_name,
                        'email' => $parent->email,
                        'business_id' => $businessId,
                        'status' => 'active',
                        'branch_id' => null, // Parents don't belong to a branch
                        'password' => '', // Empty password - parent uses ParentGuardian login
                    ]);
                }

                $participantIds[] = $parentUser->id;
            }
        }

        if (empty($participantIds)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one participant is required.',
            ], 422);
        }
        $conversationType = $validated['type'] ?? 'direct';

        // Add current user to participants if not already included
        if (!in_array($user->id, $participantIds)) {
            $participantIds[] = $user->id;
        }
        $participantIds = array_unique($participantIds);

        // For direct conversations, check if one already exists
        if ($conversationType === 'direct' && count($participantIds) === 2) {
            $existingConversation = Conversation::where('type', 'direct')
                ->where('business_id', $businessId)
                ->whereHas('users', function ($query) use ($participantIds) {
                    $query->whereIn('users.id', $participantIds);
                })
                ->withCount(['users' => function ($query) use ($participantIds) {
                    $query->whereIn('users.id', $participantIds);
                }])
                ->having('users_count', count($participantIds))
                ->first();

            if ($existingConversation) {
                // If there's a message to send, send it to the existing conversation
                if (!empty($validated['message_content'])) {
                    $message = $existingConversation->messages()->create([
                        'sender_id' => $user->id,
                        'content' => $validated['message_content'],
                        'type' => 'text',
                        'is_read' => false,
                    ]);

                    $existingConversation->update(['last_message_at' => now()]);

                    $existingConversation->load(['users:id,name,email,profile_photo_path', 'latestMessage.sender:id,name,email,profile_photo_path']);

                    return response()->json([
                        'success' => true,
                        'message' => 'Message sent to existing conversation.',
                        'data' => [
                            'conversation' => $this->transformConversation($existingConversation, $user),
                            'message' => $this->transformMessage($message, $user),
                        ],
                    ]);
                }

                $existingConversation->load(['users:id,name,email,profile_photo_path', 'latestMessage.sender:id,name,email,profile_photo_path']);

                return response()->json([
                    'success' => true,
                    'message' => 'Conversation already exists.',
                    'data' => [
                        'conversation' => $this->transformConversation($existingConversation, $user),
                    ],
                ]);
            }
        }

        // Create new conversation
        DB::beginTransaction();
        try {
            $conversation = Conversation::create([
                'type' => $conversationType,
                'title' => $validated['title'] ?? null,
                'business_id' => $businessId,
                'created_by' => $user->id,
                'last_message_at' => !empty($validated['message_content']) ? now() : null,
            ]);

            // Add participants
            foreach ($participantIds as $participantId) {
                $conversation->users()->attach($participantId, [
                    'joined_at' => now(),
                    'is_active' => true,
                    'last_read_at' => $participantId === $user->id ? now() : null,
                ]);
            }

            // Send initial message if provided
            $message = null;
            if (!empty($validated['message_content'])) {
                $message = $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'content' => $validated['message_content'],
                    'type' => 'text',
                    'is_read' => false,
                ]);
            }

            DB::commit();

            $conversation->load(['users:id,name,email,profile_photo_path', 'latestMessage.sender:id,name,email,profile_photo_path']);

            $responseData = [
                'success' => true,
                'message' => $message ? 'Conversation created and message sent.' : 'Conversation created successfully.',
                'data' => [
                    'conversation' => $this->transformConversation($conversation, $user),
                ],
            ];

            if ($message) {
                $responseData['data']['message'] = $this->transformMessage($message, $user);
            }

            return response()->json($responseData, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create conversation: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'business_id' => $businessId,
                'participant_ids' => $participantIds,
                'error' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation. Please try again.',
            ], 500);
        }
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        $businessId = $request->get('business_id');
        $authenticatedUser = $request->get('authenticated_user');
        
        // Get the User record - if authenticated user is ParentGuardian, find/create corresponding User
        $user = $this->getUserForConversation($authenticatedUser, $businessId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find user account for conversations.',
            ], 404);
        }

        if ($conversation->business_id !== $businessId || !$this->userInConversation($conversation, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $this->markMessagesAsRead($conversation, $user);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read.',
        ]);
    }

    protected function transformConversation(Conversation $conversation, User $user): array
    {
        $conversation->loadMissing(['users:id,name,email,profile_photo_path', 'latestMessage.sender:id,name,email,profile_photo_path']);

        $latestMessage = $conversation->latestMessage;
        $participant = $conversation->participants()->where('user_id', $user->id)->first();
        $unreadCount = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return [
            'id' => $conversation->id,
            'uuid' => $conversation->uuid,
            'title' => $conversation->getDisplayName($user->id),
            'type' => $conversation->type,
            'last_message' => $latestMessage ? $this->transformMessage($latestMessage, $user) : null,
            'unread_count' => $unreadCount,
            'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
            'participants' => $conversation->users->map(function (User $participantUser) use ($user) {
                return [
                    'id' => $participantUser->id,
                    'name' => $participantUser->name,
                    'email' => $participantUser->email,
                    'avatar_url' => $participantUser->profile_photo_url,
                    'is_self' => $participantUser->id === $user->id,
                ];
            })->values()->all(),
        ];
    }

    protected function transformMessage(Message $message, User $user): array
    {
        $data = [
            'id' => $message->id,
            'content' => $message->content,
            'type' => $message->type,
            'is_from_user' => $message->sender_id === $user->id,
            'is_read' => (bool) $message->is_read,
            'read_at' => optional($message->read_at)->toIso8601String(),
            'created_at' => optional($message->created_at)->toIso8601String(),
            'sender' => $message->relationLoaded('sender') && $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
                'email' => $message->sender->email,
                'avatar_url' => $message->sender->profile_photo_url,
            ] : null,
        ];

        // Add attachment info if present
        if ($message->attachment_path) {
            $data['attachment'] = [
                'path' => $message->attachment_path,
                'url' => asset('storage/' . $message->attachment_path),
                'name' => $message->attachment_name,
                'size' => $message->attachment_size,
            ];
        }

        return $data;
    }

    protected function userInConversation(Conversation $conversation, User $user): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    protected function markMessagesAsRead(Conversation $conversation, User $user): void
    {
        DB::transaction(function () use ($conversation, $user) {
            $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            $conversation->participants()
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);
        });
    }

    /**
     * Get User record for conversation operations.
     * If authenticated user is ParentGuardian, find or create corresponding User record.
     */
    protected function getUserForConversation($authenticatedUser, int $businessId): ?User
    {
        // If already a User, return it
        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        // If ParentGuardian, find or create corresponding User
        if ($authenticatedUser instanceof ParentGuardian) {
            $user = User::where('email', $authenticatedUser->email)
                ->where('business_id', $businessId)
                ->first();

            if (!$user) {
                // Create a user account for the parent
                $user = User::create([
                    'name' => $authenticatedUser->full_name,
                    'email' => $authenticatedUser->email,
                    'business_id' => $businessId,
                    'status' => 'active',
                    'branch_id' => null, // Parents don't belong to a branch
                    'password' => '', // Empty password - parent uses ParentGuardian login
                ]);
            }

            return $user;
        }

        return null;
    }
}
