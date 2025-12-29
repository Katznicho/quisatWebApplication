<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of advertisements
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;
        
        $query = Advertisement::where('business_id', $business->id)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $advertisements = $query->paginate(10);

        return view('advertisements.index', compact('advertisements'));
    }

    /**
     * Show the form for creating a new advertisement
     */
    public function create()
    {
        $user = Auth::user();
        $business = $user->business;
        
        return view('advertisements.create', compact('business'));
    }

    /**
     * Store a newly created advertisement
     */
    public function store(Request $request)
    {
        Log::info('=== ADVERTISEMENT CREATION STARTED ===');
        Log::info('User ID: ' . Auth::id());
        Log::info('Request data: ' . json_encode($request->all()));
        
        $user = Auth::user();
        $business = $user->business;
        
        Log::info('User business ID: ' . ($business ? $business->id : 'NULL'));
        Log::info('User business: ' . ($business ? $business->name : 'NULL'));

        try {
            Log::info('Starting validation...');
            
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'media_type' => 'required|in:image,video,text',
                'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov|max:10240',
                'target_audience' => 'required|array|min:1',
                'target_audience.*' => 'in:all_users,staff,students,parents',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'is_recurring' => 'boolean',
                'recurrence_pattern' => 'nullable|in:daily,weekly,monthly',
                'budget' => 'nullable|numeric|min:0',
                'category' => 'nullable|string|max:255'
            ]);
            
            Log::info('Validation passed successfully');
            Log::info('Validated data: ' . json_encode($validated));

            // Handle media upload
            $mediaPath = null;
            if ($request->hasFile('media')) {
                Log::info('Media file detected, uploading...');
                $mediaPath = $request->file('media')->store('advertisements', 'public');
                Log::info('Media uploaded to: ' . $mediaPath);
            } else {
                Log::info('No media file provided');
            }

            Log::info('Creating advertisement record...');
            
            // Convert dates to Carbon instances for proper comparison
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            $now = now();
            
            // Determine status based on dates
            // If start_date is in the future, it's scheduled
            // If start_date is today or in the past and end_date is in the future, it's active
            $status = 'draft';
            if ($startDate->isFuture()) {
                $status = 'scheduled';
            } elseif ($startDate->lte($now) && $endDate->gte($now)) {
                $status = 'active';
            } elseif ($endDate->isPast()) {
                $status = 'expired';
            }
            
            $advertisementData = [
                'business_id' => $business->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'media_type' => $validated['media_type'],
                'media_path' => $mediaPath,
                'target_audience' => $validated['target_audience'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurrence_pattern' => $validated['recurrence_pattern'] ?? null,
                'budget' => $validated['budget'],
                'category' => $validated['category'],
                'created_by' => $user->id,
                'status' => $status
            ];
            
            Log::info('Advertisement data to create: ' . json_encode($advertisementData));

            $advertisement = Advertisement::create($advertisementData);
            
            Log::info('Advertisement created successfully with ID: ' . $advertisement->id);
            Log::info('=== ADVERTISEMENT CREATION COMPLETED ===');

            return redirect()->route('advertisements.index')
                ->with('success', 'Advertisement created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            Log::error('Validation exception: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please check the form for errors.');
                
        } catch (\Exception $e) {
            Log::error('=== ADVERTISEMENT CREATION FAILED ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Error trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            Log::error('User ID: ' . Auth::id());
            Log::error('Business ID: ' . ($business ? $business->id : 'NULL'));
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the advertisement. Check logs for details.');
        }
    }

    /**
     * Display the specified advertisement
     */
    public function show(Advertisement $advertisement)
    {
        $this->authorize('view', $advertisement);
        
        $advertisement->load('creator', 'analytics');
        
        return view('advertisements.show', compact('advertisement'));
    }

    /**
     * Show the form for editing the specified advertisement
     */
    public function edit(Advertisement $advertisement)
    {
        $this->authorize('update', $advertisement);
        
        return view('advertisements.edit', compact('advertisement'));
    }

    /**
     * Update the specified advertisement
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        $this->authorize('update', $advertisement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:image,video,text',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov|max:10240',
            'target_audience' => 'required|array|min:1',
            'target_audience.*' => 'in:all_users,staff,students,parents',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|in:daily,weekly,monthly',
            'budget' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:draft,scheduled,active,paused,expired'
        ]);

        try {
            // Handle media upload
            if ($request->hasFile('media')) {
                // Delete old media if exists
                if ($advertisement->media_path) {
                    Storage::disk('public')->delete($advertisement->media_path);
                }
                $validated['media_path'] = $request->file('media')->store('advertisements', 'public');
            }

            $advertisement->update($validated);

            return redirect()->route('advertisements.index')
                ->with('success', 'Advertisement updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating advertisement: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the advertisement.');
        }
    }

    /**
     * Remove the specified advertisement
     */
    public function destroy(Advertisement $advertisement)
    {
        $this->authorize('delete', $advertisement);

        try {
            // Delete media file if exists
            if ($advertisement->media_path) {
                Storage::disk('public')->delete($advertisement->media_path);
            }

            $advertisement->delete();

            return redirect()->route('advertisements.index')
                ->with('success', 'Advertisement deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting advertisement: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the advertisement.');
        }
    }

    /**
     * Get analytics for a specific advertisement
     */
    public function analytics(Advertisement $advertisement)
    {
        $this->authorize('view', $advertisement);

        $analytics = AdvertisementAnalytics::where('advertisement_id', $advertisement->id)
            ->orderBy('date', 'desc')
            ->get();

        $totalImpressions = $analytics->sum('impressions');
        $totalClicks = $analytics->sum('clicks');
        $totalConversions = $analytics->sum('conversions');
        $totalSpend = $analytics->sum('spend');

        $clickThroughRate = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        $conversionRate = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;

        return response()->json([
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'conversions' => $totalConversions,
            'spend' => $totalSpend,
            'click_through_rate' => $clickThroughRate,
            'conversion_rate' => $conversionRate,
            'daily_data' => $analytics
        ]);
    }

    /**
     * Track advertisement interaction (impression, click, conversion)
     */
    public function track(Request $request, Advertisement $advertisement)
    {
        $interactionType = $request->input('type', 'view'); // view, click, conversion
        $userId = Auth::id();

        // Create or update analytics record for today
        $analytics = AdvertisementAnalytics::firstOrCreate(
            [
                'advertisement_id' => $advertisement->id,
                'date' => now()->toDateString()
            ],
            [
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'spend' => 0
            ]
        );

        // Update the appropriate counter
        switch ($interactionType) {
            case 'view':
                $analytics->increment('impressions');
                break;
            case 'click':
                $analytics->increment('clicks');
                break;
            case 'conversion':
                $analytics->increment('conversions');
                break;
        }

        // Log individual interaction if user is authenticated
        if ($userId) {
            AdvertisementAnalytics::create([
                'advertisement_id' => $advertisement->id,
                'date' => now()->toDateString(),
                'user_id' => $userId,
                'interaction_type' => $interactionType,
                'impressions' => $interactionType === 'view' ? 1 : 0,
                'clicks' => $interactionType === 'click' ? 1 : 0,
                'conversions' => $interactionType === 'conversion' ? 1 : 0,
                'spend' => 0
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Export advertisement report
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;

        $query = Advertisement::where('business_id', $business->id)
            ->with('creator', 'analytics');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $advertisements = $query->get();

        // Generate CSV content
        $csvData = "Title,Status,Start Date,End Date,Impressions,Clicks,Conversions,CTR,Spend\n";
        
        foreach ($advertisements as $ad) {
            $impressions = $ad->getTotalImpressions();
            $clicks = $ad->getTotalClicks();
            $conversions = $ad->getTotalConversions();
            $ctr = $ad->getClickThroughRate();
            
            $csvData .= sprintf(
                "%s,%s,%s,%s,%d,%d,%d,%.2f%%,%.2f\n",
                $ad->title,
                $ad->status,
                $ad->start_date->format('Y-m-d'),
                $ad->end_date->format('Y-m-d'),
                $impressions,
                $clicks,
                $conversions,
                $ctr,
                $ad->budget ?? 0
            );
        }

        $filename = 'advertisement_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Publish selected advertisements
     */
    public function publishSelected(Request $request)
    {
        $request->validate([
            'advertisement_ids' => 'required|array',
            'advertisement_ids.*' => 'exists:advertisements,id'
        ]);

        $user = Auth::user();
        $business = $user->business;

        $advertisements = Advertisement::where('business_id', $business->id)
            ->whereIn('id', $request->advertisement_ids)
            ->get();

        foreach ($advertisements as $advertisement) {
            $this->authorize('update', $advertisement);
            
            if ($advertisement->status === 'draft' || $advertisement->status === 'scheduled') {
                $advertisement->update([
                    'status' => $advertisement->start_date > now() ? 'scheduled' : 'active'
                ]);
            }
        }

        return redirect()->back()
            ->with('success', 'Selected advertisements have been published!');
    }
}
