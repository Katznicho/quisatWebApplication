<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicProgramsController extends Controller
{
    /**
     * List all programs (public, no authentication required)
     * Returns Programs with their event counts for Christian Kids Hub
     */
    public function index(Request $request)
    {
        try {
            Log::info('PublicProgramsController::index - Starting request');
            
            // Query Programs
            $query = Program::query()
                ->where(function($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status');
                })
                ->orderBy('name');

            // Search
            if ($search = $request->query('search')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            Log::info('PublicProgramsController::index - Executing query');
            // Get all programs
            $programs = $query->get();
            Log::info('PublicProgramsController::index - Found ' . $programs->count() . ' programs');

            Log::info('PublicProgramsController::index - Transforming programs');
            $transformedPrograms = $programs->map(function (Program $program) {
                return $this->transformProgram($program);
            });

            Log::info('PublicProgramsController::index - Returning response');
            return response()->json([
                'success' => true,
                'message' => 'Programs retrieved successfully.',
                'data' => [
                    'programs' => $transformedPrograms,
                    'total' => $transformedPrograms->count(),
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicProgramsController::index - Error: ' . $errorMessage, [
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving programs.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        }
    }

    /**
     * Show a single program with its events (public)
     */
    public function show($id)
    {
        try {
            Log::info('PublicProgramsController::show - Requesting program ID: ' . $id);
            
            $program = Program::where(function($q) use ($id) {
                    $q->where('id', $id)
                      ->orWhere('uuid', $id);
                })
                ->where(function($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status');
                })
                ->first();

            if (!$program) {
                Log::warning('PublicProgramsController::show - Program not found: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Program not found.',
                ], 404);
            }

            Log::info('PublicProgramsController::show - Getting program events');
            // Get events for this program (events that have this program in their program_ids)
            $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
                ->where(function($q) {
                    $q->where('status', 'active')
                      ->orWhere('status', 'published')
                      ->orWhereNull('status');
                })
                ->orderBy('start_date')
                ->with(['business', 'user'])
                ->get();

            Log::info('PublicProgramsController::show - Transforming program and events');
            $transformedProgram = $this->transformProgram($program, true);
            $transformedEvents = $events->map(function (ProgramEvent $event) {
                return $this->transformProgramEvent($event, true);
            });

            return response()->json([
                'success' => true,
                'message' => 'Program retrieved successfully.',
                'data' => [
                    'program' => $transformedProgram,
                    'events' => $transformedEvents,
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicProgramsController::show - Error: ' . $errorMessage, [
                'id' => $id,
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the program.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        }
    }

    /**
     * Transform program for API response
     */
    protected function transformProgram(Program $program, bool $includeDetails = false): array
    {
        try {
            // Get events for this program
            $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
                ->where(function($q) {
                    $q->where('status', 'active')
                      ->orWhere('status', 'published')
                      ->orWhereNull('status');
                })
                ->count();

            // Get program's own image/video if available, otherwise fall back to first event's image
            $imageUrl = null;
            $videoUrl = null;
            
            if ($program->image) {
                $imageUrl = str_starts_with($program->image, 'http') 
                    ? $program->image 
                    : asset('storage/' . $program->image);
            } elseif ($program->video) {
                $videoUrl = str_starts_with($program->video, 'http') 
                    ? $program->video 
                    : asset('storage/' . $program->video);
            } else {
                // Fall back to first event's image if program has no media
                $firstEvent = ProgramEvent::whereJsonContains('program_ids', $program->id)
                    ->where(function($q) {
                        $q->where('status', 'active')
                          ->orWhere('status', 'published')
                          ->orWhereNull('status');
                    })
                    ->whereNotNull('image')
                    ->first();

                if ($firstEvent && $firstEvent->image) {
                    $imageUrl = str_starts_with($firstEvent->image, 'http') 
                        ? $firstEvent->image 
                        : asset('storage/' . $firstEvent->image);
                }
            }

            $data = [
                'id' => $program->id,
                'uuid' => $program->uuid,
                'title' => $program->name,
                'description' => $program->description,
                'image_url' => $imageUrl,
                'video_url' => $videoUrl,
                'category' => $program->name, // Use program name as category
                'is_featured' => false,
                'age_groups' => [$program->{'age-group'} ?? 'All Ages'],
                'duration' => 'Ongoing',
                'schedule' => 'See events',
                'status' => $program->status ?? 'active',
                'spots_available' => 999,
                'is_full' => false,
                'formatted_price' => 'See events',
                'price' => 0,
                'total_events' => $events,
            ];

            if ($includeDetails) {
                $data['created_at'] = $program->created_at->toIso8601String();
                $data['updated_at'] = $program->updated_at->toIso8601String();
                
                // Get business details from the first event
                $firstEventWithBusiness = ProgramEvent::whereJsonContains('program_ids', $program->id)
                    ->where(function($q) {
                        $q->where('status', 'active')
                          ->orWhere('status', 'published')
                          ->orWhereNull('status');
                    })
                    ->with('business')
                    ->first();
                
                if ($firstEventWithBusiness && $firstEventWithBusiness->business) {
                    $data['business'] = [
                        'id' => $firstEventWithBusiness->business->id,
                        'name' => $firstEventWithBusiness->business->name,
                        'email' => $firstEventWithBusiness->business->email,
                        'phone' => $firstEventWithBusiness->business->phone,
                        'address' => $firstEventWithBusiness->business->address,
                        'shop_number' => $firstEventWithBusiness->business->shop_number,
                        'social_media_handles' => $firstEventWithBusiness->business->social_media_handles ?: [],
                    ];
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('PublicProgramsController::transformProgram - Error: ' . $e->getMessage(), [
                'program_id' => $program->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Transform program event for API response
     */
    protected function transformProgramEvent(ProgramEvent $event, bool $includeDetails = false): array
    {
        try {
            // Load programs from program_ids JSON array
            $programIds = $event->program_ids ?? [];
            $programs = [];
            if (!empty($programIds) && is_array($programIds)) {
                $programs = Program::whereIn('id', $programIds)->get(['id', 'name']);
            }
            $programName = $programs->first()?->name ?? 'Program';
            $category = $programs->first()?->name ?? 'General';

            // Format price
            $price = (float) $event->price;
            $formattedPrice = $price > 0 ? 'UGX ' . number_format($price, 2) : 'Free';

            // Calculate spots (if needed - you may want to add max_participants to program_events)
            $spotsAvailable = 999; // Default unlimited
            $isFull = false;

            $data = [
                'id' => $event->id,
                'uuid' => $event->uuid,
                'title' => $event->name,
                'description' => $event->description,
                'image_url' => $event->image ? (str_starts_with($event->image, 'http') ? $event->image : asset('storage/' . $event->image)) : null,
                'video_url' => $event->video ? (str_starts_with($event->video, 'http') ? $event->video : asset('storage/' . $event->video)) : null,
                'category' => $category,
                'is_featured' => false, // You may want to add this field
                'age_groups' => [], // You may want to add this field
                'duration' => $event->start_date && $event->end_date ? $this->formatDuration($event->start_date, $event->end_date) : 'N/A',
                'schedule' => $event->start_date && $event->end_date ? $this->formatSchedule($event->start_date, $event->end_date) : 'N/A',
                'status' => $event->status,
                'spots_available' => $spotsAvailable,
                'is_full' => $isFull,
                'formatted_price' => $formattedPrice,
                'price' => $price,
                'location' => $event->location,
                'start_date' => $event->start_date ? $event->start_date->toIso8601String() : null,
                'end_date' => $event->end_date ? $event->end_date->toIso8601String() : null,
            ];

            if ($includeDetails) {
                $data['registration_method'] = $event->registration_method;
                $data['registration_link'] = $event->registration_link;
                $data['registration_list'] = $event->registration_list ?: [];
                $data['social_media_handles'] = $event->social_media_handles ?: [];
                $data['organizer'] = [
                    'name' => $event->organizer_name,
                    'email' => $event->organizer_email,
                    'phone' => $event->organizer_phone,
                    'address' => $event->organizer_address,
                ];
                $data['business'] = $event->business ? [
                    'id' => $event->business->id,
                    'name' => $event->business->name,
                    'email' => $event->business->email,
                    'phone' => $event->business->phone,
                    'address' => $event->business->address,
                    'shop_number' => $event->business->shop_number,
                    'social_media_handles' => $event->business->social_media_handles ?: [],
                    'website_link' => $event->business->website_link,
                ] : null;
                $data['creator'] = $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                ] : null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('PublicProgramsController::transformProgramEvent - Error: ' . $e->getMessage(), [
                'event_id' => $event->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    protected function formatDuration($start, $end): string
    {
        if (!$start || !$end) {
            return 'N/A';
        }
        
        $startDate = $start instanceof \Carbon\Carbon ? $start : \Carbon\Carbon::parse($start);
        $endDate = $end instanceof \Carbon\Carbon ? $end : \Carbon\Carbon::parse($end);
        
        $diffDays = $startDate->diffInDays($endDate);
        if ($diffDays > 0) {
            return $diffDays . ' day' . ($diffDays > 1 ? 's' : '');
        }
        
        $diffHours = $startDate->diffInHours($endDate);
        if ($diffHours > 0) {
            return $diffHours . ' hour' . ($diffHours > 1 ? 's' : '');
        }
        
        return $startDate->diffInMinutes($endDate) . ' minutes';
    }

    protected function formatSchedule($start, $end): string
    {
        if (!$start || !$end) {
            return 'N/A';
        }
        
        $startDate = $start instanceof \Carbon\Carbon ? $start : \Carbon\Carbon::parse($start);
        $endDate = $end instanceof \Carbon\Carbon ? $end : \Carbon\Carbon::parse($end);
        
        $startDay = $startDate->format('l');
        $startTime = $startDate->format('g:i A');
        $endTime = $endDate->format('g:i A');

        if ($startDate->isSameDay($endDate)) {
            return "{$startDay} {$startTime} - {$endTime}";
        }

        $endDay = $endDate->format('l');
        return "{$startDay} {$startTime} - {$endDay} {$endTime}";
    }
}

