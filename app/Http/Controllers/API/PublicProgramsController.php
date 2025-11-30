<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramEvent;
use Illuminate\Http\Request;

class PublicProgramsController extends Controller
{
    /**
     * List all programs (public, no authentication required)
     */
    public function index(Request $request)
    {
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

        // Get all programs (no pagination)
        $programs = $query->get();

        $transformedPrograms = $programs->map(function (Program $program) {
            return $this->transformProgram($program);
        });

        return response()->json([
            'success' => true,
            'message' => 'Programs retrieved successfully.',
            'data' => [
                'programs' => $transformedPrograms,
                'total' => $transformedPrograms->count(),
            ],
        ]);
    }

    /**
     * Show a single program with its events (public)
     */
    public function show($id)
    {
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
            return response()->json([
                'success' => false,
                'message' => 'Program not found.',
            ], 404);
        }

        // Get events for this program
        $events = ProgramEvent::whereJsonContains('program_ids', $program->id)
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Program retrieved successfully.',
            'data' => [
                'program' => $this->transformProgram($program, true),
                'events' => $events->map(function ($event) {
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
                }),
            ],
        ]);
    }

    /**
     * Transform program for API response
     */
    protected function transformProgram(Program $program, bool $includeDetails = false): array
    {
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
    }
}

