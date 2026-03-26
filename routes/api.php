<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\SyncCustomersToHelloCash;
use App\Http\Controllers\Api\SyncController;

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

// Sync from Pfotenstube Homepage
Route::prefix('v1/external-sync')->middleware('verify.homepage')->group(function () {
    Route::post('/reservation', [SyncController::class, 'syncReservation']);
    Route::post('/reservation/cancel', [SyncController::class, 'cancelReservation']);
    Route::post('/dog', [SyncController::class, 'syncDog']);
});
