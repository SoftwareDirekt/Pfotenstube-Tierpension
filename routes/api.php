<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\SyncCustomersToHelloCash;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerReservationController;
use App\Http\Controllers\Api\CustomerDogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Sync customers to HelloCash (Hit via POSTMAN or similar)
Route::post('/hellocash/sync-customers', function () {
    SyncCustomersToHelloCash::dispatch(10);
    return response()->json(['message' => 'HelloCash customer sync job has been dispatched. Check logs for progress.']);
});

Route::prefix('customer/auth')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:8,1');
    Route::post('/verify-email-code', [CustomerAuthController::class, 'verifyEmailCode'])->middleware('throttle:10,1');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/resend-code', [CustomerAuthController::class, 'resendCode'])->middleware('throttle:5,1');
});

Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
    Route::get('/me', [CustomerAuthController::class, 'me']);
    Route::post('/auth/logout', [CustomerAuthController::class, 'logout']);

    Route::get('/reservations', [CustomerReservationController::class, 'index']);
    Route::post('/reservations', [CustomerReservationController::class, 'store'])->middleware('throttle:12,1');

    Route::get('/dogs', [CustomerDogController::class, 'index']);
    Route::post('/dogs', [CustomerDogController::class, 'store'])->middleware('throttle:10,1');
});
