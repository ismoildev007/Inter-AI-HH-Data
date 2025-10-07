<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\VisitorsApiController;
use App\Http\Controllers\TrackVisitApiController;

// Visitors analytics API
//Route::get('v1/visitors', [VisitorsApiController::class, 'index']);
Route::middleware(['track.visits'])->prefix('v1')->group(function () {
Route::post('visits/track', [TrackVisitApiController::class, 'store']);
//Route::get('visits/track', [TrackVisitApiController::class, 'track']);
});
Route::get('visits/track', [TrackVisitApiController::class, 'track']);
