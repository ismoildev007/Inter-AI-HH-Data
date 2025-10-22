@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Subscription #{{ $subscription->id }}</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
            <li class="breadcrumb-item">#{{ $subscription->id }}</li>
        </ul>
    </div>
    <div class="ms-auto">
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary">
            <i class="feather-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xxl-8">
        <div class="card subscription-summary">
            <div class="card-body">
                <div class="subscription-summary__header">
                    <div>
                        <span class="badge bg-soft-primary text-primary text-uppercase fw-semibold">Current cycle</span>
                        <h2 class="subscription-summary__title mt-3 mb-2">{{ $subscription->plan?->name ?? 'No plan' }}</h2>
                        <p class="subscription-summary__subtitle mb-0">
                            {{ ucfirst($subscription->status ?? 'unknown') }} •
                            Started {{ optional($subscription->starts_at)->format('M d, Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="subscription-summary__status">
                        <span class="status-pill status-pill--{{ $subscription->status ?? 'unknown' }}">
                            {{ ucfirst($subscription->status ?? 'unknown') }}
                        </span>
                    </div>
                </div>

                <div class="subscription-grid mt-4">
                    <div class="subscription-grid__item">
                        <span class="label">Subscriber</span>
                        <span class="value">{{ $subscription->user?->first_name }} {{ $subscription->user?->last_name }}</span>
                        <span class="hint">{{ $subscription->user?->email ?? 'User #'.$subscription->user_id }}</span>
                    </div>
                    <div class="subscription-grid__item">
                        <span class="label">Plan Credits</span>
                        <span class="value">
                            @if($subscription->plan)
                                {{ number_format($subscription->plan->auto_response_limit ?? 0) }}
                            @else
                                —
                            @endif
                        </span>
                        <span class="hint">Auto responses per cycle</span>
                    </div>
                    <div class="subscription-grid__item">
                        <span class="label">Remaining</span>
                        <span class="value">{{ number_format((int) $subscription->remaining_auto_responses) }}</span>
                        <span class="hint">Credits left for automation</span>
                    </div>
                    <div class="subscription-grid__item">
                        <span class="label">Ends</span>
                        <span class="value">{{ optional($subscription->ends_at)->format('M d, Y') ?? '—' }}</span>
                        <span class="hint">{{ optional($subscription->ends_at)->diffForHumans() }}</span>
                    </div>
                </div>

                <div class="subscription-timeline mt-4">
                    <h6 class="text-uppercase text-muted fw-semibold mb-3">Timeline</h6>
                    <div class="timeline-cards">
                        @foreach($timeline as $label => $value)
                            <div class="timeline-card">
                                <span class="timeline-label">{{ $label }}</span>
                                <span class="timeline-value">{{ $value ?: '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card subscription-transactions mt-3">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0 text-uppercase text-muted fw-semibold">Transactions</h6>
                    <p class="mb-0 text-muted small">Most recent charges and adjustments.</p>
                </div>
            </div>
            <div class="card-body">
                @if($subscription->transactions->isEmpty())
                    <div class="text-muted text-center py-4">
                        No payment events recorded for this subscription.
                    </div>
                @else
                    <div class="transactions-list">
                        @foreach($subscription->transactions as $transaction)
                            <div class="transactions-item">
                                <div class="transactions-item__meta">
                                    <span class="transactions-item__title">
                                        {{ $transaction->type ?? 'Charge' }}
                                    </span>
                                    <span class="transactions-item__date">
                                        {{ optional($transaction->created_at)->format('M d, Y • H:i') }}
                                    </span>
                                </div>
                                <div class="transactions-item__amount">
                                    {{ number_format((float) ($transaction->amount ?? 0), 2, '.', ' ') }} {{ strtoupper($transaction->currency ?? 'UZS') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card subscription-sidecard">
            <div class="card-body">
                <h6 class="text-uppercase text-muted fw-semibold mb-3">Utilization</h6>
                <div class="utilization-meter">
                    <div class="utilization-bar">
                        <div class="utilization-bar__fill" style="--progress: {{ max(0, min(1, $autoResponseUtilization ?? 0)) }};"></div>
                    </div>
                    <div class="utilization-meta">
                        <span class="utilization-percent">
                            {{ $autoResponseUtilization !== null ? number_format(($autoResponseUtilization) * 100, 1) . '%' : '—' }}
                        </span>
                        <span class="utilization-hint">Credits consumed</span>
                    </div>
                </div>

                <div class="sidecard-info mt-4">
                    <div class="sidecard-info__item">
                        <span class="label">Customer ID</span>
                        <span class="value">#{{ $subscription->user_id }}</span>
                    </div>
                    <div class="sidecard-info__item">
                        <span class="label">Plan ID</span>
                        <span class="value">#{{ $subscription->plan_id ?? '—' }}</span>
                    </div>
                    <div class="sidecard-info__item">
                        <span class="label">Subscription ID</span>
                        <span class="value">#{{ $subscription->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .subscription-summary {
        border-radius: 26px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08);
    }
    .subscription-summary__header {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .subscription-summary__title {
        font-size: clamp(1.8rem, 2.2vw, 2.2rem);
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .subscription-summary__subtitle {
        color: #475569;
        font-size: 0.95rem;
    }
    .subscription-summary__status {
        display: flex;
        align-items: flex-start;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 999px;
        text-transform: uppercase;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        color: #0f172a;
        background: rgba(148, 163, 184, 0.2);
    }
    .status-pill--active { background: rgba(20, 184, 166, 0.18); color: #047857; }
    .status-pill--pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
    .status-pill--expired { background: rgba(148, 163, 184, 0.25); color: #4b5563; }
    .status-pill--cancelled { background: rgba(239, 68, 68, 0.2); color: #b91c1c; }

    .subscription-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }
    .subscription-grid__item {
        background: #f8fafc;
        border-radius: 18px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .subscription-grid__item .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .subscription-grid__item .value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #0f172a;
    }
    .subscription-grid__item .hint {
        font-size: 0.85rem;
        color: #94a3b8;
    }

    .subscription-timeline .timeline-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .timeline-card {
        flex: 1 1 160px;
        background: #f1f5ff;
        border-radius: 14px;
        padding: 14px 16px;
        border: 1px solid rgba(99, 102, 241, 0.18);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .timeline-label {
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.14em;
        color: #4338ca;
    }
    .timeline-value {
        font-weight: 600;
        font-size: 1rem;
        color: #1f2937;
    }

    .subscription-transactions {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.85);
        box-shadow: 0 22px 52px rgba(15, 23, 42, 0.08);
    }
    .transactions-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .transactions-item {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px dashed rgba(203, 213, 225, 0.7);
    }
    .transactions-item:last-child {
        border-bottom: none;
    }
    .transactions-item__title {
        font-weight: 600;
        color: #0f172a;
    }
    .transactions-item__date {
        font-size: 0.82rem;
        color: #94a3b8;
    }
    .transactions-item__amount {
        font-weight: 600;
        color: #2563eb;
        white-space: nowrap;
    }

    .subscription-sidecard {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
    }
    .utilization-meter {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .utilization-bar {
        position: relative;
        width: 100%;
        height: 12px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }
    .utilization-bar__fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: calc(var(--progress, 0) * 100%);
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        border-radius: inherit;
        transition: width 0.3s ease;
    }
    .utilization-meta {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }
    .utilization-percent {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1d4ed8;
    }
    .utilization-hint {
        font-size: 0.82rem;
        color: #94a3b8;
    }
    .sidecard-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .sidecard-info__item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #475569;
    }
    .sidecard-info__item .label {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 0.75rem;
        color: #94a3b8;
    }
    .sidecard-info__item .value {
        font-weight: 600;
        color: #0f172a;
    }
    @media (max-width: 991px) {
        .subscription-summary__header {
            flex-direction: column;
        }
        .subscription-summary__status {
            justify-content: flex-start;
        }
    }
</style>
@endsection
