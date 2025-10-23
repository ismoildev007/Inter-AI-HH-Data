@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Plans</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Plans</li>
        </ul>
    </div>

</div>

@if (session('status'))
    <div class="plan-alert alert alert-success d-flex align-items-center gap-2">
        <i class="feather-check-circle"></i>{{ session('status') }}
    </div>
@endif

<div class="plan-hero">
    <div class="plan-hero__content">
        <div class="plan-hero__left">
            <span class="plan-hero__badge">
                <i class="feather-layers"></i>
                Monetization Suite
            </span>
            <h1 class="plan-hero__title">Manage subscription blueprints</h1>
            <p class="plan-hero__subtitle">
                Calibrate pricing strategy, limits and messaging for every package your AI assistant offers. All updates sync instantly to the checkout experience.
            </p>
        </div>
        <div class="plan-hero__stats">
            <div class="plan-stat-card">
                <span class="label">Total plans</span>
                <span class="value">{{ number_format($totalPlans) }}</span>
                <span class="hint">Ready for checkout</span>
            </div>
            <div class="plan-stat-card">
                <span class="label">Average price</span>
                <span class="value">
                    {{ is_null($averagePrice) ? '—' : number_format($averagePrice, 2, '.', ' ') }}
                    <small>{{ is_null($averagePrice) ? '' : 'UZS' }}</small>
                </span>
                <span class="hint">Across billable plans</span>
            </div>
            <div class="plan-stat-card">
                <span class="label">Highest auto responses</span>
                <span class="value">{{ is_null($maxAutoResponses) ? '—' : number_format($maxAutoResponses) }}</span>
                <span class="hint">AI replies bundled</span>
            </div>
        </div>
    </div>
</div>

<div class="plan-filter-card card">
    <div class="card-body">
        <div class="plan-filter-header">
            <div>
                <h6 class="mb-1 fw-semibold">Filter library</h6>
                <p class="text-muted mb-0 small">Search by plan name, highlights or numeric values.</p>
            </div>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-soft-primary">
                <i class="feather-sparkles me-1"></i> Launch new plan
            </a>
        </div>
        <form action="{{ route('admin.plans.index') }}" method="GET" class="plan-search-form">
            <div class="input-group">
                <span class="input-group-text"><i class="feather-search"></i></span>
                <input type="text"
                       name="q"
                       class="form-control"
                       value="{{ $search }}"
                       placeholder="Search plan name, description or price...">
            </div>
            @if($search !== '')
                <a class="plan-search-clear" href="{{ route('admin.plans.index') }}">
                    <i class="feather-x-circle me-1"></i>Clear
                </a>
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="feather-filter me-1"></i> Apply
            </button>
        </form>
    </div>
</div>
<div class="plan-header-actions d-flex justify-content-end">
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
        <i class="feather-plus me-1"></i>Create New Plan
    </a>
</div>
<div class="plan-table card">
    <div class="card-body p-0">
        <div class="plan-table__head">
            <div class="plan-table__cell">Plan</div>
            <div class="plan-table__cell">Price</div>
            <div class="plan-table__cell">Auto responses</div>
            <div class="plan-table__cell">Duration</div>
            <div class="plan-table__cell text-end">Created</div>
            <div class="plan-table__cell text-end">Actions</div>
        </div>
        <div class="plan-table__body">
            @php
                $formatPrice = fn ($value) => is_null($value) ? '—' : number_format((float) $value, 2, '.', ' ');
                $rowStart = method_exists($plans, 'firstItem') ? ($plans->firstItem() ?? 1) : 1;
            @endphp
            @forelse($plans as $plan)
                @php $rowNumber = $rowStart + $loop->index; @endphp
                <div class="plan-table__row" data-href="{{ route('admin.plans.show', $plan) }}">
                    <div class="plan-table__cell plan-table__cell--primary">
                        <span class="table-id-pill">{{ $rowNumber }}</span>
                        <div class="plan-table__primary">
                            <span class="plan-table__title">
                                {{ $plan->name }}
                            </span>
                            <!-- <span class="plan-table__meta">Plan ID #{{ $plan->id }}</span> -->
                            <span class="plan-table__description">{{ \Illuminate\Support\Str::limit($plan->description, 80) ?: 'No description provided' }}</span>
                        </div>
                    </div>
                    <div class="plan-table__cell">
                        <div class="plan-price">
                            <span class="current">{{ $formatPrice($plan->price) }} <small>UZS</small></span>
                            @if(!is_null($plan->fake_price) && $plan->fake_price > 0)
                                <span class="fake">{{ $formatPrice($plan->fake_price) }} UZS</span>
                            @endif
                        </div>
                    </div>
                    <div class="plan-table__cell">
                        <span class="plan-count">{{ number_format((int) $plan->auto_response_limit) }}</span>
                        <span class="plan-count-hint">AI replies</span>
                    </div>
                    <div class="plan-table__cell">
                        <span class="plan-date">{{ $plan->duration ? $plan->duration->format('M d, Y') : 'No expiry' }}</span>
                    </div>
                    <div class="plan-table__cell text-end">
                        <span class="plan-date">{{ optional($plan->created_at)->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="plan-table__cell text-end">
                        <div class="plan-table__actions">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-outline-primary plan-action-btn">
                            <i class="feather-edit-3 me-1"></i>
                           
                        </a>
                        <form action="{{ route('admin.plans.destroy', $plan) }}"
                              method="POST"
                              onsubmit="return confirm('Delete this plan? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger plan-action-btn">
                                <i class="feather-trash-2 me-1"></i>
                              
                            </button>
                        </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="plan-table__empty text-center py-5">
                    <h6 class="fw-semibold mb-1">No plans yet</h6>
                    <p class="text-muted mb-3">Kickstart monetization by creating your first plan.</p>
                    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">
                        <i class="feather-plus me-1"></i> Create Plan
                    </a>
                </div>
            @endforelse
        </div>
    </div>
    @include('admin::components.pagination', ['paginator' => $plans])
</div>

<style>
.plan-alert {
    border-radius: 16px;
    border: 1px solid rgba(34, 197, 94, 0.35);
    background: rgba(220, 252, 231, 0.6);
    padding: 14px 18px;
    margin: 1.5rem 1.5rem;
}
.plan-hero {
    margin: 1.5rem 1.5rem 1.5rem;
    border-radius: 28px;
    padding: 42px 48px;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(14, 165, 233, 0.12));
        border: 1px solid rgba(59, 130, 246, 0.16);
        box-shadow: 0 26px 58px rgba(15, 23, 42, 0.08);
    }
.plan-hero__content {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    align-items: flex-start;
    padding: 16px;
}
    .plan-hero__left {
        flex: 1 1 320px;
    }
    .plan-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 18px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
        font-size: 0.78rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }
    .plan-hero__title {
        margin: 18px 0 12px;
        font-size: clamp(2.1rem, 2.9vw, 2.8rem);
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #0f172a;
    }
    .plan-hero__subtitle {
        margin: 0;
        max-width: 520px;
        color: #475569;
        line-height: 1.6;
    }
.plan-hero__stats {
    flex: 1 1 280px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}
    .plan-stat-card {
        background: rgba(255, 255, 255, 0.85);
        border-radius: 20px;
        padding: 20px 22px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }
    .plan-stat-card .label {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
    .plan-stat-card .value {
        display: block;
        margin-top: 8px;
        font-size: 1.7rem;
        font-weight: 700;
        color: #0f172a;
    }
    .plan-stat-card .value small {
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 4px;
        color: #2563eb;
    }
    .plan-stat-card .hint {
        display: block;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 0.85rem;
    }
.plan-filter-card {
    margin: 1.5rem 1.5rem 1.5rem;
    border-radius: 22px;
        margin-bottom: 1.5rem;
        box-shadow: 0 20px 46px rgba(15, 46, 122, 0.12);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }
    .plan-filter-card .card-body {
        padding: 26px 32px;
    }
    .plan-filter-header {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }
    .plan-search-form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
    }
    .plan-search-form .input-group {
        flex: 1 1 320px;
        border-radius: 16px;
        background: #f1f5ff;
        padding: 4px;
    }
    .plan-search-form .input-group-text {
        border: none;
        background: transparent;
        color: #2563eb;
    }
    .plan-search-form .form-control {
        border: none;
        background: transparent;
        padding: 12px 16px;
    }
    .plan-search-form .form-control:focus {
        box-shadow: none;
    }
    .plan-search-clear {
        color: #8a96b8;
        text-decoration: none;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
    }
.plan-table {
    margin: 1.5rem 1.5rem 1.5rem;
    border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }
    .plan-table__head {
        display: grid;
        grid-template-columns: 25% 18% 15% 15% 12% 15%;
        padding: 18px 26px;
        background: rgba(248, 250, 252, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
    }
.plan-table__head .plan-table__cell.text-end {
    padding-right: 20px;
    }
.plan-table__cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.plan-table__cell--primary {
    flex-direction: row;
    align-items: center;
    gap: 16px;
}
.plan-table__primary {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.plan-table__meta {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #94a3b8;
}
.table-id-pill {
    min-width: 48px;
    height: 48px;
    border-radius: 16px;
    background: linear-gradient(135deg, #eff3ff, #dce5ff);
    color: #1f2f7a;
    font-weight: 600;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 24px rgba(31, 51, 126, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.85);
}
.plan-table__body {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 20px 26px 28px;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.75), rgba(241, 245, 255, 0.65));
}
.plan-header-actions {
    margin: 0 1.5rem 1rem;
}
    .plan-table__row {
        display: grid;
        grid-template-columns: 25% 18% 15% 15% 12% 15%;
        padding: 20px 26px;
        text-decoration: none;
        color: inherit;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
        transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        align-items: center;
        cursor: pointer;
    }
    .plan-table__row:hover {
        border-color: rgba(59, 130, 246, 0.28);
        box-shadow: 0 22px 42px rgba(59, 130, 246, 0.12);
        transform: translateY(-3px);
    }
    .plan-table__title {
        font-weight: 600;
        font-size: 1.02rem;
        color: #0f172a;
        text-decoration: none;
    }
    .plan-table__description {
        font-size: 0.86rem;
        color: #64748b;
    }
    .plan-price .current {
        font-weight: 600;
        font-size: 1rem;
        color: #0f172a;
    }
    .plan-price .fake {
        font-size: 0.82rem;
        color: #ef4444;
        text-decoration: line-through;
    }
.plan-table__actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    align-items: center;
    padding-right: 20px;
    cursor: default;
}
.plan-table__actions form {
    margin: 0;
}
    .plan-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 12px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .plan-count {
        font-weight: 600;
        color: #2563eb;
    }
    .plan-count-hint {
        font-size: 0.78rem;
        color: #94a3b8;
    }
    .plan-date {
        font-size: 0.9rem;
        color: #475569;
    }
    .plan-table__empty {
        padding: 36px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }
    @media (max-width: 991px) {
        .plan-alert,
        .plan-hero,
        .plan-filter-card,
        .plan-table {
            margin: 1.5rem 1rem;
        }
        .plan-table__head {
            display: none;
        }
        .plan-table__body {
            padding: 18px 18px 24px;
            gap: 12px;
        }
        .plan-table__row {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 18px;
        }
        .plan-table__cell {
            align-items: flex-start;
        }
        .plan-table__cell--primary {
            flex-direction: column;
        }
        .plan-table__actions {
            justify-content: flex-start;
        }
        .plan-price, .plan-count {
            font-size: 1.05rem;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.plan-table__row[data-href]').forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('.plan-table__actions')) {
                    return;
                }
                var href = row.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            });
        });
    });
</script>
@endsection
