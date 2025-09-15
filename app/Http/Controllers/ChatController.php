<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\BroadcastAnnouncement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Display the main chat interface
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's conversations with latest message
        $conversations = $user->conversations()
            ->with(['latestMessage.sender', 'users'])
            ->wherePivot('is_active', true)
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Get contacts (other users in the same business) with unread message counts
        $contacts = User::where('business_id', $user->business_id)
            ->where('id', '!=', $user->id)
            ->with('role')
            ->get()
            ->map(function ($contact) use ($user) {
                // Get unread message count for this contact
                $unreadCount = 0;
                $conversation = Conversation::where('type', 'direct')
                    ->where('business_id', $user->business_id)
                    ->whereHas('users', function ($query) use ($user, $contact) {
                        $query->whereIn('users.id', [$user->id, $contact->id]);
                    })
                    ->withCount(['users' => function ($query) use ($user, $contact) {
                        $query->whereIn('users.id', [$user->id, $contact->id]);
                    }])
                    ->having('users_count', 2)
                    ->first();

                if ($conversation) {
                    $unreadCount = $conversation->messages()
                        ->where('sender_id', $contact->id)
                        ->where('created_at', '>', $conversation->users()->where('user_id', $user->id)->first()->pivot->last_read_at ?? '1970-01-01')
                        ->count();
                }

                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'role' => $contact->role->name ?? 'No Role',
                    'avatar' => $contact->avatar_url ?? null,
                    'unread_count' => $unreadCount
                ];
            });


        return view('chat.index', compact('conversations', 'contacts'));
    }

    /**
     * Get all conversations for the current user
     */
    public function conversations()
    {
        $user = Auth::user();
        
        // Add detailed debugging
        \Log::info('=== CONVERSATIONS ENDPOINT CALLED ===');
        \Log::info('User ID: ' . $user->id);
        \Log::info('User Name: ' . $user->name);
        \Log::info('User Business ID: ' . $user->business_id);
        \Log::info('User Email: ' . $user->email);
        
        $conversations = $user->conversations()
            ->with(['latestMessage.sender', 'users'])
            ->wherePivot('is_active', true)
            ->orderBy('last_message_at', 'desc')
            ->get();
            
        \Log::info('Total conversations found: ' . $conversations->count());
        foreach ($conversations as $conv) {
            \Log::info('Conversation ID: ' . $conv->id . ', Type: ' . $conv->type . ', Business ID: ' . $conv->business_id);
            \Log::info('Participants: ' . $conv->users->pluck('name')->implode(', '));
        }
        
        $mappedConversations = $conversations->map(function ($conversation) use ($user) {
            return [
                'id' => $conversation->id,
                'uuid' => $conversation->uuid,
                'title' => $conversation->getDisplayName($user->id),
                'type' => $conversation->type,
                'last_message' => $conversation->latestMessage ? [
                    'content' => $conversation->latestMessage->content,
                    'sender_name' => $conversation->latestMessage->sender->name,
                    'created_at' => $conversation->latestMessage->created_at->diffForHumans()
                ] : null,
                'unread_count' => $this->getUnreadCount($conversation, $user),
                'participants' => $conversation->users->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'role' => $participant->role->name ?? 'No Role'
                    ];
                })
            ];
        });

        return response()->json($mappedConversations);
    }

    /**
     * Get unread count for a conversation
     */
    private function getUnreadCount($conversation, $user)
    {
        $lastReadAt = $conversation->users()
            ->wherePivot('user_id', $user->id)
            ->first()
            ->pivot
            ->last_read_at ?? null;

        if (!$lastReadAt) {
            return $conversation->messages()->count();
        }

        return $conversation->messages()
            ->where('created_at', '>', $lastReadAt)
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    /**
     * Show a specific conversation
     */
    public function show(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Check if user is participant
        if (!$conversation->users->contains($user)) {
            abort(403, 'You are not authorized to view this conversation.');
        }

        // Mark conversation as read for this user
        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.conversation', compact('conversation', 'messages'));
    }

    /**
     * Create a new conversation
     */
    public function store(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'type' => 'in:direct,group',
            'title' => 'nullable|string|max:255',
            'message_content' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $participantIds = $request->participant_ids;
        
        // Add detailed debugging
        \Log::info('=== CREATE CONVERSATION ENDPOINT CALLED ===');
        \Log::info('User ID: ' . $user->id . ' (' . $user->name . ')');
        \Log::info('User Business ID: ' . $user->business_id);
        \Log::info('Participant IDs: ' . implode(', ', $participantIds));
        \Log::info('Conversation type: ' . $request->type);
        \Log::info('Message content: ' . ($request->message_content ?? 'none'));
        
        // Add current user to participants
        $participantIds[] = $user->id;
        $participantIds = array_unique($participantIds);

        // Check if direct conversation already exists
        if ($request->type === 'direct' && count($participantIds) === 2) {
            $existingConversation = Conversation::where('type', 'direct')
                ->where('business_id', $user->business_id)
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
                if ($request->message_content) {
                    $message = $existingConversation->messages()->create([
                        'sender_id' => $user->id,
                        'content' => $request->message_content,
                        'type' => 'text'
                    ]);

                    // Update last message timestamp
                    $existingConversation->update(['last_message_at' => now()]);

                    return response()->json([
                        'conversation' => $existingConversation->load('users'),
                        'message' => $message,
                        'message_sent' => 'Message sent to existing conversation'
                    ]);
                }

                return response()->json([
                    'conversation' => $existingConversation,
                    'message' => 'Conversation already exists'
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $conversation = Conversation::create([
                'type' => $request->type ?? 'direct',
                'title' => $request->title,
                'business_id' => $user->business_id,
                'created_by' => $user->id,
                'last_message_at' => $request->message_content ? now() : null
            ]);

            // Add participants
            foreach ($participantIds as $participantId) {
                $conversation->users()->attach($participantId, [
                    'joined_at' => now(),
                    'is_active' => true
                ]);
            }

            // Send initial message if provided
            $message = null;
            if ($request->message_content) {
                $message = $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'content' => $request->message_content,
                    'type' => 'text'
                ]);
            }

            DB::commit();

            return response()->json([
                'conversation' => $conversation->load('users'),
                'message' => $message,
                'message_sent' => $message ? 'Conversation created and message sent' : 'Conversation created successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to create conversation: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create conversation'], 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Add detailed debugging
        \Log::info('=== GET MESSAGES ENDPOINT CALLED ===');
        \Log::info('Conversation ID: ' . $conversation->id);
        \Log::info('User ID: ' . $user->id . ' (' . $user->name . ')');
        \Log::info('User Business ID: ' . $user->business_id);
        \Log::info('Conversation Business ID: ' . $conversation->business_id);
        \Log::info('Conversation participants: ' . $conversation->users->pluck('id')->implode(', '));
        \Log::info('Participant names: ' . $conversation->users->pluck('name')->implode(', '));
        
        // Check if user is participant
        if (!$conversation->users->contains($user)) {
            \Log::warning('=== AUTHORIZATION FAILED ===');
            \Log::warning('User ' . $user->id . ' (' . $user->name . ') is not a participant in conversation ' . $conversation->id);
            \Log::warning('Conversation participants: ' . $conversation->users->pluck('name')->implode(', '));
            abort(403, 'You are not authorized to view this conversation.');
        }
        
        \Log::info('=== AUTHORIZATION SUCCESSFUL ===');

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'created_at' => $message->created_at->toISOString(),
                    'updated_at' => $message->updated_at->toISOString()
                ];
            });

        return response()->json($messages);
    }

    /**
     * Send a message to a conversation
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'type' => 'in:text,image,file'
        ]);

        $user = Auth::user();
        
        // Add detailed debugging
        \Log::info('=== SEND MESSAGE ENDPOINT CALLED ===');
        \Log::info('Conversation ID: ' . $conversation->id);
        \Log::info('User ID: ' . $user->id . ' (' . $user->name . ')');
        \Log::info('Message content: ' . $request->content);
        \Log::info('Message type: ' . ($request->type ?? 'text'));
        
        // Check if user is participant
        if (!$conversation->users->contains($user)) {
            \Log::warning('=== SEND MESSAGE AUTHORIZATION FAILED ===');
            \Log::warning('User ' . $user->id . ' (' . $user->name . ') is not a participant in conversation ' . $conversation->id);
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        \Log::info('=== SEND MESSAGE AUTHORIZATION SUCCESSFUL ===');

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->content,
            'type' => $request->type ?? 'text'
        ]);

        // Update conversation's last message time
        $conversation->update(['last_message_at' => $message->created_at]);

        return response()->json([
            'message' => $message->load('sender'),
            'success' => true
        ]);
    }


    /**
     * Mark conversation as read
     */
    public function markAsRead(Conversation $conversation)
    {
        $user = Auth::user();
        
        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get contacts for the current user
     */
    public function getContacts()
    {
        $user = Auth::user();
        
        $contacts = User::where('business_id', $user->business_id)
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->with('role')
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'role' => $contact->role->name ?? 'No Role',
                    'email' => $contact->email,
                    'profile_photo_url' => $contact->profile_photo_url
                ];
            });

        return response()->json($contacts);
    }

    /**
     * Send broadcast announcement
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'in:general,urgent,info',
            'target_roles' => 'nullable|array',
            'target_users' => 'nullable|array'
        ]);

        $user = Auth::user();
        
        $broadcast = BroadcastAnnouncement::create([
            'business_id' => $user->business_id,
            'sender_id' => $user->id,
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type ?? 'general',
            'target_roles' => $request->target_roles,
            'target_users' => $request->target_users,
            'status' => 'sent',
            'sent_at' => now()
        ]);

        return response()->json([
            'broadcast' => $broadcast,
            'message' => 'Broadcast sent successfully'
        ]);
    }

    /**
     * Create a meeting for video calls
     */
    public function createMeeting(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:zoom,meet,teams,jitsi',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:users,id'
        ]);

        $user = Auth::user();
        $meetingId = $this->generateMeetingId();
        $platform = $request->platform;
        
        // Generate platform-specific meeting URLs
        $meetingUrls = [
            'zoom' => "https://zoom.us/j/{$meetingId}",
            'meet' => "https://meet.google.com/{$meetingId}",
            'teams' => "https://teams.microsoft.com/l/meetup-join/{$meetingId}",
            'jitsi' => "https://meet.jit.si/{$meetingId}"
        ];

        // Create a system message about the meeting
        $participants = User::whereIn('id', $request->participant_ids)->get();
        $participantNames = $participants->pluck('name')->join(', ');
        
        $meetingMessage = "📹 Video meeting started on {$platform}\n";
        $meetingMessage .= "Meeting ID: {$meetingId}\n";
        $meetingMessage .= "Join link: {$meetingUrls[$platform]}\n";
        $meetingMessage .= "Participants: {$participantNames}";

        return response()->json([
            'meeting_id' => $meetingId,
            'platform' => $platform,
            'meeting_url' => $meetingUrls[$platform],
            'message' => $meetingMessage,
            'success' => true
        ]);
    }

    /**
     * Generate a unique meeting ID
     */
    private function generateMeetingId()
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
    }
}
