<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\ParentUniversalCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ParentUniversalAccountController extends Controller
{
    public function __construct(
        protected ParentUniversalCodeService $codes
    ) {}

    /**
     * GET /api/v1/parent/me
     */
    public function me(Request $request)
    {
        $parent = $this->requireParent($request);
        if (! $parent instanceof ParentGuardian) {
            return $parent;
        }

        $this->codes->ensureCode($parent);
        $parent->load(['students', 'memberships.business']);

        return response()->json([
            'success' => true,
            'data' => $this->parentPayload($parent),
        ]);
    }

    /**
     * GET /api/v1/parent/universal-code
     */
    public function universalCode(Request $request)
    {
        $parent = $this->requireParent($request);
        if (! $parent instanceof ParentGuardian) {
            return $parent;
        }

        $code = $this->codes->ensureCode($parent);

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $code,
                'link' => $this->codes->universalLink($code),
                'account_type' => $parent->account_type,
                'businesses_count' => $parent->memberships()->where('status', 'active')->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/parent/universal-code/regenerate
     */
    public function regenerate(Request $request)
    {
        $parent = $this->requireParent($request);
        if (! $parent instanceof ParentGuardian) {
            return $parent;
        }

        $code = $this->codes->regenerate($parent);

        return response()->json([
            'success' => true,
            'message' => 'Universal code regenerated',
            'data' => [
                'code' => $code,
                'link' => $this->codes->universalLink($code),
                'account_type' => $parent->account_type,
                'businesses_count' => $parent->memberships()->where('status', 'active')->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/parent/businesses
     */
    public function businesses(Request $request)
    {
        $parent = $this->requireParent($request);
        if (! $parent instanceof ParentGuardian) {
            return $parent;
        }

        $parent->load(['memberships.business']);

        return response()->json([
            'success' => true,
            'data' => [
                'businesses' => $this->mapBusinesses($parent),
            ],
        ]);
    }

    /**
     * POST /api/v1/parent/join-business
     * Phase 1: clinic self-join only.
     */
    public function joinBusiness(Request $request)
    {
        $parent = $this->requireParent($request);
        if (! $parent instanceof ParentGuardian) {
            return $parent;
        }

        $validator = Validator::make($request->all(), [
            'business_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::query()
            ->where(function ($q) use ($request) {
                $q->where('id', $request->business_id)
                    ->orWhere('uuid', $request->business_id);
            })
            ->first();

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        if (! $this->codes->isClinicBusiness($business)) {
            return response()->json([
                'success' => false,
                'code' => 'BUSINESS_JOIN_FORBIDDEN',
                'message' => 'Self-join is only allowed for clinics. Share your Quisat code with the school or clinic staff.',
            ], 403);
        }

        try {
            $membership = $this->codes->attachToBusiness(
                $parent->fresh(),
                (int) $business->id,
                'self_join',
                $parent->relationship
            );

            $parent = $parent->fresh(['memberships.business', 'students']);

            return response()->json([
                'success' => true,
                'message' => 'Joined business successfully',
                'data' => [
                    'membership' => [
                        'id' => $membership->id,
                        'business_id' => $membership->business_id,
                        'joined_via' => $membership->joined_via,
                        'status' => $membership->status,
                        'joined_at' => optional($membership->joined_at)->toIso8601String(),
                    ],
                    'parent' => $this->parentPayload($parent),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Parent join business error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to join business',
            ], 500);
        }
    }

    /**
     * POST /api/v1/businesses/{business}/parents/accept-universal-code
     */
    public function acceptUniversalCode(Request $request, Business $business)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only business staff can accept universal codes.',
            ], 403);
        }

        $userBusinessId = (int) ($user->business_id ?? 0);
        if ($userBusinessId !== 1 && $userBusinessId !== (int) $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized for this business.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'universal_code' => 'required|string|max:32',
            'relationship' => 'nullable|in:father,mother,guardian,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parent = $this->codes->findByCode($request->universal_code);

        if (! $parent) {
            return response()->json([
                'success' => false,
                'code' => 'CODE_NOT_FOUND',
                'message' => 'Universal code not found',
            ], 404);
        }

        try {
            $membership = $this->codes->attachToBusiness(
                $parent,
                (int) $business->id,
                'universal_code',
                $request->input('relationship')
            );

            $parent = $parent->fresh(['memberships.business']);

            return response()->json([
                'success' => true,
                'message' => 'Parent linked to business successfully',
                'data' => [
                    'parent' => [
                        'id' => $parent->id,
                        'first_name' => $parent->first_name,
                        'last_name' => $parent->last_name,
                        'full_name' => $parent->full_name,
                        'email' => $parent->email,
                        'phone' => $parent->phone,
                        'account_type' => $parent->account_type,
                        'universal_code' => $parent->universal_code,
                        'business_id' => $parent->business_id,
                    ],
                    'membership' => [
                        'id' => $membership->id,
                        'business_id' => $membership->business_id,
                        'joined_via' => $membership->joined_via,
                        'status' => $membership->status,
                        'joined_at' => optional($membership->joined_at)->toIso8601String(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Accept universal code error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to link parent',
            ], 500);
        }
    }

    protected function requireParent(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents/guardians can access this resource.',
            ], 403);
        }

        return $user;
    }

    protected function parentPayload(ParentGuardian $parent): array
    {
        $primaryBusiness = $this->resolvePrimaryBusiness($parent);

        return [
            'id' => $parent->id,
            'first_name' => $parent->first_name,
            'last_name' => $parent->last_name,
            'full_name' => $parent->full_name,
            'email' => $parent->email,
            'phone' => $parent->phone,
            'relationship' => $parent->relationship,
            'status' => $parent->status,
            'account_type' => $parent->account_type ?? 'guest',
            'universal_code' => $parent->universal_code,
            'universal_link' => $this->codes->universalLink($parent->universal_code),
            'business_id' => $primaryBusiness?->id ?? $parent->business_id,
            'photo_url' => $this->resolvePhotoUrl($parent->photo),
            'business' => $primaryBusiness ? $this->mapBusiness($primaryBusiness) : null,
            'businesses' => $this->mapBusinesses($parent),
            'students' => ($parent->relationLoaded('students') ? $parent->students : $parent->students()->get())
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'full_name' => $student->full_name,
                        'student_id' => $student->student_id,
                        'class' => $student->class,
                        'status' => $student->status,
                        'photo_url' => $this->resolvePhotoUrl($student->photo),
                    ];
                })->values(),
            'user_type' => 'parent_guardian',
        ];
    }

    protected function resolvePrimaryBusiness(ParentGuardian $parent): ?Business
    {
        if ($parent->relationLoaded('memberships')) {
            $active = $parent->memberships->firstWhere('status', 'active');
            if ($active?->business) {
                return $active->business;
            }
        } else {
            $membership = $parent->memberships()
                ->where('status', 'active')
                ->with('business')
                ->first();
            if ($membership?->business) {
                return $membership->business;
            }
        }

        return $parent->business;
    }

    protected function mapBusinesses(ParentGuardian $parent): array
    {
        $memberships = $parent->relationLoaded('memberships')
            ? $parent->memberships
            : $parent->memberships()->with('business')->get();

        return $memberships
            ->filter(fn ($m) => $m->status === 'active' && $m->business)
            ->map(function ($membership) {
                $business = $membership->business;

                return array_merge($this->mapBusiness($business), [
                    'joined_via' => $membership->joined_via,
                    'joined_at' => optional($membership->joined_at)->toIso8601String(),
                    'membership_status' => $membership->status,
                ]);
            })
            ->values()
            ->all();
    }

    protected function mapBusiness(Business $business): array
    {
        return [
            'id' => $business->id,
            'uuid' => $business->uuid,
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            'address' => $business->address,
            'city' => $business->city,
            'country' => $business->country,
            'logo' => $business->logo,
            'type' => $business->type,
            'mode' => $business->mode,
            'enabled_features' => $business->enabled_feature_ids,
        ];
    }

    protected function resolvePhotoUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
