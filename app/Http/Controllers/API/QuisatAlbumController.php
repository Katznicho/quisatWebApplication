<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\QuisatAlbum;
use App\Models\QuisatAlbumComment;
use App\Models\QuisatAlbumLike;
use App\Models\User;
use App\Services\QuisatAlbumNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuisatAlbumController extends Controller
{
    public function __construct(
        protected QuisatAlbumNotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $businessIds = collect([(int) $businessId])->filter();
        if ($user instanceof ParentGuardian) {
            $churchIds = $user->scopedChurchBusinessIds((int) $businessId);
            if ($churchIds) {
                $businessIds = collect($churchIds);
            }
        }

        $query = QuisatAlbum::query()
            ->with(['classRoom:id,name,code', 'media', 'likes'])
            ->withCount(['media', 'likes', 'comments'])
            ->whereIn('business_id', $businessIds->all() ?: [(int) $businessId])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($user instanceof ParentGuardian) {
            $query->where('status', 'published');
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($classId = $request->query('class_room_id')) {
            $query->where('class_room_id', $classId);
        }

        $albums = $query->paginate(min((int) $request->query('per_page', 20), 50));
        $parentId = $user instanceof ParentGuardian ? $user->id : null;

        $albums->getCollection()->transform(function (QuisatAlbum $album) use ($parentId) {
            return $this->transformAlbum($album, $parentId, false);
        });

        return response()->json([
            'success' => true,
            'message' => 'Moments loaded.',
            'data' => [
                'albums' => $albums->items(),
                'pagination' => [
                    'current_page' => $albums->currentPage(),
                    'last_page' => $albums->lastPage(),
                    'has_more' => $albums->hasMorePages(),
                ],
            ],
        ]);
    }

    public function show(Request $request, QuisatAlbum $album)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        if ((int) $album->business_id !== (int) $businessId) {
            return response()->json(['success' => false, 'message' => 'Album not found.'], 404);
        }

        if ($user instanceof ParentGuardian && ! $this->parentCanView($user, $album)) {
            return response()->json(['success' => false, 'message' => 'This album is private to the class.'], 403);
        }

        $album->load([
            'classRoom:id,name,code',
            'media.tags.student:id,first_name,last_name',
            'likes',
            'comments.parentGuardian:id,first_name,last_name',
            'comments.user:id,name',
        ]);

        if ($user instanceof ParentGuardian) {
            $album->setRelation('comments', $album->comments->filter(function ($comment) use ($user) {
                return (int) $comment->parent_guardian_id === (int) $user->id
                    || $comment->user_id;
            })->values());
        }

        return response()->json([
            'success' => true,
            'data' => [
                'album' => $this->transformAlbum($album, $user instanceof ParentGuardian ? $user->id : null, true),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->get('business_id');
        $auth = $request->get('authenticated_user');

        if (! $auth instanceof User) {
            return response()->json(['success' => false, 'message' => 'Only staff can create albums.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'nullable|in:class_daily,event,official_class_photo',
            'class_room_id' => 'nullable|exists:class_rooms,id',
            'calendar_event_id' => 'nullable|exists:calendar_events,id',
            'status' => 'nullable|in:draft,published',
            'is_hd_paid' => 'nullable|boolean',
            'hd_price' => 'nullable|numeric|min:0',
            'photos' => 'nullable|array|max:40',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'tagged_student_ids' => 'nullable|array',
            'tagged_student_ids.*' => 'integer|exists:students,id',
        ]);

        $album = QuisatAlbum::create([
            'business_id' => $businessId,
            'created_by' => $auth->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'class_daily',
            'class_room_id' => $validated['class_room_id'] ?? null,
            'calendar_event_id' => $validated['calendar_event_id'] ?? null,
            'status' => $validated['status'] ?? 'published',
            'is_hd_paid' => (bool) ($validated['is_hd_paid'] ?? false),
            'hd_price' => $validated['hd_price'] ?? null,
            'published_at' => ($validated['status'] ?? 'published') === 'published' ? now() : null,
        ]);

        $this->storePhotos($request, $album, $auth->id, $validated['tagged_student_ids'] ?? []);

        if ($album->isPublished()) {
            try {
                $this->notifications->notifyPublished($album->fresh(['classRoom', 'media']));
                if (! empty($validated['tagged_student_ids'])) {
                    $this->notifications->notifyTagged($album, $validated['tagged_student_ids']);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Album created.',
            'data' => ['album' => $this->transformAlbum($album->fresh(['classRoom', 'media']), null, true)],
        ], 201);
    }

    public function addMedia(Request $request, QuisatAlbum $album)
    {
        $auth = $request->get('authenticated_user');
        if (! $auth instanceof User || (int) $album->business_id !== (int) $request->get('business_id')) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $request->validate([
            'photos' => 'required|array|max:40',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'tagged_student_ids' => 'nullable|array',
            'tagged_student_ids.*' => 'integer|exists:students,id',
        ]);

        $this->storePhotos($request, $album, $auth->id, $request->input('tagged_student_ids', []));

        if ($album->isPublished()) {
            $this->notifications->notifyPublished($album->fresh(['classRoom', 'media']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Photos added.',
            'data' => ['album' => $this->transformAlbum($album->fresh(['classRoom', 'media']), null, true)],
        ]);
    }

    public function like(Request $request, QuisatAlbum $album)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian || ! $this->parentCanView($user, $album)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $like = QuisatAlbumLike::firstOrCreate([
            'album_id' => $album->id,
            'media_id' => $request->input('media_id'),
            'parent_guardian_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => true,
                'likes_count' => $album->likes()->count(),
                'like_id' => $like->id,
            ],
        ]);
    }

    public function unlike(Request $request, QuisatAlbum $album)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        QuisatAlbumLike::query()
            ->where('album_id', $album->id)
            ->where('parent_guardian_id', $user->id)
            ->when($request->filled('media_id'), fn ($q) => $q->where('media_id', $request->input('media_id')))
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['liked' => false, 'likes_count' => $album->likes()->count()],
        ]);
    }

    public function comment(Request $request, QuisatAlbum $album)
    {
        $user = $request->get('authenticated_user');
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'media_id' => 'nullable|exists:quisat_album_media,id',
        ]);

        if ($user instanceof ParentGuardian) {
            if (! $this->parentCanView($user, $album)) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
            $comment = QuisatAlbumComment::create([
                'album_id' => $album->id,
                'media_id' => $validated['media_id'] ?? null,
                'parent_guardian_id' => $user->id,
                'body' => $validated['body'],
                'visible_to_staff_only' => true,
            ]);
        } elseif ($user instanceof User) {
            $comment = QuisatAlbumComment::create([
                'album_id' => $album->id,
                'media_id' => $validated['media_id'] ?? null,
                'user_id' => $user->id,
                'body' => $validated['body'],
                'visible_to_staff_only' => false,
            ]);
        } else {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => $user instanceof ParentGuardian ? $user->full_name : $user->name,
                    'created_at' => $comment->created_at->toIso8601String(),
                ],
            ],
        ], 201);
    }

    public function destroyMedia(Request $request, QuisatAlbum $album, QuisatAlbumMedia $media)
    {
        $auth = $request->get('authenticated_user');
        if (! $auth instanceof User || (int) $album->business_id !== (int) $request->get('business_id')) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if ((int) $media->album_id !== (int) $album->id) {
            return response()->json(['success' => false, 'message' => 'Photo not found.'], 404);
        }

        if ($media->path) {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        return response()->json(['success' => true, 'message' => 'Photo removed.']);
    }

    protected function storePhotos(Request $request, QuisatAlbum $album, int $uploaderId, array $taggedStudentIds): void
    {
        $files = $request->file('photos');
        if (! $files) {
            return;
        }

        $files = is_array($files) ? $files : [$files];
        $taggedStudentIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $taggedStudentIds ?: (array) $request->input('tagged_student_ids', [])
        ))));

        $sort = (int) $album->media()->max('sort_order');
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }
            $sort++;
            $media = QuisatAlbumMedia::create([
                'album_id' => $album->id,
                'uploaded_by' => $uploaderId,
                'path' => $file->store('quisat-moments', 'public'),
                'media_type' => 'photo',
                'sort_order' => $sort,
            ]);

            foreach ($taggedStudentIds as $studentId) {
                $media->tags()->firstOrCreate(['student_id' => $studentId]);
            }
        }
    }

    protected function parentCanView(ParentGuardian $parent, QuisatAlbum $album): bool
    {
        if (! $album->isPublished()) {
            return false;
        }

        return $parent->belongsToBusiness((int) $album->business_id);
    }

    protected function transformAlbum(QuisatAlbum $album, ?int $parentId, bool $includeMedia): array
    {
        $cover = $album->media->first();
        $liked = $parentId
            ? $album->likes->contains(fn ($like) => (int) $like->parent_guardian_id === $parentId && $like->media_id === null)
            : false;

        $data = [
            'id' => $album->id,
            'uuid' => $album->uuid,
            'title' => $album->title,
            'description' => $album->description,
            'type' => $album->type,
            'status' => $album->status,
            'class_room' => $album->classRoom ? [
                'id' => $album->classRoom->id,
                'name' => $album->classRoom->name,
            ] : null,
            'cover_url' => $cover?->url,
            'photos_count' => $album->media_count ?? $album->media->count(),
            'likes_count' => $album->likes_count ?? $album->likes()->count(),
            'comments_count' => $album->comments_count ?? $album->comments()->count(),
            'liked' => $liked,
            'is_hd_paid' => (bool) $album->is_hd_paid,
            'hd_price' => $album->hd_price,
            'published_at' => optional($album->published_at)->toIso8601String(),
        ];

        if ($includeMedia) {
            $data['photos'] = $album->media->map(function (QuisatAlbumMedia $media) {
                return [
                    'id' => $media->id,
                    'uuid' => $media->uuid,
                    'url' => $media->url,
                    'caption' => $media->caption,
                    'tagged_students' => $media->relationLoaded('tags')
                        ? $media->tags->map(fn ($tag) => [
                            'id' => $tag->student?->id,
                            'name' => $tag->student?->full_name,
                        ])->filter(fn ($row) => $row['id'])->values()
                        : [],
                ];
            })->values();

            $data['comments'] = $album->relationLoaded('comments')
                ? $album->comments->map(fn (QuisatAlbumComment $comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => $comment->parentGuardian?->full_name ?: $comment->user?->name ?: 'Staff',
                    'from_parent' => (bool) $comment->parent_guardian_id,
                    'created_at' => optional($comment->created_at)->toIso8601String(),
                ])->values()
                : [];
        }

        return $data;
    }
}
