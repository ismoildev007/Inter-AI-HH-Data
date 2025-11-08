@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Transactions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Transactions</li>
        </ul>
    </div>
</div>

<div class="transactions-hero">
    <div class="transactions-hero__content">
        <div class="transactions-hero__intro">
            <span class="transactions-hero__badge">
                <i class="feather-credit-card"></i>
                Cashflow
            </span>
            <h1 class="transactions-hero__title">Payment activity overview</h1>
            <p class="transactions-hero__subtitle">
                Inspect purchase conversions, monitor processor states, and keep a pulse on total revenue generated.
            </p>
        </div>
        <div class="transactions-hero__stats">
            <div class="transactions-stat-card">
                <span class="label">Total Volume</span>
                <span class="value">{{ number_format((float) $totalVolume, 2, '.', ' ') }} UZS</span>
                <span class="hint">All-time processed</span>
            </div>
            <div class="transactions-stat-card">
                <span class="label">Active Volume</span>
                <span class="value">{{ number_format((float) ($activeVolume ?? 0), 2, '.', ' ') }} UZS</span>
                <span class="hint">Currently active payments</span>
            </div>
            <div class="transactions-stat-card">
                <span class="label">Active</span>
                <span class="value text-success">{{ number_format($stats['active'] ?? 0) }}</span>
                <span class="hint">Currently delivering access</span>
            </div>
            <div class="transactions-stat-card">
                <span class="label">Pending</span>
                <span class="value text-warning">{{ number_format($stats['pending'] ?? 0) }}</span>
                <span class="hint">Awaiting confirmation</span>
            </div>
            <div class="transactions-stat-card">
                <span class="label">Expired</span>
                <span class="value text-muted">{{ number_format($stats['expired'] ?? 0) }}</span>
                <span class="hint">Failed or lapsed</span>
            </div>
        </div>
    </div>
</div>

<div class="transactions-filter card">
    <div class="card-body">
        <div class="transactions-filter__header">
            <h6 class="mb-1 fw-semibold">Filter ledger</h6>
            <p class="text-muted mb-0 small">Refine by status, method, subscriber or time window.</p>
        </div>
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="transactions-filter__form">
            <div class="transactions-filter__row">
                <div class="input-group transactions-filter__search">
                    <span class="input-group-text"><i class="feather-search"></i></span>
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search transaction ID, email, plan or status">
                </div>
                <div class="form-floating">
                    <select class="form-select" name="status" id="transactions-status">
                        @php
                            $statusOptions = ['all' => 'All statuses', 'active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'cancelled' => 'Cancelled'];
                        @endphp
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <label for="transactions-status">Status</label>
                </div>
                <div class="form-floating">
                    <select class="form-select" name="method" id="transactions-method">
                        <option value="all">All methods</option>
                        @foreach($methods as $methodOption)
                            <option value="{{ $methodOption }}" {{ $method === $methodOption ? 'selected' : '' }}>
                                {{ ucfirst($methodOption) }}
                            </option>
                        @endforeach
                    </select>
                    <label for="transactions-method">Method</label>
                </div>
            </div>
            <div class="transactions-filter__row">
                <div class="form-floating">
                    <input type="date" name="from" id="transactions-from" class="form-control" value="{{ $from }}">
                    <label for="transactions-from">From</label>
                </div>
                <div class="form-floating">
                    <input type="date" name="to" id="transactions-to" class="form-control" value="{{ $to }}">
                    <label for="transactions-to">To</label>
                </div>
                <div class="transactions-filter__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="feather-filter me-1"></i> Apply
                    </button>
                    @if($search !== '' || $status !== 'all' || $method !== 'all' || $from !== '' || $to !== '')
                        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="transactions-table card">
    <div class="card-body p-0">
        <div class="transactions-table__head">
            <div class="cell">ID</div>
            <div class="cell">Subscriber</div>
            <div class="cell">Plan</div>
            <div class="cell">Status</div>
            <div class="cell">Method</div>
            <div class="cell text-end">Amount</div>
            <div class="cell text-end">Created</div>
        </div>
        <div class="transactions-table__body">
            @php
                $rowStart = method_exists($transactions, 'firstItem') ? ($transactions->firstItem() ?? 1) : 1;
            @endphp
            @forelse($transactions as $tx)
                @php $rowNumber = $rowStart + $loop->index; @endphp
                <a href="{{ route('admin.transactions.show', $tx) }}" class="transactions-table__row">
                    <div class="cell cell--id">
                        <span class="table-id-pill">{{ $rowNumber }}</span>
                        <!-- <span class="table-id-hint">ID #{{ $tx->id }}</span> -->
                    </div>
                    <div class="cell cell--subscriber">
                        <span class="subscriber-name">{{ trim(($tx->user->first_name ?? '').' '.($tx->user->last_name ?? '')) ?: 'User #'.$tx->user_id }}</span>
                        <!-- <span class="subscriber-meta">{{ $tx->user?->email }}</span> -->
                    </div>
                    <div class="cell cell--plan">
                        @php
                            $subscription = $tx->subscription;
                            $plan = $subscription?->plan;
                        @endphp
                        <span class="plan-name">{{ $plan?->name ?? '—' }}</span>
                        <span class="plan-meta">
                            @if($subscription)
                                Sub #{{ $subscription->id }}
                                @if($subscription->status)
                                    <span class="mx-1">•</span>{{ ucfirst($subscription->status) }}
                                @endif
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="cell cell--status">
                        @php
                            $raw = strtolower($tx->payment_status ?? '');
                            $norm = in_array($raw, ['active','success']) ? 'active'
                                : ($raw === 'pending' ? 'pending'
                                : ($raw === 'cancelled' ? 'cancelled' : 'expired'));
                        @endphp
                        <span class="status-pill status-pill--{{ $norm }}">{{ ucfirst($norm) }}</span>
                    </div>
                    <div class="cell">
                        <span class="method">{{ ucfirst($tx->payment_method ?? '—') }}</span>
                        <span class="state text-muted small">State {{ $tx->state ?? '—' }}</span>
                    </div>
                    <div class="cell text-end">
                        <span class="amount">{{ number_format((float) $tx->amount, 2, '.', ' ') }} {{ strtoupper($tx->currency ?? 'UZS') }}</span>
                    </div>
                    <div class="cell text-end">
                        <span class="date">{{ optional($tx->created_at)->format('M d, Y • H:i') ?? '—' }}</span>
                    </div>
                </a>
            @empty
                <div class="transactions-table__empty text-center py-5">
                    <h6 class="fw-semibold mb-1">No transactions found</h6>
                    <p class="text-muted mb-0">Adjust your filters or check back later for new records.</p>
                </div>
            @endforelse
        </div>
    </div>
    @include('admin::components.pagination', ['paginator' => $transactions])
</div>

<style>
    .transactions-hero {
        margin: 1.5rem 1.5rem 1.5rem;
        border-radius: 28px;
        padding: 40px 44px;
        background: linear-gradient(140deg, rgba(59, 130, 246, 0.08), rgba(37, 99, 235, 0.12));
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
    }
    .transactions-hero__content {
        display: flex;
        flex-wrap: wrap;
        gap: 26px;
        align-items: flex-start;
    }
    .transactions-hero__intro {
        flex: 1 1 320px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .transactions-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 18px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 0.76rem;
    }
    .transactions-hero__title {
        margin: 0;
        font-size: clamp(2rem, 2.6vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #0f172a;
    }
    .transactions-hero__subtitle {
        margin: 0;
        max-width: 480px;
        color: #475569;
        line-height: 1.6;
    }
    .transactions-hero__stats {
        flex: 1 1 280px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
    }
    .transactions-stat-card {
        background: rgba(255, 255, 255, 0.85);
        border-radius: 20px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .transactions-stat-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .transactions-stat-card .value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
    }
    .transactions-stat-card .hint {
        font-size: 0.84rem;
        color: #64748b;
    }
    .transactions-filter {
        margin: 1.5rem 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 22px 52px rgba(15, 46, 122, 0.12);
        margin-bottom: 1.5rem;
    }
    .transactions-filter .card-body {
        padding: 26px 28px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .transactions-filter__form {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .transactions-filter__row {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: center;
    }
    .transactions-filter__search {
        flex: 1 1 320px;
        background: #f8fafc;
        border-radius: 14px;
        padding: 4px;
    }
    .transactions-filter__search .input-group-text {
        border: none;
        background: transparent;
        color: #2563eb;
    }
    .transactions-filter__search .form-control {
        border: none;
        background: transparent;
        padding: 10px 14px;
    }
    .transactions-filter__actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .transactions-table {
        margin: 1.5rem 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.85);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .transactions-table__head {
        display: grid;
        grid-template-columns: 8% 22% 20% 16% 14% 10% 10%;
        padding: 18px 26px;
        background: rgba(248, 250, 252, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
    .transactions-table__body {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 20px 26px 28px;
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.78), rgba(241, 245, 255, 0.65));
    }
    .transactions-table__row {
        display: grid;
        grid-template-columns: 8% 22% 20% 16% 14% 10% 10%;
        padding: 20px 26px;
        text-decoration: none;
        color: inherit;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
        transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .transactions-table__row:hover {
        border-color: rgba(59, 130, 246, 0.28);
        box-shadow: 0 22px 42px rgba(59, 130, 246, 0.12);
        transform: translateY(-3px);
    }
    .transactions-table__row .cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
    }
    .transactions-table__row .cell--id,
    .transactions-table__row .cell--subscriber,
    .transactions-table__row .cell--plan,
    .transactions-table__row .cell--status {
        align-items: flex-start;
    }
    .table-id-pill {
        min-width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, #eff3ff, #dce5ff);
        color: #1f2f7a;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 22px rgba(31, 51, 126, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }
    .table-id-hint {
        display: block;
        margin-top: 6px;
        font-size: 0.68rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .tx-meta,
    .subscriber-meta,
    .plan-meta,
    .state {
        font-size: 0.8rem;
        color: #94a3b8;
    }
    .subscriber-name {
        font-weight: 600;
        color: #0f172a;
    }
    .plan-name {
        font-weight: 600;
        color: #2563eb;
    }
    /* Align status pill styles with Subscriptions */
    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: #0f172a;
        background: rgba(148, 163, 184, 0.2);
        max-width: 160px;
        min-width: 0;
    }
    .status-pill--active { background: rgba(20, 184, 166, 0.18); color: #047857; }
    .status-pill--pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
    .status-pill--expired { background: rgba(148, 163, 184, 0.2); color: #4b5563; }
    .status-pill--cancelled { background: rgba(239, 68, 68, 0.2); color: #b91c1c; }
    .amount {
        font-weight: 600;
        color: #0f172a;
    }
    .date {
        font-weight: 500;
        color: #475569;
    }
    @media (max-width: 991px) {
        .transactions-hero {
            margin: 1.5rem 1rem;
        }
        .transactions-filter,
        .transactions-table {
            margin: 1.5rem 1rem;
        }
        .transactions-table__head {
            display: none;
        }
        .transactions-table__body {
            padding: 18px 18px 24px;
            gap: 12px;
        }
        .transactions-table__row {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 18px;
        }
        .transactions-table__row .cell {
            align-items: flex-start;
        }
    }
</style>
@endsection
