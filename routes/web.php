<?php


use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\KidsEventController;
use App\Http\Controllers\KidsFunVenueController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

// Test route for chat functionality (no auth required)
Route::get('/chat-test', function () {
    return view('chat.test');
})->name('chat.test');

// Test route with real contacts (no auth required)
Route::get('/chat-demo', function () {
    $contacts = \App\Models\User::whereNotNull('business_id')->take(5)->get();
    return view('chat.demo', compact('contacts'));
})->name('chat.demo');

// Public KidsMart Products Page
Route::get('/kidsmart', [ProductController::class, 'publicIndex'])->name('products.public');

// Test route that mimics the main chat page (no auth required)
Route::get('/chat-test-main', function () {
    $user = \App\Models\User::first(); // Get first user for testing
    if (!$user) {
        return 'No users found in database';
    }
    
    // Get contacts for this user
    $contacts = \App\Models\User::where('business_id', $user->business_id)
        ->where('id', '!=', $user->id)
        ->with('role')
        ->get();
    
    // Get conversations (empty for testing)
    $conversations = collect();
    
    // Mock the authenticated user for the view
    auth()->login($user);
    
    return view('chat.index', compact('conversations', 'contacts'));
})->name('chat.test-main');

use Illuminate\Support\Facades\Route;






/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'login');

// Business Registration Routes (Public)
Route::get('/business/register', [BusinessRegistrationController::class, 'showRegistrationForm'])->name('business.register');
Route::post('/business/register', [BusinessRegistrationController::class, 'register']);
Route::get('/business/registration/success', [BusinessRegistrationController::class, 'registrationSuccess'])->name('business.registration.success');
Route::post('/business/resend-verification', [BusinessRegistrationController::class, 'resendVerification'])->name('business.resend.verification');

// Route::get("makePayment",[PaymentController::class,"makePayment"])->name("makePayment");    

Route::middleware(['auth:sanctum'])->group(function () {

    // Route for the getting the data feed
    // Route::get('/json-data-feed', [DataFeedController::class, 'getDataFeed'])->name('json_data_feed');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::impersonate();



    Route::resource("businesses", BusinessController::class);
    Route::patch('businesses/{business}/update-logo', [BusinessController::class, 'updateLogo'])->name('businesses.update-logo');
    Route::patch('businesses/{business}/update-social-media', [BusinessController::class, 'updateSocialMedia'])->name('businesses.update-social-media');
    Route::resource("support", SupportController::class);
    Route::resource("transactions", TransactionController::class);
    Route::resource("users", UserController::class);
    Route::resource("roles", RoleController::class);
    Route::resource("features", FeatureController::class);
    Route::resource("currency", CurrencyController::class);
    Route::resource("business-categories", BusinessCategoryController::class);
    
    // Advertisement routes
    Route::resource("advertisements", AdvertisementController::class);
    Route::get('advertisements/{advertisement}/analytics', [AdvertisementController::class, 'analytics'])->name('advertisements.analytics');
    Route::post('advertisements/{advertisement}/track', [AdvertisementController::class, 'track'])->name('advertisements.track');
    Route::get('advertisements/export/report', [AdvertisementController::class, 'export'])->name('advertisements.export');
    Route::post('advertisements/publish-selected', [AdvertisementController::class, 'publishSelected'])->name('advertisements.publish-selected');
    
    // Kids Events routes
    Route::resource("kids-events", KidsEventController::class);
    Route::post('kids-events/{kidsEvent}/toggle-featured', [KidsEventController::class, 'toggleFeatured'])->name('kids-events.toggle-featured');
    Route::post('kids-events/{kidsEvent}/update-status', [KidsEventController::class, 'updateStatus'])->name('kids-events.update-status');
    
    Route::resource("programs", ProgramController::class);
    
    // KidsMart Products routes
    Route::resource("products", ProductController::class);
    
    // Kids Fun Venues routes
    Route::resource("kids-fun-venues", KidsFunVenueController::class);
    
    // KidsMart Orders routes
    Route::resource("orders", OrderController::class)->only(['index', 'show']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

    // Admin Management Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminManagementController::class, 'index'])->name('dashboard');
        Route::get('/create-admin', [AdminManagementController::class, 'createAdmin'])->name('create-admin');
        Route::post('/create-admin', [AdminManagementController::class, 'storeAdmin'])->name('store-admin');
        Route::get('/create-staff', [AdminManagementController::class, 'createStaff'])->name('create-staff');
        Route::post('/create-staff', [AdminManagementController::class, 'storeStaff'])->name('store-staff');
        Route::get('/edit-user/{user}', [AdminManagementController::class, 'editUser'])->name('edit-user');
        Route::put('/update-user/{user}', [AdminManagementController::class, 'updateUser'])->name('update-user');
        Route::delete('/delete-user/{user}', [AdminManagementController::class, 'destroyUser'])->name('delete-user');
        Route::post('/reset-password/{user}', [AdminManagementController::class, 'resetPassword'])->name('reset-password');
        Route::post('/toggle-status/{user}', [AdminManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/users-by-business', [AdminManagementController::class, 'getUsersByBusiness'])->name('users-by-business');
        Route::get('/users-by-role', [AdminManagementController::class, 'getUsersByRole'])->name('users-by-role');
    });
    Route::post('/programs/{program}/events', [ProgramController::class, 'storeEvent'])->name('programs.events.store')->where('program', '[a-f0-9\-]+');
    Route::post('/events/{event}/attendees', [ProgramController::class, 'storeAttendee'])->name('events.attendees.store')->where('event', '[a-f0-9\-]+');
    Route::post('/attendees/{attendee}/payments', [ProgramController::class, 'storePayment'])->name('attendees.payments.store')->where('attendee', '[a-f0-9\-]+');
    Route::get('/attendees/{attendee}/payments', [ProgramController::class, 'getAttendeePayments'])->name('attendees.payments.index')->where('attendee', '[a-f0-9\-]+');
    
    // Payment management routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::get('/payments/reports', [PaymentController::class, 'reports'])->name('payments.reports');





    Route::put('/users/{user}', [UserController::class, 'update']);
    // routes/web.php
    Route::get('/users/{user:uuid}', [UserController::class, 'show']);

    // Calendar & Events Routes
    Route::resource('calendar-events', CalendarEventController::class);
    Route::get('/calendar', [CalendarEventController::class, 'calendar'])->name('calendar-events.calendar');
    Route::get('/calendar-events/api/events', [CalendarEventController::class, 'apiEvents'])->name('calendar-events.api');
    Route::get('/calendar-events/upcoming', [CalendarEventController::class, 'upcoming'])->name('calendar-events.upcoming');

    // Term Management Routes
    Route::resource('terms', TermController::class);
    Route::get('/terms/current', [TermController::class, 'current'])->name('terms.current');
    Route::post('/terms/{term}/activate', [TermController::class, 'activate'])->name('terms.activate');
    Route::post('/terms/{term}/complete', [TermController::class, 'complete'])->name('terms.complete');
    Route::get('/terms/{term}/statistics', [TermController::class, 'statistics'])->name('terms.statistics');

    // School Management Routes
    Route::prefix('school-management')->name('school-management.')->group(function () {
        Route::get('/students', function () {
            return view('school-management.students');
        })->name('students');
        
        Route::get('/attendance', function () {
            return view('school-management.attendance');
        })->name('attendance');
        
        Route::get('/calendar-events', function () {
            return view('school-management.calendar-events');
        })->name('calendar-events');
        
        Route::get('/classrooms', function () {
            return view('school-management.classrooms');
        })->name('classrooms');
        
        Route::get('/subjects', function () {
            return view('school-management.subjects');
        })->name('subjects');
        
        Route::get('/grades', function () {
            return view('school-management.grades');
        })->name('grades');
        
        Route::get('/exams', function () {
            return view('school-management.exams');
        })->name('exams');
        
        Route::get('/fees', function () {
            return view('school-management.fees');
        })->name('fees');
        
        Route::get('/timetable', function () {
            return view('school-management.timetable');
        })->name('timetable');
        
        Route::get('/parents', function () {
            return view('school-management.parents');
        })->name('parents');
        
        Route::get('/terms', function () {
            return view('school-management.terms');
        })->name('terms');
    });

    Route::get('/test-mail-view', function () {
        return view('mail.bot'); // 
    });

    // Chat & Communications Routes
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/conversations', [ChatController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/conversations', [ChatController::class, 'store'])->name('store');
        Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('send-message');
        Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages'])->name('messages');
        Route::post('/conversations/{conversation}/mark-read', [ChatController::class, 'markAsRead'])->name('mark-read');
        Route::get('/contacts', [ChatController::class, 'getContacts'])->name('contacts');
        Route::post('/broadcast', [ChatController::class, 'sendBroadcast'])->name('broadcast');
        Route::post('/meetings', [ChatController::class, 'createMeeting'])->name('create-meeting');
    });
});
