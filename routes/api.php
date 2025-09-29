<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\VisitorsApiController;
use App\Http\Controllers\TrackVisitApiController;

// Visitors analytics API
//Route::get('v1/visitors', [VisitorsApiController::class, 'index']);
Route::post('v1/visits/track', [TrackVisitApiController::class, 'store']);
Route::get('v1/visits/track', [TrackVisitApiController::class, 'track']);
