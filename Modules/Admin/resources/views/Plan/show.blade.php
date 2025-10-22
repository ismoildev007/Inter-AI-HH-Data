@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">{{ $plan->name }}</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
            <li class="breadcrumb-item">{{ $plan->name }}</li>
        </ul>
    </div>
    <!-- <div class="ms-auto">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
            <i class="feather-arrow-left me-1"></i> Back to Plans
        </a>
    </div> -->
</div>

@if (session('status'))
<div class="plan-notice alert alert-success d-flex align-items-center gap-2">
    <i class="feather-check-circle"></i> {{ session('status') }}
</div>
@endif

<div class="row g-3 mt-1">
    <div class="col-xxl-7 col-xl-8">
        <div class="card plan-overview">
            <div class="card-body">
                <div class="plan-overview__header">
                    <div>
                        <span class="badge bg-soft-primary text-primary text-uppercase fw-semibold">Plan Overview</span>
                        <h2 class="plan-title mt-3 mb-2">{{ $plan->name }}</h2>
                        <p class="text-muted mb-0">Centralized pricing block for the AI hiring assistant — crafted to convert premium users.</p>
                    </div>
                    <div class="plan-pricing">
                        <div class="plan-pricing__current">
                            <span class="label text-uppercase">Price</span>
                            <span class="value">{{ is_null($plan->price) ? '—' : number_format((float) $plan->price, 2, '.', ' ') }} <small class="currency">UZS</small></span>
                        </div>
                        <div class="plan-pricing__fake">
                            <span class="label text-uppercase">Fake price</span>
                            <span class="value">{{ is_null($plan->fake_price) ? '—' : number_format((float) $plan->fake_price, 2, '.', ' ') }} <small class="currency">UZS</small></span>
                        </div>
                    </div>
                </div>
                <div class="plan-grid">
                    <div class="plan-grid__item">
                        <span class="label">Auto response limit</span>
                        <span class="value">{{ number_format((int) $plan->auto_response_limit) }}</span>
                        <span class="hint">AI replies included in this plan.</span>
                    </div>
                    <div class="plan-grid__item">
                        <span class="label">Duration</span>
                        <span class="value">{{ $plan->duration ? $plan->duration->format('M d, Y') : 'No expiry' }}</span>
                        <span class="hint">Set for campaigns or seasonal offers.</span>
                    </div>
                    <div class="plan-grid__item">
                        <span class="label">Created</span>
                        <span class="value">{{ optional($plan->created_at)->format('M d, Y') ?? '—' }}</span>
                        <span class="hint">{{ optional($plan->created_at)->diffForHumans() }}</span>
                    </div>
                    <div class="plan-grid__item">
                        <span class="label">Last updated</span>
                        <span class="value">{{ optional($plan->updated_at)->format('M d, Y') ?? '—' }}</span>
                        <span class="hint">{{ optional($plan->updated_at)->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card plan-description mt-3">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0 text-uppercase text-muted fw-semibold">Narrative</h6>
            </div>
            <div class="card-body pt-3">
                @if ($plan->description)
                <div class="plan-description__content">
                    {!! nl2br(e($plan->description)) !!}
                </div>
                @else
                <div class="text-muted fst-italic">No description provided for this plan yet.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-5 col-xl-4">
        <div class="card plan-analytics">
            <div class="card-body">
                <h6 class="text-uppercase text-muted fw-semibold mb-3">At a Glance</h6>
                <ul class="list-unstyled m-0 plan-metrics">
                    <li>
                        <span>Total Subscribers</span>
                        <span>{{ number_format($subscriptionCount) }}</span>
                    </li>
                    <li>
                        <span>Monthly Revenue Potential</span>
                        <span>
                            @if(!is_null($revenuePotential))
                            {{ number_format($revenuePotential, 2, '.', ' ') }} UZS
                            @else
                            —
                            @endif
                        </span>
                    </li>
                    <li>
                        <span>Auto Response / Price</span>
                        <span>
                            @if(!is_null($autoResponsePerPrice))
                            {{ number_format($autoResponsePerPrice, 2) }}
                            @else
                            —
                            @endif
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .plan-notice {
        border-radius: 16px;
        border: 1px solid rgba(34, 197, 94, 0.35);
        background: rgba(220, 252, 231, 0.6);
        padding: 14px 18px;
        margin-bottom: 16px;
    }

    .plan-overview {
        border-radius: 26px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(148, 163, 184, 0.22);
    }

    .plan-overview__header {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }

    .plan-title {
        font-size: clamp(1.9rem, 2.4vw, 2.6rem);
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .plan-pricing {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        padding: 18px 20px;
        border-radius: 20px;
        background: linear-gradient(140deg, rgba(59, 130, 246, 0.08), rgba(37, 99, 235, 0.12));
        min-width: 220px;
    }

    .plan-pricing .label {
        color: #1d4ed8;
        font-size: 0.78rem;
        letter-spacing: 0.12em;
    }

    .plan-pricing .value {
        font-size: 1.65rem;
        font-weight: 600;
        color: #0f172a;
    }

    .plan-pricing .currency {
        font-size: 0.8rem;
        font-weight: 500;
        color: #1d4ed8;
    }

    .plan-grid {
        margin-top: 28px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 18px;
    }

    .plan-grid__item {
        background: #f8fafc;
        border-radius: 18px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .plan-grid__item .label {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }

    .plan-grid__item .value {
        display: block;
        font-size: 1.25rem;
        font-weight: 600;
        color: #0f172a;
        margin-top: 8px;
    }

    .plan-grid__item .hint {
        display: block;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .plan-description {
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .plan-description__content {
        font-size: 1rem;
        line-height: 1.7;
        color: #1f2937;
    }

.plan-analytics {
        border-radius: 18px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .plan-metrics li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px dashed rgba(148, 163, 184, 0.3);
        font-size: 0.94rem;
        color: #475569;
    }

    .plan-metrics li:last-child {
        border-bottom: none;
    }

    .plan-metrics span:last-child {
        font-weight: 600;
        color: #0f172a;
    }

    @media (max-width: 991px) {
        .plan-overview__header {
            flex-direction: column;
        }

        .plan-pricing {
            width: 100%;
        }
    }
</style>
@endsection
