@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Subscriptions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Subscriptions</li>
        </ul>
    </div>
</div>

<div class="subscriptions-hero">
    <div class="subscriptions-hero__content">
        <div class="subscriptions-hero__intro">
            <span class="subscriptions-hero__badge">
                <i class="feather-zap"></i>
                Revenue Pulse
            </span>
            <h1 class="subscriptions-hero__title">Monitor plan activations</h1>
            <p class="subscriptions-hero__subtitle">
                Track lifecycle stages, plan mix and remaining automation credits across the full subscriber base.
            </p>
        </div>
        <div class="subscriptions-hero__stats">
            <div class="subscriptions-stat-card">
                <span class="label">Total</span>
                <span class="value">{{ number_format($stats['total'] ?? 0) }}</span>
                <span class="hint">All time subscriptions</span>
            </div>
            <div class="subscriptions-stat-card">
                <span class="label">Active</span>
                <span class="value text-success">{{ number_format($stats['active'] ?? 0) }}</span>
                <span class="hint">Currently delivering access</span>
            </div>
            <div class="subscriptions-stat-card">
                <span class="label">Expired</span>
                <span class="value text-muted">{{ number_format($stats['expired'] ?? 0) }}</span>
                <span class="hint">Require renewal or upsell</span>
            </div>
            <div class="subscriptions-stat-card">
                <span class="label">Pending</span>
                <span class="value text-warning">{{ number_format($stats['pending'] ?? 0) }}</span>
                <span class="hint">Awaiting confirmation</span>
            </div>
        </div>
    </div>
</div>

<div class="subscriptions-filter card">
    <div class="card-body">
        <div class="subscriptions-filter__header">
            <div>
                <h6 class="mb-1 fw-semibold">Search &amp; filter</h6>
                <p class="text-muted mb-0 small">Find subscriptions by plan, person, status or ID.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="subscriptions-filter__form">
            <div class="input-group subscriptions-filter__search">
                <span class="input-group-text"><i class="feather-search"></i></span>
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search user email, plan name, status or subscription ID">
            </div>
            <div class="subscriptions-filter__selects">
                <div class="form-floating">
                    <select class="form-select" id="status-filter" name="status">
                        @php
                            $statuses = ['all' => 'All statuses', 'active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'cancelled' => 'Cancelled'];
                        @endphp
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="status-filter">Status</label>
                </div>
                <div class="form-floating">
                    <select class="form-select" id="plan-filter" name="plan">
                        <option value="0">All plans</option>
                        @foreach($planOptions as $id => $name)
                            <option value="{{ $id }}" {{ (int)$planId === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <label for="plan-filter">Plan</label>
                </div>
            </div>
            <div class="subscriptions-filter__actions">
                <button type="submit" class="btn btn-primary">
                    <i class="feather-filter me-1"></i> Apply
                </button>
                @if($search !== '' || $status !== 'all' || (int)$planId !== 0)
                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="subscriptions-table card">
    <div class="card-body p-0">
        <div class="subscriptions-table__head">
            <div class="cell">ID</div>
            <div class="cell">Subscriber</div>
            <div class="cell">Plan</div>
            <div class="cell">Status</div>
            <div class="cell">Starts</div>
            <div class="cell">Ends</div>
            <div class="cell text-end">Credits left</div>
        </div>
        <div class="subscriptions-table__body">
            @forelse($subscriptions as $subscription)
                <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="subscriptions-table__row">
                    <div class="cell cell--id">
                        <span class="table-id-pill">{{ $subscription->id }}</span>
                    </div>
                    <div class="cell cell--subscriber">
                        <span class="subscriber-name">{{ $subscription->user?->first_name }} {{ $subscription->user?->last_name }}</span>
                        <span class="subscriber-meta">{{ $subscription->user?->email ?? 'User #'.$subscription->user_id }}</span>
                    </div>
                    <div class="cell cell--plan">
                        <span class="plan-name">{{ $subscription->plan?->name ?? '—' }}</span>
                        <span class="plan-meta">
                            @if($subscription->plan)
                                Plan #{{ $subscription->plan->id }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="cell cell--status">
                        <span class="status-pill status-pill--{{ $subscription->status ?? 'unknown' }}">
                            {{ ucfirst($subscription->status ?? 'unknown') }}
                        </span>
                    </div>
                    <div class="cell">
                        <span class="date">{{ optional($subscription->starts_at)->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="cell">
                        <span class="date">{{ optional($subscription->ends_at)->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="cell text-end">
                        <span class="credits">{{ number_format((int) $subscription->remaining_auto_responses) }}</span>
                    </div>
                </a>
            @empty
                <div class="subscriptions-table__empty text-center py-5">
                    <h6 class="fw-semibold mb-1">No subscriptions found</h6>
                    <p class="text-muted mb-0">Try adjusting your filters or check back after new activations.</p>
                </div>
            @endforelse
        </div>
    </div>
    @include('admin::components.pagination', ['paginator' => $subscriptions])
</div>

<style>
    .subscriptions-hero {
        margin: 0 1.5rem 1.5rem;
        border-radius: 28px;
        padding: 40px 44px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(20, 184, 166, 0.14));
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .subscriptions-hero__content {
        display: flex;
        flex-wrap: wrap;
        gap: 28px;
        align-items: flex-start;
    }
    .subscriptions-hero__intro {
        flex: 1 1 320px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .subscriptions-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    .subscriptions-hero__title {
        margin: 0;
        font-size: clamp(2rem, 2.8vw, 2.6rem);
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .subscriptions-hero__subtitle {
        margin: 0;
        max-width: 460px;
        line-height: 1.6;
        color: #475569;
    }
    .subscriptions-hero__stats {
        flex: 1 1 280px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
    }
    .subscriptions-stat-card {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 18px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .subscriptions-stat-card .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .subscriptions-stat-card .value {
        font-size: 1.7rem;
        font-weight: 700;
        color: #0f172a;
    }
    .subscriptions-stat-card .hint {
        font-size: 0.85rem;
        color: #64748b;
    }
    .subscriptions-filter {
        margin: 0 1.5rem 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 22px 52px rgba(15, 46, 122, 0.12);
        margin-bottom: 1.5rem;
    }
    .subscriptions-filter .card-body {
        padding: 26px 28px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .subscriptions-filter__form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
    }
    .subscriptions-filter__search {
        flex: 1 1 320px;
        background: #f8fafc;
        border-radius: 14px;
        padding: 4px;
    }
    .subscriptions-filter__search .input-group-text {
        border: none;
        background: transparent;
        color: #2563eb;
    }
    .subscriptions-filter__search .form-control {
        border: none;
        background: transparent;
        padding: 10px 12px;
    }
    .subscriptions-filter__selects {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        flex: 1 1 260px;
    }
    .subscriptions-filter__selects .form-floating {
        min-width: 200px;
        flex: 1;
    }
    .subscriptions-filter__actions {
        display: flex;
        gap: 12px;
        align-items: center;
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
        grid-template-columns: 10% 24% 18% 12% 12% 12% 12%;
        padding: 18px 26px;
        background: rgba(248, 250, 252, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
    .subscriptions-table__body {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 20px 26px 28px;
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.78), rgba(241, 245, 255, 0.65));
    }
    .subscriptions-table__row {
        display: grid;
        grid-template-columns: 10% 24% 18% 12% 12% 12% 12%;
        padding: 20px 26px;
        color: inherit;
        text-decoration: none;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
        transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .subscriptions-table__row:hover {
        border-color: rgba(59, 130, 246, 0.28);
        box-shadow: 0 22px 42px rgba(59, 130, 246, 0.12);
        transform: translateY(-3px);
    }
    .subscriptions-table__row .cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
    }
    .subscriptions-table__row .cell--id,
    .subscriptions-table__row .cell--subscriber,
    .subscriptions-table__row .cell--plan {
        align-items: flex-start;
    }
    .subscriptions-table__row .cell--status {
        justify-content: center;
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
    .subscriber-name {
        font-weight: 600;
        color: #0f172a;
    }
    .subscriber-meta,
    .plan-meta {
        font-size: 0.82rem;
        color: #94a3b8;
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
        color: #0f172a;
        background: rgba(148, 163, 184, 0.2);
        max-width: 160px;
        min-width: 0;
    }
    .status-pill--active { background: rgba(20, 184, 166, 0.18); color: #047857; }
    .status-pill--pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
    .status-pill--expired { background: rgba(148, 163, 184, 0.2); color: #4b5563; }
    .status-pill--cancelled { background: rgba(239, 68, 68, 0.2); color: #b91c1c; }
    .date {
        font-weight: 500;
        color: #475569;
    }
    .credits {
        font-weight: 600;
        color: #0f172a;
    }
    @media (max-width: 991px) {
        .subscriptions-table__head {
            display: none;
        }
        .subscriptions-table__body {
            padding: 18px 18px 24px;
            gap: 12px;
        }
        .subscriptions-table__row {
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 18px;
        }
        .subscriptions-table__row .cell {
            align-items: flex-start;
        }
    }
</style>
@endsection
