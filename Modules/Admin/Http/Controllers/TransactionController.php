<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = strtolower((string) $request->query('status', 'all'));
        $method = strtolower((string) $request->query('method', 'all'));
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Transaction::query()
            ->with([
                'user:id,first_name,last_name,email',
                'subscription' => function ($query) {
                    $query->select('id', 'plan_id', 'status')
                        ->with(['plan:id,name,auto_response_limit']);
                },
            ])
            ->latest('create_time');

        if ($search !== '') {
            $like = '%'.mb_strtolower($search, 'UTF-8').'%';
            $query->where(function ($inner) use ($like, $search) {
                $inner->whereRaw('LOWER(COALESCE(payment_status, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(payment_method, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(transaction_id, \'\')) LIKE ?', [$like])
                    ->orWhereHas('subscription.plan', function ($planQuery) use ($like) {
                        $planQuery->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('user', function ($userQuery) use ($like, $search) {
                        $userQuery->whereRaw('LOWER(first_name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

                        if (ctype_digit($search)) {
                            $userQuery->orWhere('id', (int) $search);
                        }
                    });

                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search);
                }
            });
        }

        if ($status !== 'all' && $status !== '') {
            // Align filtering with subscription-like statuses
            if ($status === 'active') {
                $query->whereIn('payment_status', ['active', 'success']);
            } elseif ($status === 'expired') {
                $query->whereIn('payment_status', ['expired', 'failed']);
            } elseif ($status === 'cancelled') {
                $query->where('payment_status', 'cancelled');
            } elseif ($status === 'pending') {
                $query->where('payment_status', 'pending');
            } else {
                $query->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', [$status]);
            }
        }

        if ($method !== 'all' && $method !== '') {
            $query->whereRaw('LOWER(COALESCE(payment_method, \'\')) = ?', [$method]);
        }

        $fromDate = null;
        $toDate = null;

        try {
            if ($from) {
                $fromDate = Carbon::parse($from)->startOfDay();
            }
        } catch (\Throwable $e) {
            $fromDate = null;
        }

        try {
            if ($to) {
                $toDate = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable $e) {
            $toDate = null;
        }

        if ($fromDate) {
            $query->where('create_time', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('create_time', '<=', $toDate);
        }

        $transactions = $query->paginate(100)->withQueryString();

        $baseAggregate = Transaction::query();

        $stats = [
            'total' => (clone $baseAggregate)->count(),
            'active' => (clone $baseAggregate)->whereIn('payment_status', ['active', 'success'])->count(),
            'pending' => (clone $baseAggregate)->where('payment_status', 'pending')->count(),
            'expired' => (clone $baseAggregate)->whereIn('payment_status', ['expired', 'failed'])->count(),
            'cancelled' => (clone $baseAggregate)->where('payment_status', 'cancelled')->count(),
        ];

        $totalVolume = (clone $baseAggregate)->sum('amount');

        $methods = Transaction::query()
            ->selectRaw('LOWER(payment_method) as method')
            ->whereNotNull('payment_method')
            ->groupBy('method')
            ->orderBy('method')
            ->pluck('method')
            ->filter()
            ->unique()
            ->values();

        return view('admin::Transactions.index', [
            'transactions' => $transactions,
            'search' => $search,
            'status' => $status,
            'method' => $method,
            'from' => $fromDate?->format('Y-m-d') ?? '',
            'to' => $toDate?->format('Y-m-d') ?? '',
            'stats' => $stats,
            'totalVolume' => $totalVolume,
            'methods' => $methods,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        $transaction->load([
            'user',
            'subscription.plan',
        ]);

        $timeline = collect([
            [
                'label' => 'Created',
                'value' => optional($transaction->create_time)->format('M d, Y • H:i'),
                'subtitle' => 'Payment initiated',
            ],
            [
                'label' => 'Performed',
                'value' => optional($transaction->perform_time)->format('M d, Y • H:i'),
                'subtitle' => 'Funds confirmed',
            ],
            [
                'label' => 'Cancelled',
                'value' => optional($transaction->cancel_time)->format('M d, Y • H:i'),
                'subtitle' => $transaction->reason ? 'Reason: '.$transaction->reason : 'No cancellation recorded',
            ],
        ]);

        return view('admin::Transactions.show', [
            'transaction' => $transaction,
            'timeline' => $timeline,
        ]);
    }
}
