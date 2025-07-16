<?php


use App\Http\Controllers\BranchController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TitleController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServicePointController;

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
    Route::resource("branches", BranchController::class);
    Route::resource("support", SupportController::class);
    Route::resource("transactions", TransactionController::class);
    Route::resource("users", UserController::class);
    Route::resource("roles", RoleController::class);
    Route::resource("departments", DepartmentController::class);
    Route::resource("titles", TitleController::class);
    Route::resource("qualifications", QualificationController::class);
    Route::resource("rooms", RoomController::class);
    Route::resource("service-points", ServicePointController::class);

    
    Route::post('/select-room', [RoomController::class, 'selectRoom'])->name('room.select');





    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    // routes/web.php
    Route::get('/users/{user:uuid}', [UserController::class, 'show']);







    Route::get('/test-mail-view', function () {
        return view('mail.bot'); // 
    });
});
