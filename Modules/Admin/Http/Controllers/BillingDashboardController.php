<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingDashboardController extends Controller
{
    public function index()
    {
        // Treat both 'success' and 'active' as paid for some analytics,
        // but use only 'active' where strictly required (cards and plan totals)
        $paidTransactions = Transaction::query()->whereIn('payment_status', ['success', 'active']);
        $activeTransactions = Transaction::query()->where('payment_status', 'active');

        $totalPlans = Plan::count();

        $planSubscriptionAggregates = Subscription::query()
            ->select([
                'plan_id',
                DB::raw('COUNT(*) as subscriptions_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users_count'),
            ])
            ->groupBy('plan_id')
            ->get()
            ->keyBy('plan_id');

        // Revenue per plan: include paid transactions (active + success)
        $planRevenueAggregates = (clone $paidTransactions)
            ->join('subscriptions', 'transactions.subscription_id', '=', 'subscriptions.id')
            ->whereNotNull('subscriptions.plan_id')
            ->select([
                'subscriptions.plan_id as plan_id',
                DB::raw('SUM(transactions.amount) as total_amount'),
            ])
            ->groupBy('subscriptions.plan_id')
            ->get()
            ->keyBy('plan_id');

        $planUserSpendAggregates = (clone $paidTransactions)
            ->join('subscriptions', 'transactions.subscription_id', '=', 'subscriptions.id')
            ->whereNotNull('subscriptions.plan_id')
            ->select([
                'subscriptions.plan_id as plan_id',
                'transactions.user_id',
                DB::raw('SUM(transactions.amount) as total_amount'),
                DB::raw('COUNT(*) as payments_count'),
            ])
            ->groupBy('subscriptions.plan_id', 'transactions.user_id')
            ->get();

        // Only ACTIVE purchases per plan
        $planPurchaseAggregates = (clone $activeTransactions)
            ->join('subscriptions', 'transactions.subscription_id', '=', 'subscriptions.id')
            ->whereNotNull('subscriptions.plan_id')
            ->select([
                'subscriptions.plan_id as plan_id',
                DB::raw('COUNT(*) as purchases_count'),
                DB::raw('COUNT(DISTINCT transactions.user_id) as unique_users_count'),
            ])
            ->groupBy('subscriptions.plan_id')
            ->get()
            ->keyBy('plan_id');

        $totalPayingUsers = $planUserSpendAggregates->pluck('user_id')->unique()->count();
        $totalSubscriptions = $planSubscriptionAggregates->sum('subscriptions_count');
        // Overall revenue card: sum of paid (active + success) revenue across all plans
        $overallRevenue = (float) $planRevenueAggregates->sum('total_amount');

        $plans = Plan::all()->keyBy('id');
        $planIds = $planPurchaseAggregates->keys()
            ->merge($planRevenueAggregates->keys())
            ->unique()
            ->filter()
            ->values();

        $userIds = $planUserSpendAggregates->pluck('user_id')->unique();

        $topMonthlyPayers = (clone $paidTransactions)
            ->select([
                'user_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as payments_count'),
            ])
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('user_id')
            ->with('user:id,first_name,last_name,phone,email')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();

        $topLifetimePayers = (clone $paidTransactions)
            ->select([
                'user_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as payments_count'),
            ])
            ->groupBy('user_id')
            ->with('user:id,first_name,last_name,phone,email')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $userIds = $userIds
            ->merge($topMonthlyPayers->pluck('user_id'))
            ->merge($topLifetimePayers->pluck('user_id'))
            ->unique();

        /** @var Collection<int,\App\Models\User> $users */
        $users = User::query()
            ->select(['id', 'first_name', 'last_name', 'phone', 'email'])
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $planOverview = $planIds->map(function ($planId) use ($plans, $planPurchaseAggregates, $planRevenueAggregates) {
            $plan = $plans->get($planId);
            $purchaseData = $planPurchaseAggregates->get($planId);
            $revenueData = $planRevenueAggregates->get($planId);

            return [
                'id' => $planId,
                'name' => $plan?->name ?? 'Unknown Plan',
                'price' => (float) ($plan?->price ?? 0),
                'fake_price' => (float) ($plan?->fake_price ?? 0),
                // Use only active purchases for counts shown in the card
                'subscriptions' => (int) ($purchaseData->purchases_count ?? 0),
                'unique_users' => (int) ($purchaseData->unique_users_count ?? 0),
                'revenue' => (float) ($revenueData->total_amount ?? 0),
            ];
        })->values();

        $planUserTotals = $planUserSpendAggregates
            ->groupBy('plan_id')
            ->filter(fn ($records, $planId) => !is_null($planId))
            ->map(function (Collection $records, $planId) use ($plans, $users) {
                $plan = $plans->get($planId);

                return [
                    'plan_id' => $planId,
                    'plan_name' => $plan?->name ?? 'Unknown Plan',
                    'users' => $records
                        ->sortByDesc('total_amount')
                        ->take(5)
                        ->values()
                        ->map(function ($item) use ($users) {
                            $user = $users->get($item->user_id);
                            $name = trim(collect([$user?->first_name, $user?->last_name])->filter()->implode(' '));

                            return [
                                'user_id' => $item->user_id,
                                'user_name' => $name !== '' ? $name : ($user?->phone ?? 'Unknown User'),
                                'total_amount' => (float) $item->total_amount,
                                'payments_count' => (int) $item->payments_count,
                            ];
                        }),
                ];
            })
            ->values();

        $monthlyPayers = $topMonthlyPayers->map(function (Transaction $item) {
            $user = $item->user;
            $name = trim(collect([$user?->first_name, $user?->last_name])->filter()->implode(' '));

            return [
                'user_id' => $item->user_id,
                'user_name' => $name !== '' ? $name : ($user?->phone ?? 'Unknown User'),
                'phone' => $user?->phone,
                'total_amount' => (float) $item->total_amount,
                'payments_count' => (int) $item->payments_count,
            ];
        });

        $lifetimePayers = $topLifetimePayers->map(function (Transaction $item) {
            $user = $item->user;
            $name = trim(collect([$user?->first_name, $user?->last_name])->filter()->implode(' '));

            return [
                'user_id' => $item->user_id,
                'user_name' => $name !== '' ? $name : ($user?->phone ?? 'Unknown User'),
                'phone' => $user?->phone,
                'total_amount' => (float) $item->total_amount,
                'payments_count' => (int) $item->payments_count,
            ];
        });

        $planRevenueChart = [
            'labels' => $planOverview->pluck('name'),
            'revenue' => $planOverview->pluck('revenue'),
            'unique_users' => $planOverview->pluck('unique_users'),
        ];

        return view('admin::Admin.Dashboard.billing', [
            'metrics' => [
                'total_plans' => $totalPlans,
                'total_subscriptions' => (int) $totalSubscriptions,
                'total_paying_users' => (int) $totalPayingUsers,
                'overall_revenue' => (float) $overallRevenue,
            ],
            'planOverview' => $planOverview,
            'planUserTotals' => $planUserTotals,
            'monthlyPayers' => $monthlyPayers,
            'lifetimePayers' => $lifetimePayers,
            'planRevenueChart' => $planRevenueChart,
        ]);
    }
}
