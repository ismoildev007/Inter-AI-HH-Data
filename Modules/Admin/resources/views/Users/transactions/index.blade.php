@extends('admin::components.layouts.master')

@section('content')
@php
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User #'.$user->id;
@endphp

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">User Transactions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->id }}</a></li>
            <li class="breadcrumb-item">Transactions</li>
        </ul>
    </div>

</div>

<div class="user-transactions-hero">
    <div class="user-transactions-hero__content">
        <div>
            <span class="badge bg-soft-primary text-primary text-uppercase fw-semibold">Payment history</span>
            <h1 class="hero-title mt-3 mb-1">{{ $fullName }}</h1>
            <p class="hero-subtitle mb-0 text-muted">
                Detailed ledger of payment events, statuses, and plan allocations.
            </p>
        </div>
        <div class="hero-stats">
            <div class="hero-stat-card">
                <span class="label">Transactions</span>
                <span class="value">{{ number_format($stats['total'] ?? 0) }}</span>
                <span class="hint">{{ number_format($stats['success'] ?? 0) }} success · {{ number_format($stats['pending'] ?? 0) }} pending</span>
            </div>
            <div class="hero-stat-card">
                <span class="label">Failed</span>
                <span class="value text-danger">{{ number_format($stats['failed'] ?? 0) }}</span>
                <span class="hint">Requires attention</span>
            </div>
            <div class="hero-stat-card">
                <span class="label">Volume</span>
                <span class="value">{{ number_format((float)($stats['totalVolume'] ?? 0), 2, '.', ' ') }} UZS</span>
                <span class="hint">Success: {{ number_format((float)($stats['successVolume'] ?? 0), 2, '.', ' ') }} UZS</span>
            </div>
        </div>
    </div>
</div>

<div class="transactions-filter card">
    <div class="card-body">
        <form action="{{ route('admin.users.transactions.index', $user->id) }}" method="GET" class="transactions-filter__form">
            <div class="transactions-filter__row">
                <div class="form-floating">
                    <select class="form-select" name="status" id="status-filter">
                        @php
                            $statusOptions = ['all' => 'All statuses', 'success' => 'Success', 'pending' => 'Pending', 'failed' => 'Failed', 'cancelled' => 'Cancelled'];
                        @endphp
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="status-filter">Status</label>
                </div>
                <div class="form-floating">
                    <select class="form-select" name="method" id="method-filter">
                        <option value="all">All methods</option>
                        @foreach($methods as $methodOption)
                            <option value="{{ $methodOption }}" {{ $method === $methodOption ? 'selected' : '' }}>
                                {{ ucfirst($methodOption) }}
                            </option>
                        @endforeach
                    </select>
                    <label for="method-filter">Method</label>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="feather-filter me-1"></i> Apply
                    </button>
                    @if($status !== 'all' || $method !== 'all')
                        <a href="{{ route('admin.users.transactions.index', $user->id) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="transactions-table card">
    <div class="card-body p-0">
        <div class="transactions-table__head">
            <div class="cell">Transaction</div>
            <div class="cell">Status</div>
            <div class="cell">Method</div>
            <div class="cell">Plan</div>
            <div class="cell text-end">Amount</div>
            <div class="cell text-end">Created</div>
            <div class="cell text-end">Action</div>
        </div>
        <div class="transactions-table__body">
            @forelse($transactions as $transaction)
                <div class="transactions-table__row">
                    <div class="cell">
                        <span class="fw-semibold">#{{ $transaction->id }}</span>
                        <span class="text-muted small">{{ $transaction->transaction_id ?? '—' }}</span>
                    </div>
                    <div class="cell">
                        @php $statusLabel = strtolower($transaction->payment_status ?? 'unknown'); @endphp
                        <span class="status-pill status-pill--{{ $statusLabel }}">{{ ucfirst($transaction->payment_status ?? 'unknown') }}</span>
                    </div>
                    <div class="cell">
                        <span class="fw-semibold">{{ ucfirst($transaction->payment_method ?? '—') }}</span>
                        <span class="text-muted small">State {{ $transaction->state ?? '—' }}</span>
                    </div>
                    <div class="cell">
                        <span class="fw-semibold">{{ $transaction->subscription?->plan?->name ?? '—' }}</span>
                        <span class="text-muted small">
                            @if($transaction->subscription)
                                Sub #{{ $transaction->subscription->id }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="cell text-end">
                        <span class="fw-semibold">{{ number_format((float) $transaction->amount, 2, '.', ' ') }} {{ strtoupper($transaction->currency ?? 'UZS') }}</span>
                    </div>
                    <div class="cell text-end">
                        <div>{{ optional($transaction->create_time)->format('M d, Y • H:i') ?? '—' }}</div>
                        <span class="text-muted small">{{ optional($transaction->create_time)->diffForHumans() }}</span>
                    </div>
                    <div class="cell text-end">
                        <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                            Details
                        </a>
                    </div>
                </div>
            @empty
                <div class="transactions-table__empty text-center py-5">
                    <h6 class="fw-semibold mb-1">No transactions found</h6>
                    <p class="text-muted mb-0">This user has not generated any payment activity.</p>
                </div>
            @endforelse
        </div>
    </div>
    @if($transactions instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="transactions-table__pagination">
            {{ $transactions->links('vendor.pagination.bootstrap-5') }}
        </div>
    @endif
</div>

<style>
.user-transactions-hero {
    margin: 0 1.5rem 1.5rem;
        border-radius: 26px;
        padding: 38px 44px;
        background: linear-gradient(140deg, rgba(59, 130, 246, 0.09), rgba(96, 165, 250, 0.12));
        border: 1px solid rgba(59, 130, 246, 0.18);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .transactions-filter {
        margin: 0 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 22px 52px rgba(15, 46, 122, 0.12);
    }
    .user-transactions-hero__content {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        align-items: center;
        justify-content: space-between;
    }
    .hero-title {
        margin: 0;
        font-size: clamp(2rem, 2.5vw, 2.4rem);
        font-weight: 700;
        color: #0f172a;
    }
    .hero-subtitle {
        margin: 0;
        max-width: 480px;
        color: #475569;
    }
    .hero-stats {
        flex: 1 1 320px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
    }
    .hero-stat-card {
        background: rgba(255, 255, 255, 0.85);
        border-radius: 18px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .hero-stat-card .label {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .hero-stat-card .value {
        font-size: 1.45rem;
        font-weight: 700;
        color: #0f172a;
    }
    .hero-stat-card .hint {
        font-size: 0.82rem;
        color: #64748b;
    }

.transactions-table {
    margin: 0 1.5rem 1.5rem;
    border-radius: 22px;
    border: 1px solid rgba(226, 232, 240, 0.85);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}
    .transactions-table__head {
        display: grid;
        grid-template-columns: 18% 12% 14% 18% 14% 16% 8%;
        padding: 18px 26px;
        background: rgba(248, 250, 252, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
    .transactions-table__row {
        display: grid;
        grid-template-columns: 18% 12% 14% 18% 14% 16% 8%;
        padding: 18px 26px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        transition: background 0.18s ease;
    }
    .transactions-table__row:hover {
        background: rgba(59, 130, 246, 0.08);
    }
    .transactions-table__row .cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        background: rgba(148, 163, 184, 0.22);
        color: #0f172a;
    }
    .status-pill--success { background: rgba(20, 184, 166, 0.2); color: #047857; }
    .status-pill--pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
    .status-pill--failed { background: rgba(239, 68, 68, 0.22); color: #b91c1c; }
    .status-pill--cancelled { background: rgba(148, 163, 184, 0.22); color: #475569; }

    .transactions-table__empty {
        padding: 36px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }
    .transactions-table__pagination {
        padding: 18px 26px;
        background: #fff;
    }

    .transactions-filter__form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .transactions-filter__row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    @media (max-width: 991px) {
        .transactions-table__head {
            display: none;
        }
        .transactions-table__row {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .transactions-table__row .cell {
            align-items: flex-start;
        }
    }
</style>
@endsection
