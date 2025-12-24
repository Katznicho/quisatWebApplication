<?php

namespace App\Http\Controllers;

use App\Models\KidsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KidsEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = KidsEvent::with(['business', 'creator'])
            ->byBusiness(Auth::user()->business_id);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by host organization
        if ($request->has('host_organization') && $request->host_organization) {
            $query->where('host_organization', 'like', '%' . $request->host_organization . '%');
        }

        // Search (title and description only, host_organization has its own filter)
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(12);

        // Get statistics
        $stats = [
            'total_events' => KidsEvent::byBusiness(Auth::user()->business_id)->count(),
            'upcoming_events' => KidsEvent::byBusiness(Auth::user()->business_id)->upcoming()->count(),
            'ongoing_events' => KidsEvent::byBusiness(Auth::user()->business_id)->ongoing()->count(),
            'completed_events' => KidsEvent::byBusiness(Auth::user()->business_id)->completed()->count(),
        ];

        return view('kids-events.index', compact('events', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('kids-events.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'host_organization' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'requires_parent_permission' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'target_age_groups' => 'nullable|array',
            'target_age_groups.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
            'contact_info' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('kids-events', 'public');
        }

        $validated['business_id'] = Auth::user()->business_id;
        $validated['created_by'] = Auth::id();
        $validated['current_participants'] = 0;

        KidsEvent::create($validated);

        return redirect()->route('kids-events.index')
            ->with('success', 'Kids event created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(KidsEvent $kidsEvent)
    {
        $kidsEvent->load(['business', 'creator']);
        return view('kids-events.show', compact('kidsEvent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KidsEvent $kidsEvent)
    {
        return view('kids-events.edit', compact('kidsEvent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KidsEvent $kidsEvent)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'host_organization' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'requires_parent_permission' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'target_age_groups' => 'nullable|array',
            'target_age_groups.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
            'contact_info' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($kidsEvent->image_url) {
                Storage::disk('public')->delete($kidsEvent->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('kids-events', 'public');
        }

        $kidsEvent->update($validated);

        return redirect()->route('kids-events.show', $kidsEvent)
            ->with('success', 'Kids event updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KidsEvent $kidsEvent)
    {
        // Delete image if exists
        if ($kidsEvent->image_url) {
            Storage::disk('public')->delete($kidsEvent->image_url);
        }

        $kidsEvent->delete();

        return redirect()->route('kids-events.index')
            ->with('success', 'Kids event deleted successfully!');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(KidsEvent $kidsEvent)
    {
        $kidsEvent->update(['is_featured' => !$kidsEvent->is_featured]);

        return response()->json([
            'success' => true,
            'is_featured' => $kidsEvent->is_featured
        ]);
    }

    /**
     * Update event status
     */
    public function updateStatus(Request $request, KidsEvent $kidsEvent)
    {
        $validated = $request->validate([
            'status' => 'required|in:upcoming,ongoing,completed,cancelled'
        ]);

        $kidsEvent->update($validated);

        return response()->json([
            'success' => true,
            'status' => $kidsEvent->status
        ]);
    }
}
