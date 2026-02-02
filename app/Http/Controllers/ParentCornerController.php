<?php

namespace App\Http\Controllers;

use App\Models\ParentCorner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ParentCornerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ParentCorner::with(['business', 'creator'])
            ->byBusiness(Auth::user()->business_id);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $parentCorners = $query->orderBy('start_date', 'desc')->paginate(12);

        // Get statistics
        $stats = [
            'total' => ParentCorner::byBusiness(Auth::user()->business_id)->count(),
            'upcoming' => ParentCorner::byBusiness(Auth::user()->business_id)->upcoming()->count(),
            'ongoing' => ParentCorner::byBusiness(Auth::user()->business_id)->ongoing()->count(),
            'completed' => ParentCorner::byBusiness(Auth::user()->business_id)->completed()->count(),
        ];

        return view('parent-corners.index', compact('parentCorners', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('parent-corners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'contact_info' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'organizer_name' => 'nullable|string|max:255',
            'organizer_email' => 'nullable|email|max:255',
            'organizer_phone' => 'nullable|string|max:255',
            'organizer_address' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('parent-corners', 'public');
        }

        $validated['business_id'] = Auth::user()->business_id;
        $validated['created_by'] = Auth::id();
        $validated['current_participants'] = 0;
        $validated['status'] = 'draft'; // Default status is draft

        ParentCorner::create($validated);

        return redirect()->route('parent-corners.index')
            ->with('success', 'Parent Corner event created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ParentCorner $parentCorner)
    {
        $parentCorner->load(['business', 'creator', 'registrations']);
        
        // Prepare registrations data for JavaScript
        $registrationsData = $parentCorner->registrations->map(function($reg) {
            return [
                'uuid' => $reg->uuid,
                'parent_name' => $reg->parent_name,
                'parent_email' => $reg->parent_email,
                'parent_phone' => $reg->parent_phone,
                'parent_address' => $reg->parent_address,
                'number_of_children' => $reg->number_of_children,
                'interests' => $reg->interests,
                'notes' => $reg->notes,
                'payment_method' => $reg->payment_method,
                'payment_status' => $reg->payment_status,
                'registration_status' => $reg->registration_status,
                'created_at' => $reg->created_at->format('M d, Y \a\t g:i A'),
            ];
        })->values();
        
        return view('parent-corners.show', compact('parentCorner', 'registrationsData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ParentCorner $parentCorner)
    {
        return view('parent-corners.edit', compact('parentCorner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ParentCorner $parentCorner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,published,completed,cancelled',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'contact_info' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'organizer_name' => 'nullable|string|max:255',
            'organizer_email' => 'nullable|email|max:255',
            'organizer_phone' => 'nullable|string|max:255',
            'organizer_address' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($parentCorner->image_url) {
                Storage::disk('public')->delete($parentCorner->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('parent-corners', 'public');
        }

        $parentCorner->update($validated);

        return redirect()->route('parent-corners.show', $parentCorner)
            ->with('success', 'Parent Corner event updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParentCorner $parentCorner)
    {
        // Delete image if exists
        if ($parentCorner->image_url) {
            Storage::disk('public')->delete($parentCorner->image_url);
        }

        $parentCorner->delete();

        return redirect()->route('parent-corners.index')
            ->with('success', 'Parent Corner event deleted successfully!');
    }

    /**
     * Store a new parent registration (web form)
     */
    public function storeRegistration(Request $request, $id)
    {
        try {
            $parentCorner = ParentCorner::findOrFail($id);
            
            // Check if event is full
            if ($parentCorner->is_full) {
                return redirect()->back()->with('error', 'This event is fully booked.');
            }
            
            $validated = $request->validate([
                'parent_name' => 'required|string|max:255',
                'parent_email' => 'required|email|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_address' => 'nullable|string|max:255',
                'interests' => 'nullable|string',
                'payment_method' => 'required|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
                'notes' => 'nullable|string',
            ]);
            
            $registration = \App\Models\ParentCornerRegistration::create([
                'parent_corner_id' => $parentCorner->id,
                'user_id' => Auth::id(), // Will be null for guest registrations
                'parent_name' => $validated['parent_name'],
                'parent_email' => $validated['parent_email'],
                'parent_phone' => $validated['parent_phone'],
                'parent_address' => $validated['parent_address'] ?? null,
                'interests' => $validated['interests'] ?? null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'registration_status' => 'confirmed',
                'payment_status' => $validated['payment_method'] === 'cash' ? 'pending' : 'pending',
            ]);
            
            // Increment current participants
            $parentCorner->increment('current_participants');
            
            return redirect()->back()->with('success', 'Parent registered successfully!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error registering parent: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to register parent. Please try again.')->withInput();
        }
    }

    /**
     * Delete a parent registration
     */
    public function destroyRegistration($uuid)
    {
        try {
            $registration = \App\Models\ParentCornerRegistration::where('uuid', $uuid)->firstOrFail();
            
            // Decrement participant count
            $parentCorner = $registration->parentCorner;
            if ($parentCorner && $parentCorner->current_participants > 0) {
                $parentCorner->decrement('current_participants');
            }
            
            // Soft delete the registration
            $registration->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration deleted successfully!',
                ]);
            }
            
            return redirect()->back()->with('success', 'Registration deleted successfully!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting parent corner registration: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete registration.',
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to delete registration.');
        }
    }
}
