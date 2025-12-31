<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicAdvertisementsController extends Controller
{
    /**
     * List all active advertisements (public, no authentication required)
     */
    public function index(Request $request)
    {
        try {
            Log::info('PublicAdvertisementsController::index - Starting request');
            Log::info('PublicAdvertisementsController::index - Request URL: ' . $request->fullUrl());
            Log::info('PublicAdvertisementsController::index - PHP Version: ' . PHP_VERSION);
            
            $query = Advertisement::query()
                ->where('status', 'published')
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderByDesc('created_at');

            // Filter by category
            if ($category = $request->query('category')) {
                $query->where('category', $category);
            }

            // Filter by media type
            if ($mediaType = $request->query('media_type')) {
                $query->where('media_type', $mediaType);
            }

            // Search
            if ($search = $request->query('search')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            Log::info('PublicAdvertisementsController::index - Executing query');
            // Get all advertisements (no pagination) - eager load business relationship
            $advertisements = $query->with('business')->get();
            Log::info('PublicAdvertisementsController::index - Found ' . $advertisements->count() . ' advertisements');
            
            // Log first advertisement details for debugging
            if ($advertisements->count() > 0) {
                $firstAd = $advertisements->first();
                Log::info('PublicAdvertisementsController::index - First ad details', [
                    'id' => $firstAd->id,
                    'title' => $firstAd->title,
                    'start_date' => $firstAd->start_date ? $firstAd->start_date->toDateTimeString() : 'NULL',
                    'end_date' => $firstAd->end_date ? $firstAd->end_date->toDateTimeString() : 'NULL',
                    'has_business' => $firstAd->business ? 'yes' : 'no',
                ]);
            }

            Log::info('PublicAdvertisementsController::index - Transforming advertisements');
            $transformedAds = collect();
            
            foreach ($advertisements as $ad) {
                try {
                    $transformed = $this->transformAdvertisement($ad);
                    $transformedAds->push($transformed);
                } catch (\Exception $e) {
                    Log::error('PublicAdvertisementsController::index - Error transforming ad ' . $ad->id . ': ' . $e->getMessage(), [
                        'ad_id' => $ad->id,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    // Skip this ad and continue
                }
            }

            Log::info('PublicAdvertisementsController::index - Getting categories');
            $categories = Advertisement::where('status', 'published')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();

            Log::info('PublicAdvertisementsController::index - Returning response with ' . $transformedAds->count() . ' ads');
            return response()->json([
                'success' => true,
                'message' => 'Advertisements retrieved successfully.',
                'data' => [
                    'advertisements' => $transformedAds,
                    'total' => $transformedAds->count(),
                    'categories' => $categories,
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicAdvertisementsController::index - Error: ' . $errorMessage, [
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
                'request_url' => $request->fullUrl(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving advertisements.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        } catch (\Throwable $e) {
            // Catch any other throwable (PHP 7+)
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicAdvertisementsController::index - Throwable Error: ' . $errorMessage, [
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving advertisements.',
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
     * Show a single advertisement (public)
     */
    public function show($id)
    {
        try {
            Log::info('PublicAdvertisementsController::show - Requesting advertisement ID: ' . $id);
            
            $advertisement = Advertisement::with('business')
                ->where('id', $id)
                ->where('status', 'published')
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if (!$advertisement) {
                Log::warning('PublicAdvertisementsController::show - Advertisement not found: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found or not active.',
                ], 404);
            }

            Log::info('PublicAdvertisementsController::show - Transforming advertisement');
            $transformedAd = $this->transformAdvertisement($advertisement, true);

            return response()->json([
                'success' => true,
                'message' => 'Advertisement retrieved successfully.',
                'data' => [
                    'advertisement' => $transformedAd,
                ],
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicAdvertisementsController::show - Error: ' . $errorMessage, [
                'id' => $id,
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the advertisement.',
                'error' => [
                    'message' => (string) $errorMessage,
                    'file' => (string) $errorFile,
                    'line' => (int) $errorLine,
                    'type' => get_class($e),
                ],
            ], 500);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            Log::error('PublicAdvertisementsController::show - Throwable Error: ' . $errorMessage, [
                'id' => $id,
                'file' => $errorFile,
                'line' => $errorLine,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the advertisement.',
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
     * Transform advertisement for API response
     */
    protected function transformAdvertisement(Advertisement $ad, bool $includeDetails = false): array
    {
        try {
            $data = [
                'id' => $ad->id,
                'uuid' => $ad->uuid ?? '',
                'title' => $ad->title ?? '',
                'description' => $ad->description ?? '',
                'media_type' => $ad->media_type ?? 'text',
                'media_url' => null,
                'category' => $ad->category ?? null,
                'start_date' => null,
                'end_date' => null,
                'is_recurring' => (bool) ($ad->is_recurring ?? false),
                'recurrence_pattern' => $ad->recurrence_pattern ?? null,
            ];

            // Handle media URL
            if ($ad->media_path) {
                if (str_starts_with($ad->media_path, 'http')) {
                    $data['media_url'] = $ad->media_path;
                } else {
                    try {
                        $data['media_url'] = asset('storage/' . $ad->media_path);
                    } catch (\Exception $e) {
                        Log::warning('PublicAdvertisementsController::transformAdvertisement - Error generating media URL: ' . $e->getMessage());
                        $data['media_url'] = null;
                    }
                }
            }

            // Handle dates safely
            if ($ad->start_date) {
                try {
                    $data['start_date'] = $ad->start_date->toIso8601String();
                } catch (\Exception $e) {
                    Log::warning('PublicAdvertisementsController::transformAdvertisement - Error formatting start_date: ' . $e->getMessage());
                    $data['start_date'] = $ad->start_date instanceof \DateTime ? $ad->start_date->format('Y-m-d\TH:i:s\Z') : null;
                }
            }

            if ($ad->end_date) {
                try {
                    $data['end_date'] = $ad->end_date->toIso8601String();
                } catch (\Exception $e) {
                    Log::warning('PublicAdvertisementsController::transformAdvertisement - Error formatting end_date: ' . $e->getMessage());
                    $data['end_date'] = $ad->end_date instanceof \DateTime ? $ad->end_date->format('Y-m-d\TH:i:s\Z') : null;
                }
            }

            if ($includeDetails) {
                $data['target_audience'] = $ad->target_audience ?: [];
                $data['budget'] = $ad->budget ? (float) $ad->budget : null;
                
                // Safely load business relationship
                try {
                    $business = $ad->business;
                    $data['business'] = $business ? [
                        'id' => $business->id,
                        'name' => $business->name ?? '',
                        'email' => $business->email ?? null,
                        'phone' => $business->phone ?? null,
                        'logo' => null,
                        'social_media_handles' => $business->social_media_handles ?: [],
                        'website_link' => $business->website_link ?? null,
                    ] : null;
                    
                    // Handle logo URL safely
                    if ($business && $business->logo) {
                        try {
                            $data['business']['logo'] = asset('storage/' . $business->logo);
                        } catch (\Exception $e) {
                            Log::warning('PublicAdvertisementsController::transformAdvertisement - Error generating logo URL: ' . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('PublicAdvertisementsController::transformAdvertisement - Could not load business: ' . $e->getMessage());
                    $data['business'] = null;
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('PublicAdvertisementsController::transformAdvertisement - Error: ' . $e->getMessage(), [
                'ad_id' => $ad->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('PublicAdvertisementsController::transformAdvertisement - Throwable Error: ' . $e->getMessage(), [
                'ad_id' => $ad->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }
}
