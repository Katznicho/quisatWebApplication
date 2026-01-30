<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\Business;
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
                // Include events in list as well
                return $this->transformProgram($program, true);
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
            Log::info('========================================');
            Log::info('PublicProgramsController::show - START');
            Log::info('Requested ID:', ['id' => $id]);
            
            $program = Program::where(function ($q) use ($id) {
                    $q->where('uuid', $id)->orWhere('id', $id);
                })
                ->first();

            if (!$program) {
                Log::info('PublicProgramsController::show - Program NOT FOUND');
                return response()->json([
                    'success' => false,
                    'message' => 'Program not found',
                ], 404);
            }

            Log::info('PublicProgramsController::show - Program found:', [
                'id' => $program->id,
                'name' => $program->name,
                'uuid' => $program->uuid,
            ]);

            $transformedProgram = $this->transformProgram($program, true);
            
            Log::info('PublicProgramsController::show - Transformed program:', [
                'id' => $transformedProgram['id'],
                'name' => $transformedProgram['name'],
                'total_events' => $transformedProgram['total_events'] ?? 'not set',
                'events_count' => isset($transformedProgram['events']) ? count($transformedProgram['events']) : 'events key not present',
                'has_events_key' => isset($transformedProgram['events']),
            ]);

            if (isset($transformedProgram['events'])) {
                Log::info('PublicProgramsController::show - Events in response:', [
                    'count' => count($transformedProgram['events']),
                    'events' => $transformedProgram['events'],
                ]);
            } else {
                Log::warning('PublicProgramsController::show - NO EVENTS KEY in transformed program!');
            }

            $response = [
                'success' => true,
                'data' => [
                    'program' => $transformedProgram,
                ],
            ];

            Log::info('PublicProgramsController::show - Final response:', json_encode($response, JSON_PRETTY_PRINT));
            Log::info('PublicProgramsController::show - END');
            Log::info('========================================');

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('PublicProgramsController::show - Exception: ' . $e->getMessage());
            Log::error('PublicProgramsController::show - Stack trace: ' . $e->getTraceAsString());
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
            'social_media_handles' => $program->social_media_handles,
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
            // Fetch events for this program - matching exactly how the web app does it
            // Events are stored with program_ids as JSON array: [program_id]
            // The web app uses: ProgramEvent::whereJsonContains('program_ids', $program->id)
            $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
                ->with('business')
                ->orderBy('start_date', 'asc')
                ->get();

            // Transform events
            $transformedEvents = $events->map(function (ProgramEvent $event) {
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

            $eventsCount = $transformedEvents->count();
            $eventsArray = $transformedEvents->values()->all();
            
            // Always include events array, even if empty
            $data['events'] = $eventsArray;
            $data['total_events'] = $eventsCount;
            $data['events_count'] = $eventsCount;
            // has_events should be true only if there are actually events
            $data['has_events'] = $eventsCount > 0;

            // Get business contact information from the first event (Christian Kids Hub is business_id == 1)
            $firstEvent = $events->first();
            if ($firstEvent && $firstEvent->business) {
                $business = $firstEvent->business;
                $data['business'] = [
                    'id' => $business->id,
                    'name' => $business->name,
                    'email' => $business->email,
                    'phone' => $business->phone,
                    'address' => $business->address,
                    'shop_number' => $business->shop_number,
                    'website_link' => $business->website_link,
                    'social_media_handles' => $business->social_media_handles,
                ];
            }

            // Get contact information - prioritize program contact, then event organizer, then business
            $contactInfo = null;
            $contactEmail = null;
            $contactPhone = null;

            // First priority: Program's own contact fields
            if ($program->contact_email || $program->contact_phone) {
                $contactEmail = $program->contact_email;
                $contactPhone = $program->contact_phone;
            }
            // Second priority: Event organizer contact
            elseif ($firstEvent) {
                if ($firstEvent->organizer_email || $firstEvent->organizer_phone) {
                    $contactEmail = $firstEvent->organizer_email;
                    $contactPhone = $firstEvent->organizer_phone;
                }
            }
            // Third priority: Business contact
            if ((!$contactEmail && !$contactPhone) && isset($data['business'])) {
                $contactEmail = $data['business']['email'] ?? null;
                $contactPhone = $data['business']['phone'] ?? null;
            }

            // Build contact info string if we have any contact
            if ($contactEmail || $contactPhone) {
                $contactParts = [];
                if ($contactEmail) {
                    $contactParts[] = "Email: {$contactEmail}";
                }
                if ($contactPhone) {
                    $contactParts[] = "Phone: {$contactPhone}";
                }
                $contactInfo = implode("\n", $contactParts);
            }

            // Include contact information in the response
            $data['contact'] = [
                'info' => $contactInfo,
                'email' => $contactEmail,
                'phone' => $contactPhone,
            ];
        } else {
            $data['events'] = [];
            $data['events_count'] = 0;
            $data['has_events'] = false;
            $data['total_events'] = 0;
        }

        return $data;
    }
}

