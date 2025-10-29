<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\VisitorsApiController;
use App\Http\Controllers\TrackVisitApiController;
use Illuminate\Support\Facades\Log;
use Spatie\Async\Pool;

Route::get('/test-pool', function () {
    $start = microtime(true);
    Log::info('🧵 Spatie Pool test started');

    $pool = Pool::create();

    $pool[] = async(function () {
        sleep(3); // og‘ir ish
        return 'Task 1 done after 3s';
    })->then(function ($output) {
        Log::info('✅ '.$output);
    })->catch(function (Throwable $e) {
        Log::error('❌ Task 1 error: '.$e->getMessage());
    });

    $pool[] = async(function () {
        sleep(3); // yana 3 sekundlik ish
        return 'Task 2 done after 3s';
    })->then(function ($output) {
        Log::info('✅ '.$output);
    })->catch(function (Throwable $e) {
        Log::error('❌ Task 2 error: '.$e->getMessage());
    });

    // parallel ishni kutish
    await($pool);

    $time = round(microtime(true) - $start, 2);
    Log::info("🏁 Pool finished in {$time}s");

    return response()->json([
        'message' => 'Parallel test finished',
        'total_time' => $time,
    ]);
});

Route::get('/test-sequential', function () {
    $start = microtime(true);
    Log::info('🧵 Sequential test started');

    // 1-task
    sleep(3);
    Log::info('✅ Task 1 done after 3s');

    // 2-task
    sleep(3);
    Log::info('✅ Task 2 done after 3s');

    $time = round(microtime(true) - $start, 2);
    Log::info("🏁 Sequential finished in {$time}s");

    return response()->json([
        'message' => 'Sequential test finished',
        'total_time' => $time,
    ]);
});

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
