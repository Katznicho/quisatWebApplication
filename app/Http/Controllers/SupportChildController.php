<?php

namespace App\Http\Controllers;

use App\Models\SupportChild;
use App\Models\SupportChildImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupportChildController extends Controller
{
    public function index()
    {
        $business = Auth::user()->business;

        $children = SupportChild::where('business_id', $business->id ?? 0)
            ->with('images')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        return view('support-children.index', compact('children'));
    }

    public function create()
    {
        return view('support-children.create');
    }

    public function store(Request $request)
    {
        $business = Auth::user()->business;

        $validated = $request->validate([
            'child_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:0|max:25',
            'monthly_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'story' => 'nullable|string',
            'organisation_name' => 'nullable|string|max:255',
            'organisation_email' => 'nullable|email|max:255',
            'organisation_phone' => 'nullable|string|max:50',
            'organisation_website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'is_featured' => 'nullable|boolean',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $child = SupportChild::create([
            'business_id' => $business->id ?? null,
            'child_name' => $validated['child_name'],
            'age' => $validated['age'] ?? null,
            'monthly_fee' => $validated['monthly_fee'] ?? null,
            'currency' => $validated['currency'] ?? 'UGX',
            'story' => $validated['story'] ?? null,
            'organisation_name' => $validated['organisation_name'] ?? null,
            'organisation_email' => $validated['organisation_email'] ?? null,
            'organisation_phone' => $validated['organisation_phone'] ?? null,
            'organisation_website' => $validated['organisation_website'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'is_featured' => $request->boolean('is_featured'),
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('support-children', 'public');

                SupportChildImage::create([
                    'support_child_id' => $child->id,
                    'image_url' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('support-children.index')
            ->with('success', 'Support child created successfully!');
    }

    public function show(SupportChild $support_child)
    {
        $this->authorizeChild($support_child);

        $support_child->load('images', 'business');

        return view('support-children.show', ['child' => $support_child]);
    }

    public function edit(SupportChild $support_child)
    {
        $this->authorizeChild($support_child);

        $support_child->load('images');

        return view('support-children.edit', ['child' => $support_child]);
    }

    public function update(Request $request, SupportChild $support_child)
    {
        $this->authorizeChild($support_child);

        $validated = $request->validate([
            'child_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:0|max:25',
            'monthly_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'story' => 'nullable|string',
            'organisation_name' => 'nullable|string|max:255',
            'organisation_email' => 'nullable|email|max:255',
            'organisation_phone' => 'nullable|string|max:50',
            'organisation_website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'is_featured' => 'nullable|boolean',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $support_child->update([
            'child_name' => $validated['child_name'],
            'age' => $validated['age'] ?? null,
            'monthly_fee' => $validated['monthly_fee'] ?? null,
            'currency' => $validated['currency'] ?? $support_child->currency,
            'story' => $validated['story'] ?? null,
            'organisation_name' => $validated['organisation_name'] ?? null,
            'organisation_email' => $validated['organisation_email'] ?? null,
            'organisation_phone' => $validated['organisation_phone'] ?? null,
            'organisation_website' => $validated['organisation_website'] ?? null,
            'status' => $validated['status'] ?? $support_child->status,
            'is_featured' => $request->boolean('is_featured'),
        ]);

        if ($request->hasFile('photos')) {
            $existingCount = $support_child->images()->count();

            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('support-children', 'public');

                SupportChildImage::create([
                    'support_child_id' => $support_child->id,
                    'image_url' => $path,
                    'is_primary' => $existingCount === 0 && $index === 0,
                    'sort_order' => $existingCount + $index,
                ]);
            }
        }

        return redirect()->route('support-children.show', $support_child)
            ->with('success', 'Support child updated successfully!');
    }

    public function destroy(SupportChild $support_child)
    {
        $this->authorizeChild($support_child);

        foreach ($support_child->images as $image) {
            if ($image->image_url && Storage::disk('public')->exists($image->image_url)) {
                Storage::disk('public')->delete($image->image_url);
            }
            $image->delete();
        }

        $support_child->delete();

        return redirect()->route('support-children.index')
            ->with('success', 'Support child deleted successfully!');
    }

    protected function authorizeChild(SupportChild $child): void
    {
        $business = Auth::user()->business;

        if (!$business || $child->business_id !== $business->id) {
            abort(403, 'You are not allowed to manage this child.');
        }
    }
}

