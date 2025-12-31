<?php

namespace App\Http\Controllers;

use App\Models\KidsFunVenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KidsFunVenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;
        
        $query = KidsFunVenue::where('business_id', $business->id)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $venues = $query->paginate(10);

        return view('kids-fun-venues.index', compact('venues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $business = $user->business;
        
        return view('kids-fun-venues.create', compact('business'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'activities' => 'nullable|array',
            'activities.*' => 'string|max:255',
            'prices' => 'nullable|array',
            'prices.*' => 'string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'website_link' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'booking_link' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published',
        ]);

        try {
            // Handle images upload
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('kids-fun-venues', 'public');
                    $imagePaths[] = $path;
                }
            }

            // Build social media handles array
            $socialMediaHandles = [];
            if ($request->filled('facebook')) {
                $socialMediaHandles['facebook'] = $request->facebook;
            }
            if ($request->filled('instagram')) {
                $socialMediaHandles['instagram'] = $request->instagram;
            }
            if ($request->filled('twitter')) {
                $socialMediaHandles['twitter'] = $request->twitter;
            }
            if ($request->filled('whatsapp')) {
                $socialMediaHandles['whatsapp'] = $request->whatsapp;
            }

            $venue = KidsFunVenue::create([
                'business_id' => $business->id,
                'created_by' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'],
                'open_time' => $validated['open_time'],
                'close_time' => $validated['close_time'],
                'activities' => $validated['activities'] ?? [],
                'prices' => $validated['prices'] ?? [],
                'images' => $imagePaths,
                'website_link' => $validated['website_link'] ?? null,
                'social_media_handles' => $socialMediaHandles,
                'booking_link' => $validated['booking_link'] ?? null,
                'status' => $validated['status'],
            ]);

            return redirect()->route('kids-fun-venues.show', $venue)
                ->with('success', 'Kids Fun Venue created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating kids fun venue: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the venue.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(KidsFunVenue $kidsFunVenue)
    {
        $kidsFunVenue->load(['business', 'creator']);
        return view('kids-fun-venues.show', compact('kidsFunVenue'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KidsFunVenue $kidsFunVenue)
    {
        $user = Auth::user();
        $business = $user->business;
        
        // Ensure the venue belongs to the user's business
        if ($kidsFunVenue->business_id !== $business->id) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('kids-fun-venues.edit', compact('kidsFunVenue', 'business'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KidsFunVenue $kidsFunVenue)
    {
        $user = Auth::user();
        $business = $user->business;
        
        // Ensure the venue belongs to the user's business
        if ($kidsFunVenue->business_id !== $business->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'activities' => 'nullable|array',
            'activities.*' => 'string|max:255',
            'prices' => 'nullable|array',
            'prices.*' => 'string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'website_link' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'booking_link' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published',
        ]);

        try {
            // Handle new images upload
            $existingImages = $kidsFunVenue->images ?? [];
            $newImagePaths = [];
            
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('kids-fun-venues', 'public');
                    $newImagePaths[] = $path;
                }
            }

            // Combine existing and new images (or replace if needed)
            $imagePaths = array_merge($existingImages, $newImagePaths);

            // Build social media handles array
            $socialMediaHandles = [];
            if ($request->filled('facebook')) {
                $socialMediaHandles['facebook'] = $request->facebook;
            }
            if ($request->filled('instagram')) {
                $socialMediaHandles['instagram'] = $request->instagram;
            }
            if ($request->filled('twitter')) {
                $socialMediaHandles['twitter'] = $request->twitter;
            }
            if ($request->filled('whatsapp')) {
                $socialMediaHandles['whatsapp'] = $request->whatsapp;
            }

            $kidsFunVenue->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'],
                'open_time' => $validated['open_time'],
                'close_time' => $validated['close_time'],
                'activities' => $validated['activities'] ?? [],
                'prices' => $validated['prices'] ?? [],
                'images' => $imagePaths,
                'website_link' => $validated['website_link'] ?? null,
                'social_media_handles' => $socialMediaHandles,
                'booking_link' => $validated['booking_link'] ?? null,
                'status' => $validated['status'],
            ]);

            return redirect()->route('kids-fun-venues.show', $kidsFunVenue)
                ->with('success', 'Kids Fun Venue updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating kids fun venue: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the venue.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KidsFunVenue $kidsFunVenue)
    {
        $user = Auth::user();
        $business = $user->business;
        
        // Ensure the venue belongs to the user's business
        if ($kidsFunVenue->business_id !== $business->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Delete images from storage
            if ($kidsFunVenue->images) {
                foreach ($kidsFunVenue->images as $imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            $kidsFunVenue->delete();

            return redirect()->route('kids-fun-venues.index')
                ->with('success', 'Kids Fun Venue deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting kids fun venue: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the venue.');
        }
    }
}
