<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\ClassRoom;
use App\Models\KidsIncident;
use App\Models\KidsLesson;
use App\Models\KidsVolunteer;
use App\Models\MemoryWallItem;
use App\Models\ParentGuardian;
use App\Models\PickupCode;
use App\Models\PrayerRequest;
use App\Models\QuisatAlbum;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class KidsChurchController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $business = Auth::user()?->business;
            if (! $business || ! $business->isChurch()) {
                abort(403, 'Kids Church is not enabled for this business.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? 0;
        $today = Carbon::now(config('app.timezone', 'Africa/Nairobi'))->toDateString();

        $checkedInToday = Attendance::query()
            ->where('business_id', $businessId)
            ->whereDate('attendance_date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->count();

        $stats = [
            'children' => Student::where('business_id', $businessId)->count(),
            'groups' => ClassRoom::where('business_id', $businessId)->count(),
            'checked_in_today' => $checkedInToday,
            'pickup_codes' => Schema::hasTable('pickup_codes')
                ? PickupCode::where('business_id', $businessId)->whereDate('code_date', $today)->whereNull('used_at')->count()
                : 0,
            'prayer_requests' => Schema::hasTable('prayer_requests')
                ? PrayerRequest::where('business_id', $businessId)->whereIn('status', ['received', 'being_prayed_for'])->count()
                : 0,
            'albums' => Schema::hasTable('quisat_albums')
                ? QuisatAlbum::where('business_id', $businessId)->count()
                : 0,
            'parents' => ParentGuardian::query()
                ->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId)
                        ->orWhereHas('memberships', function ($membership) use ($businessId) {
                            $membership->where('business_id', $businessId)->where('status', 'active');
                        });
                })
                ->count(),
            'events' => CalendarEvent::where('business_id', $businessId)->where('start_date', '>=', $today)->count(),
            'memory_items' => Schema::hasTable('memory_wall_items')
                ? MemoryWallItem::where('business_id', $businessId)->current()->count()
                : 0,
            'birthdays_today' => Student::where('business_id', $businessId)
                ->whereNotNull('date_of_birth')
                ->whereMonth('date_of_birth', Carbon::parse($today)->month)
                ->whereDay('date_of_birth', Carbon::parse($today)->day)
                ->count(),
            'medical_alerts' => Student::where('business_id', $businessId)
                ->where(function ($query) {
                    $query->whereNotNull('allergies')->where('allergies', '!=', '')
                        ->orWhere(function ($medical) {
                            $medical->whereNotNull('medical_notes')->where('medical_notes', '!=', '');
                        });
                })
                ->count(),
            'lessons' => Schema::hasTable('kids_lessons')
                ? KidsLesson::where('business_id', $businessId)->published()->count()
                : 0,
            'incidents' => Schema::hasTable('kids_incidents')
                ? KidsIncident::where('business_id', $businessId)->whereDate('created_at', $today)->count()
                : 0,
            'volunteers' => Schema::hasTable('kids_volunteers')
                ? KidsVolunteer::where('business_id', $businessId)->where('status', 'active')->count()
                : 0,
        ];

        $birthdayChildren = Student::query()
            ->where('business_id', $businessId)
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', Carbon::parse($today)->month)
            ->whereDay('date_of_birth', Carbon::parse($today)->day)
            ->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name']);

        $medicalAlertChildren = Student::query()
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereNotNull('allergies')->where('allergies', '!=', '')
                    ->orWhere(function ($medical) {
                        $medical->whereNotNull('medical_notes')->where('medical_notes', '!=', '');
                    });
            })
            ->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name', 'allergies']);

        $activeLesson = Schema::hasTable('kids_lessons')
            ? KidsLesson::where('business_id', $businessId)->published()->orderByDesc('lesson_date')->orderByDesc('id')->first()
            : null;

        return view('kids-church.index', compact('stats', 'birthdayChildren', 'medicalAlertChildren', 'activeLesson'));
    }
}
