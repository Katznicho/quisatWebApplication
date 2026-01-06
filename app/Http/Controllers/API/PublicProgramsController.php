<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicProgramsController extends Controller
{
    /**
     * List of programs (public Christian Kids Hub)
     */
    public function index(Request $request)
    {
        try {
            $query = Program::query()
                ->orderBy('created_at', 'desc');

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $programs = $query->get();

            $data = $programs->map(function (Program $program) {
                return $this->transformProgram($program, false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'programs' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicProgramsController::index - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching programs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single program and its events
     */
    public function show($id)
    {
        try {
            $program = Program::where(function ($q) use ($id) {
                    $q->where('uuid', $id)->orWhere('id', $id);
                })
                ->first();

            if (!$program) {
                return response()->json([
                    'success' => false,
                    'message' => 'Program not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'program' => $this->transformProgram($program, true),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PublicProgramsController::show - ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function transformProgram(Program $program, bool $includeDetails = false): array
    {
        $resolveUrl = function (?string $pathOrUrl): ?string {
            if (!$pathOrUrl) {
                return null;
            }
            if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
                return $pathOrUrl;
            }
            return Storage::url($pathOrUrl);
        };

        $ageGroupRaw = $program->{'age-group'} ?? ($program->age_group ?? null);
        $ageGroups = [];
        if (is_string($ageGroupRaw) && trim($ageGroupRaw) !== '') {
            // Accept "3-5, 6-8" or "3-5|6-8"
            $ageGroups = preg_split('/[,\|]/', $ageGroupRaw) ?: [];
            $ageGroups = array_values(array_filter(array_map('trim', $ageGroups)));
        }

        $data = [
            'id' => $program->id,
            'uuid' => $program->uuid,
            // Keep both keys to match different app screens
            'name' => $program->name,
            'title' => $program->name,
            'description' => $program->description,
            'image_url' => $resolveUrl($program->image ?? null),
            'video_url' => $resolveUrl($program->video ?? null),
            'category' => 'Christian Kids Hub',
            'is_featured' => false,
            'age_groups' => $ageGroups,
            'duration' => null,
            'schedule' => null,
            'location' => null,
            'formatted_price' => 'Free',
            'price' => 0,
            'spots_available' => 999,
            'is_full' => false,
            'status' => $program->status,
        ];

        if ($includeDetails) {
            // Fetch related events by the JSON program_ids field
            $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function (ProgramEvent $event) {
                    return [
                        'id' => $event->id,
                        'uuid' => $event->uuid,
                        'name' => $event->name,
                        'title' => $event->name,
                        'description' => $event->description,
                        'image_url' => $event->image ? Storage::url($event->image) : null,
                        'video_url' => $event->video ? Storage::url($event->video) : null,
                        'price' => $event->price !== null ? (float) $event->price : null,
                        'formatted_price' => $event->price !== null && (float) $event->price > 0 ? ('UGX ' . number_format((float) $event->price, 0)) : 'Free',
                        'start_date' => $event->start_date?->toISOString(),
                        'end_date' => $event->end_date?->toISOString(),
                        'location' => $event->location,
                        'status' => $event->status,
                        'registration_method' => $event->registration_method,
                        'registration_link' => $event->registration_link,
                        'registration_list' => $event->registration_list,
                        'organizer' => [
                            'name' => $event->organizer_name,
                            'email' => $event->organizer_email,
                            'phone' => $event->organizer_phone,
                            'address' => $event->organizer_address,
                        ],
                        'social_media_handles' => $event->social_media_handles,
                    ];
                });

            $data['events'] = $events;
            $data['total_events'] = $events->count();
        }

        return $data;
    }
}

