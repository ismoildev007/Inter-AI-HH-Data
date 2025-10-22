@extends('admin::components.layouts.master')

@section('content')
@php
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User #'.$user->id;
@endphp

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">User Subscriptions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->id }}</a></li>
            <li class="breadcrumb-item">Subscriptions</li>
        </ul>
    </div>

</div>

<div class="user-subscriptions-hero">
    <div class="user-subscriptions-hero__content">
        <div>
            <span class="badge bg-soft-primary text-primary text-uppercase fw-semibold">Subscriber</span>
            <h1 class="hero-title mt-3 mb-1">{{ $fullName }}</h1>
            <p class="hero-subtitle mb-0 text-muted">
                Manage active plans, renewal cadence, and automation credits for this user.
            </p>
        </div>
        <div class="hero-stats">
            <div class="hero-stat-card">
                <span class="label">Total</span>
                <span class="value">{{ number_format($stats['total'] ?? 0) }}</span>
                <span class="hint">{{ number_format($stats['active'] ?? 0) }} active · {{ number_format($stats['pending'] ?? 0) }} pending</span>
            </div>
            <div class="hero-stat-card">
                <span class="label">Expired</span>
                <span class="value text-muted">{{ number_format($stats['expired'] ?? 0) }}</span>
                <span class="hint">Require reactivation</span>
            </div>
            <div class="hero-stat-card">
                <span class="label">Remaining credits</span>
                <span class="value">{{ number_format($stats['remainingCredits'] ?? 0) }}</span>
                <span class="hint">Auto responses left</span>
            </div>
        </div>
    </div>
</div>

<div class="subscriptions-filter card">
    <div class="card-body">
        <form action="{{ route('admin.users.subscriptions.index', $user->id) }}" method="GET" class="d-flex flex-wrap align-items-end gap-3">
            <div class="form-floating">
                <select class="form-select" name="status" id="status-filter">
                    @php
                        $statusOptions = ['all' => 'All statuses', 'active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'cancelled' => 'Cancelled'];
                    @endphp
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <label for="status-filter">Status</label>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="feather-filter me-1"></i> Apply
                </button>
                @if($status !== 'all')
                    <a href="{{ route('admin.users.subscriptions.index', $user->id) }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="subscriptions-table card">
    <div class="card-body p-0">
        <div class="subscriptions-table__head">
            <div class="cell">Plan</div>
            <div class="cell">Status</div>
            <div class="cell">Period</div>
            <div class="cell text-center">Credits</div>
            <div class="cell text-end">Created</div>
        </div>
        <div class="subscriptions-table__body">
            @forelse($subscriptions as $subscription)
                <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="subscriptions-table__row">
                    <div class="cell">
                        <span class="plan-name">{{ $subscription->plan?->name ?? '—' }}</span>
                        <span class="plan-id text-muted small">#{{ $subscription->id }}</span>
                    </div>
                    <div class="cell cell--status">
                        @php $statusLabel = strtolower($subscription->status ?? 'unknown'); @endphp
                        <span class="status-pill status-pill--{{ $statusLabel }}">{{ ucfirst($subscription->status ?? 'unknown') }}</span>
                    </div>
                    <div class="cell">
                        <div>{{ optional($subscription->starts_at)->format('M d, Y') ?? '—' }} → {{ optional($subscription->ends_at)->format('M d, Y') ?? '—' }}</div>
                        <span class="text-muted small">{{ optional($subscription->ends_at)->diffForHumans() }}</span>
                    </div>
                    <div class="cell text-center">
                        <div class="fw-semibold">{{ number_format((int) $subscription->remaining_auto_responses) }}</div>
                        <span class="text-muted small">Remaining</span>
                    </div>
                    <div class="cell text-end">
                        <div>{{ optional($subscription->created_at)->format('M d, Y') ?? '—' }}</div>
                        <span class="text-muted small">{{ optional($subscription->created_at)->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div class="subscriptions-table__empty text-center py-5">
                    <h6 class="fw-semibold mb-1">No subscriptions yet</h6>
                    <p class="text-muted mb-0">This user has not been assigned any plans.</p>
                </div>
            @endforelse
        </div>
    </div>
    @if($subscriptions instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="subscriptions-table__pagination">
            {{ $subscriptions->links('vendor.pagination.bootstrap-5') }}
        </div>
    @endif
</div>

<style>
.user-subscriptions-hero {
    margin: 0 1.5rem 1.5rem;
        border-radius: 26px;
        padding: 38px 44px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(59, 130, 246, 0.12));
        border: 1px solid rgba(99, 102, 241, 0.18);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .subscriptions-filter {
        margin: 0 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 22px 52px rgba(15, 46, 122, 0.12);
    }
    .subscriptions-filter .card-body {
        padding: 26px 28px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .user-subscriptions-hero__content {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        align-items: center;
        justify-content: space-between;
    }
    .hero-title {
        font-size: clamp(2rem, 2.6vw, 2.5rem);
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .hero-subtitle {
        max-width: 480px;
        line-height: 1.6;
    }
    .hero-stats {
        flex: 1 1 300px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px;
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
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.74rem;
        color: #94a3b8;
    }
    .hero-stat-card .value {
        font-weight: 700;
        font-size: 1.45rem;
        color: #1f2a55;
    }
    .hero-stat-card .hint {
        font-size: 0.82rem;
        color: #6b7280;
    }

    .subscriptions-table {
        margin: 0 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.85);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .subscriptions-table__head {
        display: grid;
        grid-template-columns: 28% 18% 22% 12% 20%;
        padding: 18px 26px;
        background: rgba(248, 250, 252, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
    .subscriptions-table__row {
        display: grid;
        grid-template-columns: 28% 18% 22% 12% 20%;
        padding: 18px 26px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        transition: background 0.18s ease;
    }
    .subscriptions-table__row:hover {
        background: rgba(59, 130, 246, 0.08);
    }
    .subscriptions-table__row .cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
    }
    .subscriptions-table__row .cell--status {
        align-items: flex-start;
    }
    .subscriptions-table__row .cell--status {
        align-items: flex-start;
    }
    .plan-name {
        font-weight: 600;
        color: #2563eb;
    }
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
        background: rgba(148, 163, 184, 0.22);
        color: #0f172a;
        margin-top: 6px;
    }
    .status-pill--active { background: rgba(20, 184, 166, 0.18); color: #047857; }
    .status-pill--pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
    .status-pill--expired { background: rgba(148, 163, 184, 0.2); color: #4b5563; }
    .status-pill--cancelled { background: rgba(239, 68, 68, 0.22); color: #b91c1c; }

    .subscriptions-table__empty {
        padding: 36px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }
    .subscriptions-table__pagination {
        padding: 18px 26px;
        background: #fff;
    }

    @media (max-width: 991px) {
        .subscriptions-table__head {
            display: none;
        }
        .subscriptions-table__row {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .subscriptions-table__row .cell {
            align-items: flex-start;
        }
    }
</style>
@endsection
