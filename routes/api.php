<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\VisitorsApiController;
use App\Http\Controllers\TrackVisitApiController;
use Illuminate\Support\Facades\Log;
use Spatie\Async\Pool;
use Illuminate\Support\Facades\Http;
use App\Models\Vacancy;
use Illuminate\Support\Str;

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


// stress test

Route::get('/stress/cpu', function () {
    $start = microtime(true);
    $loops = request('loops', 100);  

    for ($i = 0; $i < $loops; $i++) {
        $x = sqrt($i * rand(1, 99));
    }

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'CPU',
        'loops' => $loops,
        'time' => $time . 's'
    ];
});



Route::get('/stress/ram', function () {
    $start = microtime(true);

    $memory = [];
    for ($i = 0; $i < 500000; $i++) {
        $memory[] = str_repeat("A", 1024 * 50); // 50KB × 500k = 25GB (swapga tushadi)
    }

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'RAM',
        'allocated_MB' => round(memory_get_peak_usage(true) / 1024 / 1024),
        'time' => $time . 's'
    ];
});

Route::get('/stress/disk', function () {
    $start = microtime(true);

    $path = storage_path('app/stress_test.dat');
    $data = str_repeat(random_bytes(1024 * 1024), 50); // 50 MB

    file_put_contents($path, $data);
    $content = file_get_contents($path);

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'Disk IO',
        'file_size_MB' => strlen($data) / 1024 / 1024,
        'time' => $time . 's'
    ];
});



Route::get('/stress/http', function () {
    $start = microtime(true);

    $response = Http::timeout(10)->get("https://httpbin.org/delay/3");

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'Network Latency',
        'status' => $response->status(),
        'time' => $time . 's'
    ];
});


Route::get('/stress/parallel', function () {
    $start = microtime(true);
    $pool = Pool::create();

    for ($i = 1; $i <= 20; $i++) {
        $pool[] = async(function () use ($i) {
            sleep(2);
            return "Task {$i}";
        });
    }

    await($pool);

    $time = round(microtime(true) - $start, 2);
    return [
        'type' => 'Parallel (20 tasks)',
        'time' => $time . 's'
    ];
});


Route::get('/stress/db-insert', function () {
    $start = microtime(true);

    $records = 10000;
    $data = [];

    for ($i = 0; i < $records; $i++) {
        $data[] = [
            'title' => "Test Vacancy $i",
            'description' => Str::random(500),
            'source' => 'stress_test',
            'status' => Vacancy::STATUS_PUBLISH,
            'salary_from' => rand(100, 1000),
            'salary_to' => rand(1000, 5000),
            'raw_data' => Str::random(5000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    Vacancy::insert($data);

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'DB Insert',
        'records' => $records,
        'time' => $time . 's'
    ];
});


Route::get('/stress/db-read', function () {
    $start = microtime(true);

    $vacancies = Vacancy::with(['employer', 'area', 'schedule'])
        ->limit(100000)
        ->get();

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'DB Read + Eager Load',
        'fetched' => $vacancies->count(),
        'time' => $time . 's'
    ];
});

Route::get('/stress/db-update', function () {
    $start = microtime(true);

    $count = Vacancy::limit(100000)->update([
        'views_count' => \DB::raw('views_count + 1'),
        'updated_at' => now()
    ]);

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'DB Update',
        'updated_rows' => $count,
        'time' => $time . 's'
    ];
});

Route::get('/stress/db-search', function () {
    $start = microtime(true);

    $result = Vacancy::where('description', 'LIKE', '%developer%')
        ->limit(20000)
        ->get();

    $time = round(microtime(true) - $start, 2);

    return [
        'type' => 'DB Full-text Search',
        'results' => $result->count(),
        'time' => $time . 's'
    ];
});

