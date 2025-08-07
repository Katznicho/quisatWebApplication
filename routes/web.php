<?php


use App\Http\Controllers\BusinessController;
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


// Route::get("makePayment",[PaymentController::class,"makePayment"])->name("makePayment");    

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Route for the getting the data feed
    // Route::get('/json-data-feed', [DataFeedController::class, 'getDataFeed'])->name('json_data_feed');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::impersonate();



    Route::resource("businesses", BusinessController::class);
    Route::resource("support", SupportController::class);
    Route::resource("transactions", TransactionController::class);
    Route::resource("users", UserController::class);
    Route::resource("roles", RoleController::class);
    Route::resource("features", FeatureController::class);
    Route::resource("currency", CurrencyController::class);
    Route::resource("business-categories", BusinessCategoryController::class);
    Route::resource("programs", ProgramController::class);
    Route::post('/programs/{program}/events', [ProgramController::class, 'storeEvent'])->name('programs.events.store');
    Route::post('/events/{event}/attendees', [ProgramController::class, 'storeAttendee'])->name('events.attendees.store');
    Route::post('/attendees/{attendee}/payments', [ProgramController::class, 'storePayment'])->name('attendees.payments.store');
    Route::get('/attendees/{attendee}/payments', [ProgramController::class, 'getAttendeePayments'])->name('attendees.payments.index');
    
    // Payment management routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::get('/payments/reports', [PaymentController::class, 'reports'])->name('payments.reports');





    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    // routes/web.php
    Route::get('/users/{user:uuid}', [UserController::class, 'show']);







    Route::get('/test-mail-view', function () {
        return view('mail.bot'); // 
    });
});
