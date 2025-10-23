<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('users::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('users::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('users::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    public function workedStatusUpdate(Request $request)
    {
        $user = Auth::user();

        $cacheKey = 'worked_status_count_' . $user->id . '_' . now()->format('Y-m-d');

        $requestCount = Cache::get($cacheKey, 0);

        if ($requestCount >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the daily limit (4 requests per day). Please try again tomorrow.'
            ], 429);
        }

        Cache::put($cacheKey, $requestCount + 1, now()->addDay());

        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,busy,offline'
        ]);

        $user->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'user_id' => $user->id,
                'status' => $user->status,
                'requests_today' => $requestCount + 1
            ]
        ]);
    }

}
