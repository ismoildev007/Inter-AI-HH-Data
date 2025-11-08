<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $planId = (int) $request->query('plan', 0);

        $query = Subscription::query()
            ->with(['user:id,first_name,last_name,email', 'plan:id,name'])
            ->latest('starts_at');

        if ($search !== '') {
            $like = '%'.mb_strtolower($search, 'UTF-8').'%';
            $query->where(function ($inner) use ($like, $search) {
                $inner->whereRaw('LOWER(status) LIKE ?', [$like])
                    ->orWhereHas('plan', function ($q) use ($like) {
                        $q->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('user', function ($q) use ($like, $search) {
                        $q->whereRaw('LOWER(first_name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

                        if (ctype_digit($search)) {
                            $q->orWhere('id', (int) $search);
                        }
                    });

                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search);
                }
            });
        }

        if (in_array($status, ['active', 'expired', 'pending', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        if ($planId > 0) {
            $query->where('plan_id', $planId);
        }

        $subscriptions = $query->paginate(100)->withQueryString();

        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'pending' => Subscription::where('status', 'pending')->count(),
        ];

        $planOptions = Plan::orderBy('name')->pluck('name', 'id');

        return view('admin::Subscriptions.index', [
            'subscriptions' => $subscriptions,
            'search' => $search,
            'status' => $status,
            'planId' => $planId,
            'stats' => $stats,
            'planOptions' => $planOptions,
        ]);
    }

    /**
     * Display a specific subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'transactions' => function ($query) {
            $query->latest();
        }]);

        // Parse dates without relying on model casts
        $startsAt = !empty($subscription->starts_at) ? Carbon::parse($subscription->starts_at) : null;
        $endsAt = !empty($subscription->ends_at) ? Carbon::parse($subscription->ends_at) : null;

        $timeline = collect([
            'Starts' => $startsAt?->format('M d, Y'),
            'Ends' => $endsAt?->format('M d, Y'),
            'Renewal in' => $endsAt
                ? Carbon::now()->diffForHumans($endsAt, ['parts' => 2, 'short' => true, 'syntax' => Carbon::DIFF_ABSOLUTE])
                : '—',
        ]);

        $autoResponseUtilization = null;
        if ($subscription->plan && $subscription->plan->auto_response_limit) {
            $autoResponseUtilization = max(0, ($subscription->plan->auto_response_limit - (int) $subscription->remaining_auto_responses) / max(1, $subscription->plan->auto_response_limit));
        }

        return view('admin::Subscriptions.show', [
            'subscription' => $subscription,
            'timeline' => $timeline,
            'autoResponseUtilization' => $autoResponseUtilization,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
        ]);
    }
}
