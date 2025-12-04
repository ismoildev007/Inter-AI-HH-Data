<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Services\RejectionService;

class UsersController extends Controller
{
    private const RESPONSE_STATUSES = [
        'interview',
        'interview_scheduled',
        'invitation',
        'offer',
        'hired',
        'invited',
        'assessments',
        'assessment',
        'test',
    ];

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
            'status' => 'required|string|in:working,not_working',
        ]);

        $updates = [
            'status' => $validated['status'],
        ];

        if ($user->admin_check_status && $validated['status'] === 'working') {
            $updates['admin_check_status'] = false;
        }

        $user->forceFill($updates)->save();

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

    public function notificationCounts(Request $request)
    {
        $user = $request->user();

        [$responseTotal, $applicationTotal] = $this->notificationTotals($user);
        $rejectionTotal = $this->rejectionTotal($user);

        $responseUnread = max($responseTotal - (int) $user->responce_notification, 0);
        $applicationUnread = max($applicationTotal - (int) $user->application_notification, 0);
        $rejectionUnread = max($rejectionTotal - (int) $user->rejection_notification, 0);

        return response()->json([
            'responce_notification' => $responseUnread,
            'application_notification' => $applicationUnread,
            'rejection_notification' => $rejectionUnread,
        ]);
    }

    public function markNotificationsAsRead(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'nullable|string|in:responce,response,application,rejection,all',
        ]);

        $type = $validated['type'] ?? 'all';

        [$responseTotal, $applicationTotal] = $this->notificationTotals($user);
        $rejectionTotal = $this->rejectionTotal($user);

        $updates = [];

        if (in_array($type, ['responce', 'response', 'all'], true)) {
            $updates['responce_notification'] = $responseTotal;
        }

        if (in_array($type, ['application', 'all'], true)) {
            $updates['application_notification'] = $applicationTotal;
        }

        if (in_array($type, ['rejection', 'all'], true)) {
            $updates['rejection_notification'] = $rejectionTotal;
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return response()->json([
            'message' => 'Notifications marked as read.',
            'responce_notification' => max($responseTotal - (int) $user->responce_notification, 0),
            'application_notification' => max($applicationTotal - (int) $user->application_notification, 0),
            'rejection_notification' => max($rejectionTotal - (int) $user->rejection_notification, 0),
        ]);
    }

    private function notificationTotals(User $user): array
    {
        $responseTotal = Application::query()
            ->where('user_id', $user->id)
            ->whereIn('hh_status', self::RESPONSE_STATUSES)
            ->count();

        $applicationTotal = Application::query()
            ->where('user_id', $user->id)
            ->count();

        return [$responseTotal, $applicationTotal];
    }

    /**
     * Kelajakda otkazlar bo'yicha notification count uchun yordamchi.
     *
     * Hozircha bu metod HTTP API javoblarida ishlatilmaydi, faqat
     * backend logikasi tayyor bo'lishi uchun mavjud.
     */
    private function rejectionTotal(User $user): int
    {
        /** @var RejectionService $service */
        $service = app(RejectionService::class);

        return $service->countForUser($user);
    }
}
