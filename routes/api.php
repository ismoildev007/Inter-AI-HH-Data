<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\VisitorsApiController;
use App\Http\Controllers\TrackVisitApiController;

// Visitors analytics API
Route::post('visits/track', [TrackVisitApiController::class, 'store']);
//Route::get('v1/visitors', [VisitorsApiController::class, 'index']);
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('visits/track', [TrackVisitApiController::class, 'track']);
//Route::get('visits/track', [TrackVisitApiController::class, 'track']);
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found.',
    ], 404);
});
