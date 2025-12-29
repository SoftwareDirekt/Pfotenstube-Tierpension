<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\SyncCustomersToHelloCash;

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
