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
     */
    public function index(Request $request)
    {
        try {
            Log::info('PublicProgramsController::index - Starting request');
            
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
            // Get all programs (no pagination)
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
            // Get events for this program
            $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
                ->orderBy('start_date')
                ->get();

            Log::info('PublicProgramsController::show - Transforming program and events');
            $transformedProgram = $this->transformProgram($program, true);
            $transformedEvents = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'uuid' => $event->uuid,
                    'name' => $event->name,
                    'description' => $event->description,
                    'start_date' => $event->start_date ? $event->start_date->toIso8601String() : null,
                    'end_date' => $event->end_date ? $event->end_date->toIso8601String() : null,
                    'location' => $event->location,
                    'price' => (float) $event->price,
                    'status' => $event->status,
                ];
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
            $data = [
            'id' => $program->id,
            'uuid' => $program->uuid,
            'name' => $program->name,
            'description' => $program->description,
            'age_group' => $program->{'age-group'},
            'status' => $program->status,
            'total_events' => $program->total_events,
        ];

        if ($includeDetails) {
            $data['created_at'] = $program->created_at->toIso8601String();
            $data['updated_at'] = $program->updated_at->toIso8601String();
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
}

