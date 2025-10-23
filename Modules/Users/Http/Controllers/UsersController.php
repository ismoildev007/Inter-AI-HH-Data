<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('users::index');
    }

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

    public function destroyIfNoResumes(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->resumes()->exists()) {
            return response()->json([
                'message' => 'Cannot delete user: user has one or more resumes.'
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $user->preferences()->delete();
            $user->preference()->delete();
            $user->locations()->delete();
            $user->jobTypes()->delete();
            $user->credit()->delete();
            $user->profileViews()->delete();
            $user->settings()->delete();

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            if (method_exists($user, 'tokens') === false && \Schema::hasTable('oauth_access_tokens')) {
                // Agar Passport ishlatilsa va modeli mos bo'lsa, alohida: (ixtiyoriy)
                // \DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            }

            $user->delete();
        });

        return response()->json(['message' => 'User deleted (no resumes found).'], 200);
    }

}
