@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Transaction #{{ $transaction->id }}</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.transactions.index') }}">Transactions</a></li>
            <li class="breadcrumb-item">#{{ $transaction->id }}</li>
        </ul>
    </div>

</div>

<div class="transaction-layout">
<div class="row g-3">
    <div class="col-xxl-8">
        <div class="card transaction-summary">
            <div class="card-body">
                <div class="transaction-summary__header">
                    <div>
                        <span class="badge bg-soft-primary text-primary text-uppercase fw-semibold">Payment overview</span>
                        <!-- <h2 class="transaction-summary__title mt-3 mb-2">{{ $transaction->transaction_id ?? 'Internal ID #'.$transaction->id }}</h2> -->
                        @php
                            $raw = strtolower($transaction->payment_status ?? '');
                            $norm = in_array($raw, ['active','success']) ? 'active'
                                : ($raw === 'pending' ? 'pending'
                                : ($raw === 'cancelled' ? 'cancelled' : 'expired'));
                        @endphp
                        <p class="transaction-summary__subtitle mb-0">
                            {{ ucfirst($norm) }} • {{ optional($transaction->create_time)->format('M d, Y • H:i') ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="transaction-summary__amount">
                        <span class="amount">{{ number_format((float) $transaction->amount, 2, '.', ' ') }} {{ strtoupper($transaction->currency ?? 'UZS') }}</span>
                        <span class="method">{{ ucfirst($transaction->payment_method ?? '—') }}</span>
                    </div>
                </div>

                @php
                    $subscription = $transaction->subscription;
                    $plan = $subscription?->plan;
                @endphp
                <div class="transaction-grid mt-4">
                    <div class="transaction-grid__item">
                        <span class="label">Customer</span>
                        <span class="value">{{ trim(($transaction->user->first_name ?? '').' '.($transaction->user->last_name ?? '')) ?: 'User #'.$transaction->user_id }}</span>
                        <span class="hint">{{ $transaction->user?->email ?? '—' }}</span>
                    </div>
                    <div class="transaction-grid__item">
                        <span class="label">Plan</span>
                        <span class="value">{{ $plan?->name ?? '—' }}</span>
                        <span class="hint">
                            @if($plan)
                                Plan ID #{{ $plan->id }}
                            @elseif($transaction->plan_id)
                                Plan ID #{{ $transaction->plan_id }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="transaction-grid__item">
                        <span class="label">Subscription</span>
                        <span class="value">
                            @if($subscription)
                                {{ $subscription->id }} ({{ ucfirst($subscription->status ?? 'unknown') }})
                            @else
                                —
                            @endif
                        </span>
                        <span class="hint">Linked subscription record</span>
                    </div>
                    <div class="transaction-grid__item">
                        <span class="label">State</span>
                        <span class="value">{{ $transaction->state ?? '—' }}</span>
                        <span class="hint">Processor status code</span>
                    </div>
                </div>

                <div class="transaction-timeline mt-4">
                    <h6 class="text-uppercase text-muted fw-semibold mb-3">Lifecycle</h6>
                    <div class="timeline-cards">
                        @foreach($timeline as $item)
                            <div class="timeline-card">
                                <span class="timeline-label">{{ $item['label'] }}</span>
                                <span class="timeline-value">{{ $item['value'] ?? '—' }}</span>
                                <span class="timeline-hint">{{ $item['subtitle'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card transaction-reason mt-3">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 text-uppercase text-muted fw-semibold">Notes &amp; Reason</h6>
            </div>
            <div class="card-body">
                @if($transaction->reason)
                    <div class="transaction-reason__content">
                        {!! nl2br(e($transaction->reason)) !!}
                    </div>
                @else
                    <div class="text-muted">No additional notes recorded for this transaction.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card transaction-sidecard">
            <div class="card-body">
                @php
                    $subscription = $transaction->subscription;
                    $plan = $subscription?->plan;
                @endphp
                <h6 class="text-uppercase text-muted fw-semibold mb-3">Identifiers</h6>
                <div class="sidecard-info">
                    <div class="sidecard-info__item">
                        <span class="label">Transaction ID</span>
                        <span class="value">#{{ $transaction->id }}</span>
                    </div>
                    <!-- <div class="sidecard-info__item">
                        <span class="label">Gateway ID</span>
                        <span class="value">{{ $transaction->transaction_id ?? '—' }}</span>
                    </div> -->
                    <div class="sidecard-info__item">
                        <span class="label">Customer</span>
                        <span class="value">#{{ $transaction->user_id ?? '—' }}</span>
                    </div>
                    <div class="sidecard-info__item">
                        <span class="label">Plan</span>
                        <span class="value">
                            @if(isset($plan) && $plan)
                                #{{ $plan->id }}
                            @elseif($transaction->plan_id)
                                #{{ $transaction->plan_id }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="sidecard-info__item">
                        <span class="label">Subscription</span>
                        <span class="value">#{{ $transaction->subscription_id ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
    .transaction-layout {
        margin: 1.5rem 1.5rem 1.5rem;
    }
    .transaction-summary {
        border-radius: 26px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08);
    }
    .transaction-summary__header {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 24px;
    }
    .transaction-summary__title {
        margin: 0;
        font-size: clamp(1.8rem, 2.3vw, 2.2rem);
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .transaction-summary__subtitle {
        color: #475569;
        font-size: 0.96rem;
    }
    .transaction-summary__amount {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
    }
    .transaction-summary__amount .amount {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1d4ed8;
    }
    .transaction-summary__amount .method {
        font-size: 0.85rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    .transaction-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }
    .transaction-grid__item {
        background: #f8fafc;
        border-radius: 18px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .transaction-grid__item .label {
        font-size: 0.75rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .transaction-grid__item .value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0f172a;
    }
    .transaction-grid__item .hint {
        font-size: 0.84rem;
        color: #94a3b8;
    }

    .transaction-timeline .timeline-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .timeline-card {
        flex: 1 1 180px;
        background: #eef2ff;
        border-radius: 16px;
        padding: 16px 18px;
        border: 1px solid rgba(79, 70, 229, 0.18);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .timeline-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: #4338ca;
    }
    .timeline-value {
        font-weight: 600;
        color: #0f172a;
    }
    .timeline-hint {
        font-size: 0.82rem;
        color: #6b7280;
    }

    .transaction-reason {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
    }
    .transaction-reason__content {
        font-size: 0.95rem;
        line-height: 1.6;
        color: #1f2937;
    }

    .transaction-sidecard {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
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
        font-size: 0.74rem;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .sidecard-info__item .value {
        font-weight: 600;
        color: #0f172a;
    }
    @media (max-width: 991px) {
        .transaction-layout {
            margin: 1.5rem 1rem;
        }
        .transaction-summary__header {
            flex-direction: column;
            align-items: flex-start;
        }
        .transaction-summary__amount {
            align-items: flex-start;
        }
    }
</style>
@endsection
