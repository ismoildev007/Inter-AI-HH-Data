<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Users list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->where(function ($inner) use ($like, $search, $normalized) {
                    $inner->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhere('phone', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin::Users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Admin user search placeholder.
     */
    public function adminCheck()
    {
        $allUsers = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'status',
                'admin_check_status',
                'created_at',
                'updated_at',
            ])
            ->where(function ($query) {
                $query->whereNull('admin_check_status')
                    ->orWhere('admin_check_status', false);
            })
            ->orderByRaw("CASE WHEN status = 'working' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        $verifiedWorkingUsers = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'status',
                'admin_check_status',
                'created_at',
                'updated_at',
            ])
            ->where('status', 'working')
            ->where('admin_check_status', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $checkedButNotWorkingUsers = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'status',
                'admin_check_status',
                'created_at',
                'updated_at',
            ])
            ->where('status', 'not working')
            ->where('admin_check_status', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => User::count(),
            'working' => User::where('status', 'working')->count(),
            'notWorking' => User::where('status', 'not working')->count(),
            'adminChecked' => User::where('admin_check_status', true)->count(),
        ];

        return view('admin::Users.admin-user-index', [
            'allUsers' => $allUsers,
            'verifiedWorkingUsers' => $verifiedWorkingUsers,
            'checkedButNotWorkingUsers' => $checkedButNotWorkingUsers,
            'stats' => $stats,
        ]);
    }

    /**
     * Admin check detail placeholder.
     */
    public function adminCheckShow(User $user)
    {
        $data = $this->buildUserDetailContext($user->id);

        return view('admin::Users.admin-user-show', $data);
    }

    /**
     * Mark a working user as not working.
     */
    public function adminCheckMarkNotWorking(User $user)
    {
        $status = mb_strtolower((string) ($user->status ?? ''), 'UTF-8');

        if ($status !== 'working') {
            return redirect()
                ->back()
                ->with('error', 'Foydalanuvchi allaqachon working holatida emas.');
        }

        $user->forceFill([
            'status' => 'not working',
            'admin_check_status' => true,
        ])->save();

        return redirect()
            ->route('admin.users.admin_check')
            ->with('status', 'Foydalanuvchi admin tekshiruvidan o‘tkazilib, “not working” holatiga o‘tkazildi.');
    }

    /**
     * Approve a working user via admin check.
     */
    public function adminCheckVerify(User $user)
    {
        $status = mb_strtolower((string) ($user->status ?? ''), 'UTF-8');

        if ($status !== 'working') {
            return redirect()
                ->back()
                ->with('error', 'Faqat working holatidagi foydalanuvchilarni tasdiqlash mumkin.');
        }

        if ($user->admin_check_status) {
            return redirect()
                ->back()
                ->with('status', 'Bu foydalanuvchi allaqachon admin tomonidan tasdiqlangan.');
        }

        $user->forceFill([
            'admin_check_status' => true,
        ])->save();

        return redirect()
            ->route('admin.users.admin_check')
            ->with('status', 'Foydalanuvchi admin tomonidan tasdiqlandi.');
    }

    /**
     * Show user.
     */
    public function show($id)
    {
        $data = $this->buildUserDetailContext((int) $id);

        return view('admin::Users.show', $data);
    }

    /**
     * Build shared context for user detail pages.
     *
     * @return array<string, mixed>
     */
    private function buildUserDetailContext(int $userId): array
    {
        $user = User::with([
            'role',
            'resumes',
            'subscriptions.plan',
        ])->findOrFail($userId);

        $matchResultsQuery = MatchResult::query()
            ->whereNotNull('vacancy_id')
            ->whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        $matchedVacancyCount = (clone $matchResultsQuery)
            ->select('vacancy_id')
            ->distinct()
            ->count();

        $recentVacancyMatches = (clone $matchResultsQuery)
            ->with('vacancy')
            ->latest()
            ->get()
            ->filter(fn (MatchResult $result) => $result->vacancy)
            ->unique('vacancy_id')
            ->take(5)
            ->values();

        $subscriptions = $user->subscriptions()
            ->with('plan')
            ->orderByDesc('starts_at')
            ->get();

        $subscriptionStats = [
            'total' => $subscriptions->count(),
            'active' => $subscriptions->where('status', 'active')->count(),
            'pending' => $subscriptions->where('status', 'pending')->count(),
            'expired' => $subscriptions->where('status', 'expired')->count(),
            'remainingCredits' => $subscriptions->sum('remaining_auto_responses'),
        ];

        $transactionBase = Transaction::query()->where('user_id', $user->id);

        $recentTransactions = (clone $transactionBase)
            ->with(['subscription.plan'])
            ->orderByDesc('create_time')
            ->limit(8)
            ->get();

        $transactionStats = [
            'totalCount' => (clone $transactionBase)->count(),
            'successCount' => (clone $transactionBase)->where('payment_status', 'success')->count(),
            'failedCount' => (clone $transactionBase)->where('payment_status', 'failed')->count(),
            'pendingCount' => (clone $transactionBase)->where('payment_status', 'pending')->count(),
            'totalVolume' => (clone $transactionBase)->sum('amount'),
            'successVolume' => (clone $transactionBase)->where('payment_status', 'success')->sum('amount'),
        ];

        return [
            'user' => $user,
            'matchedVacancyCount' => $matchedVacancyCount,
            'recentVacancyMatches' => $recentVacancyMatches,
            'subscriptions' => $subscriptions,
            'subscriptionStats' => $subscriptionStats,
            'recentTransactions' => $recentTransactions,
            'transactionStats' => $transactionStats,
        ];
    }

    /**
     * Show matched vacancies for a user.
     */
    public function vacancies(User $user)
    {
        $vacancyQuery = Vacancy::query()
            ->whereIn('id', function ($subQuery) use ($user) {
                $subQuery->select('match_results.vacancy_id')
                    ->from('match_results')
                    ->join('resumes', 'match_results.resume_id', '=', 'resumes.id')
                    ->whereNull('match_results.deleted_at')
                    ->where('match_results.vacancy_id', '>', 0)
                    ->where('resumes.user_id', $user->id);
            })
            ->orderByDesc('id');

        /** @var Collection<string,int> $sourceCounts */
        $sourceCounts = (clone $vacancyQuery)
            ->reorder()
            ->selectRaw("LOWER(COALESCE(source, 'unknown')) as source_key, COUNT(*) as aggregate")
            ->groupBy('source_key')
            ->pluck('aggregate', 'source_key')
            ->map(fn ($count) => (int) $count);

        $vacancies = $vacancyQuery->paginate(20);

        $matchSummaries = MatchResult::query()
            ->with('resume')
            ->whereIn('vacancy_id', $vacancies->pluck('id'))
            ->whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->groupBy('vacancy_id')
            ->map(function ($group) {
                $bestMatch = $group->sortByDesc(function (MatchResult $match) {
                    $score = $match->score_percent ?? 0;
                    $timestamp = optional($match->created_at ?? $match->updated_at)->timestamp ?? 0;

                    return [$score, $timestamp];
                })->first();

                $latestMatch = $group->sortByDesc(function (MatchResult $match) {
                    return optional($match->created_at ?? $match->updated_at)->timestamp ?? 0;
                })->first();

                return [
                    'best_match' => $bestMatch,
                    'latest_match' => $latestMatch,
                    'resume_titles' => $group->pluck('resume.title')->filter()->unique()->values(),
                ];
            });

        return view('admin::Users.vacancies.index', [
            'user' => $user,
            'vacancies' => $vacancies,
            'matchSummaries' => $matchSummaries,
            'sourceTotals' => [
                'telegram' => $sourceCounts->get('telegram', 0),
                'hh' => $sourceCounts->get('hh', 0),
            ],
        ]);
    }

    /**
     * Show subscriptions belonging to a specific user.
     */
    public function subscriptions(User $user, Request $request)
    {
        $status = strtolower((string) $request->query('status', 'all'));

        $subscriptionQuery = Subscription::query()
            ->where('user_id', $user->id)
            ->with('plan')
            ->orderByDesc('starts_at');

        if (in_array($status, ['active', 'pending', 'expired', 'cancelled'], true)) {
            $subscriptionQuery->where('status', $status);
        }

        $subscriptions = $subscriptionQuery->paginate(12)->withQueryString();

        $stats = [
            'total' => Subscription::where('user_id', $user->id)->count(),
            'active' => Subscription::where('user_id', $user->id)->where('status', 'active')->count(),
            'pending' => Subscription::where('user_id', $user->id)->where('status', 'pending')->count(),
            'expired' => Subscription::where('user_id', $user->id)->where('status', 'expired')->count(),
            'remainingCredits' => Subscription::where('user_id', $user->id)->sum('remaining_auto_responses'),
        ];

        return view('admin::Users.subscriptions.index', [
            'user' => $user,
            'subscriptions' => $subscriptions,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    /**
     * Show transactions belonging to a specific user.
     */
    public function transactions(User $user, Request $request)
    {
        $status = strtolower((string) $request->query('status', 'all'));
        $method = strtolower((string) $request->query('method', 'all'));

        $transactionQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->with(['subscription.plan'])
            ->orderByDesc('create_time');

        if ($status !== 'all' && $status !== '') {
            $transactionQuery->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', [$status]);
        }

        if ($method !== 'all' && $method !== '') {
            $transactionQuery->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', [$method]);
        }

        $transactions = $transactionQuery->paginate(15)->withQueryString();

        $baseAggregate = Transaction::query()->where('user_id', $user->id);

        $stats = [
            'total' => (clone $baseAggregate)->count(),
            'success' => (clone $baseAggregate)->where('payment_status', 'success')->count(),
            'pending' => (clone $baseAggregate)->where('payment_status', 'pending')->count(),
            'failed' => (clone $baseAggregate)->where('payment_status', 'failed')->count(),
            'totalVolume' => (clone $baseAggregate)->sum('amount'),
            'successVolume' => (clone $baseAggregate)->where('payment_status', 'success')->sum('amount'),
        ];

        $methods = Transaction::query()
            ->where('user_id', $user->id)
            ->selectRaw('LOWER(payment_method) as method')
            ->whereNotNull('payment_method')
            ->groupBy('method')
            ->pluck('method')
            ->filter()
            ->unique()
            ->values();

        return view('admin::Users.transactions.index', [
            'user' => $user,
            'transactions' => $transactions,
            'status' => $status,
            'method' => $method,
            'stats' => $stats,
            'methods' => $methods,
        ]);
    }
}
