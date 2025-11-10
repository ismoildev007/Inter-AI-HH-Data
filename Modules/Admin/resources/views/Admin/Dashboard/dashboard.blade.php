@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Dashboard</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Dashboard</li>
        </ul>
    </div>
</div>

@php
    $summaryCards = [
        [
            'label' => 'Users',
            'value' => number_format($usersCount),
            'href' => route('admin.users.index'),
            'icon' => 'users',
            'accent' => '#2563eb',
            'delta' => $miniTotals['users'] ?? null,
        ],
        [
            'label' => 'Resumes',
            'value' => number_format($resumesCount),
            'href' => route('admin.resumes.index'),
            'icon' => 'file-text',
            'accent' => '#0f766e',
            'delta' => $miniTotals['resumes'] ?? null,
        ],
        [
            'label' => 'Applications',
            'value' => number_format($applicationsCount),
            'href' => route('admin.applications.index'),
            'icon' => 'briefcase',
            'accent' => '#7c3aed',
            'delta' => $miniTotals['applications'] ?? null,
        ],
        [
            'label' => 'Telegram Channels',
            'value' => number_format($telegramChannelsCount),
            'href' => route('admin.telegram_channels.index'),
            'icon' => 'send',
            'accent' => '#f97316',
            'delta' => null,
        ],
    ];
@endphp

<div class="dashboard-scene">
    <div class="dashboard-hero">
        <div class="dashboard-hero__meta">
            <span class="dashboard-hero__badge">
                <i class="feather-activity"></i>
                Realtime view
            </span>
            <h1 class="dashboard-hero__title">Platform statistics</h1>
            <h1 class="dashboard-hero__title">{{ __('elnurbek') }}</h1>
            <p class="dashboard-hero__subtitle">
                Stay on top of user activity, hiring momentum, and channel performance — all data refreshes live from the backend.
            </p>
        </div>
        <div class="dashboard-hero__glance">
            <div class="dashboard-hero__glance-item">
                <span class="label">Active visitors</span>
                <span class="value">{{ $analyticsData['liveVisitors'] ?? '📈' }}</span>
            </div>
            <div class="dashboard-hero__glance-item">
                <span class="label">Returning rate</span>
                <span class="value">{{ $analyticsData['returningRate'] ?? '📉' }}</span>
            </div>

        </div>
    </div>

    <div class="dashboard-summary-grid">
        @foreach($summaryCards as $card)
            <a class="dashboard-summary-card" href="{{ $card['href'] }}">
                <span class="dashboard-summary-card__icon" style="--summary-accent: {{ $card['accent'] }}">
                    <i class="feather-{{ $card['icon'] }}"></i>
                </span>
                <span class="dashboard-summary-card__meta">
                    <span class="dashboard-summary-card__label">{{ $card['label'] }}</span>
                    <span class="dashboard-summary-card__value">{{ $card['value'] }}</span>
                </span>
                @if(!is_null($card['delta']))
                    <span class="dashboard-summary-card__delta">
                        <i class="feather-trending-up"></i>
                        {{ number_format($card['delta']) }}
                        <small>last 7 days</small>
                    </span>
                @endif
            </a>
        @endforeach
    </div>


    <div class="dashboard-section">
        <div class="dashboard-section__body">
            <div class="row g-3">
                <div class="col-xxl-4">
                    <div class="dashboard-card dashboard-card--list">
                        <div class="dashboard-card__header">
                            <h3>Top Visitors</h3>
                            <a class="link" href="{{ route('admin.visits.top_users') }}">View all</a>
                        </div>
                        <div class="dashboard-card__body dashboard-card__body--scroll">
                            @forelse($topUsers as $u)
                                <div class="dashboard-list-item">
                                    <span class="avatar">
                                        <i class="feather-user"></i>
                                    </span>
                                    <div class="info">
                                        <span class="name">{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'User #'.$u->id) }}</span>
                                        <span class="description">{{ $u->email }}</span>
                                    </div>
                                    <span class="value">{{ number_format($u->visits_count) }}</span>
                                </div>
                            @empty
                                <div class="dashboard-empty">No visitor data yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="dashboard-card dashboard-card--list">
                        <div class="dashboard-card__header">
                            <h3>Vacancies by Category</h3>
                            @isset($vacanciesTotal)
                                <span class="badge total">Total {{ number_format($vacanciesTotal) }}</span>
                            @endisset
                            <a class="link" href="{{ route('admin.vacancies.categories') }}">View All</a>
                        </div>
                        <div class="dashboard-card__body dashboard-card__body--scroll">
                            @if(!empty($vacancyCategories) && count($vacancyCategories))
                                @foreach($vacancyCategories as $row)
                                    <div class="dashboard-list-item">
                                        <div class="info">
                                            <span class="name text-capitalize">{{ $row->category ?: 'other' }}</span>
                                        </div>
                                        <span class="value">{{ number_format($row->c) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="dashboard-empty">No vacancy data yet</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="dashboard-card">
                        <div class="dashboard-card__header">
                            <h3>Social radar</h3>
                            <span class="badge timeframe">Channel blend</span>
                        </div>
                        <div class="dashboard-card__body chart-lg" id="social-radar-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="dashboard-section">
        <div class="dashboard-section__body">
            <div class="dashboard-section__title">
                <h2>Engagement funnels</h2>
                <span class="hint"> traffic trends &amp; conversion snapshots </span>
            </div>
            <div class="row g-3">
                <div class="col-xxl-8">
                    <div class="dashboard-card">
                        <div class="dashboard-card__header">
                            <div>
                                <h3>Visitors Overview</h3>
                                <p>Weekly distribution of total visits vs conversions</p>
                            </div>
                            <span class="badge timeframe">Last 12 month</span>
                        </div>
                        <div class="dashboard-card__body chart-lg" id="visitors-overview-chart"></div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="dashboard-stack">
                        <div class="dashboard-card dashboard-card--compact">
                            <div class="dashboard-card__header">
                                <h3>Users (7d)</h3>
                                <span class="metric">{{ number_format($miniTotals['users'] ?? 0) }}</span>
                            </div>
                            <div class="dashboard-card__body chart-sm" id="task-completed-area-chart"></div>
                        </div>
                        <div class="dashboard-card dashboard-card--compact">
                            <div class="dashboard-card__header">
                                <h3>Applications (7d)</h3>
                                <span class="metric">{{ number_format($miniTotals['applications'] ?? 0) }}</span>
                            </div>
                            <div class="dashboard-card__body chart-sm" id="new-tasks-area-chart"></div>
                        </div>
                        <div class="dashboard-card dashboard-card--compact">
                            <div class="dashboard-card__header">
                                <h3>Resumes (7d)</h3>
                                <span class="metric">{{ number_format($miniTotals['resumes'] ?? 0) }}</span>
                            </div>
                            <div class="dashboard-card__body chart-sm" id="project-done-area-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="dashboard-section__body">
            <div class="dashboard-section__title">
                <h2>Acquisition rhythm</h2>
                <span class="hint"> granular cadence by time horizon </span>
            </div>
            <div class="row g-3">
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header>
                            <span>Hourly Visitors</span>
                        </header>
                        <div class="dashboard-tile__body" id="bounce-rate"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header>
                            <span>Daily Visitors</span>
                        </header>
                        <div class="dashboard-tile__body" id="page-views"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header>
                            <span>Monthly Visitors</span>
                        </header>
                        <div class="dashboard-tile__body" id="site-impressions"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header>
                            <span>Yearly Visitors</span>
                        </header>
                        <div class="dashboard-tile__body" id="conversions-rate"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="dashboard-section__body">
            <div class="dashboard-section__title">
                <h2>Vacancy throughput</h2>
                <span class="hint"> watch sourcing flow across channels </span>
            </div>
            <div class="row g-3">
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header><span>Hourly Vacancies</span></header>
                        <div class="dashboard-tile__body" id="vacancy-hourly"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header><span>Daily Vacancies</span></header>
                        <div class="dashboard-tile__body" id="vacancy-daily"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header><span>Weekly Vacancies</span></header>
                        <div class="dashboard-tile__body" id="vacancy-weekly"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="dashboard-tile">
                        <header><span>Monthly Vacancies</span></header>
                        <div class="dashboard-tile__body" id="vacancy-monthly"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <div class="dashboard-section">
        <div class="dashboard-section__body">
            <div class="row g-3">
                <div class="col-xxl-4">
                    <div class="dashboard-card dashboard-card--list">
                        <div class="dashboard-card__header">
                            <h3>Top Visitors</h3>
                            <a class="link" href="{{ route('admin.visits.top_users') }}">View all</a>
                        </div>
                        <div class="dashboard-card__body dashboard-card__body--scroll">
                            @forelse($topUsers as $u)
                                <div class="dashboard-list-item">
                                    <span class="avatar">
                                        <i class="feather-user"></i>
                                    </span>
                                    <div class="info">
                                        <span class="name">{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'User #'.$u->id) }}</span>
                                        <span class="description">{{ $u->email }}</span>
                                    </div>
                                    <span class="value">{{ number_format($u->visits_count) }}</span>
                                </div>
                            @empty
                                <div class="dashboard-empty">No visitor data yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="dashboard-card dashboard-card--list">
                        <div class="dashboard-card__header">
                            <h3>Vacancies by Category</h3>
                            @isset($vacanciesTotal)
                                <span class="badge total">Total {{ number_format($vacanciesTotal) }}</span>
                            @endisset
                            <a class="link" href="{{ route('admin.vacancies.categories') }}">View All</a>
                        </div>
                        <div class="dashboard-card__body dashboard-card__body--scroll">
                            @if(!empty($vacancyCategories) && count($vacancyCategories))
                                @foreach($vacancyCategories as $row)
                                    <div class="dashboard-list-item">
                                        <div class="info">
                                            <span class="name text-capitalize">{{ $row->category ?: 'other' }}</span>
                                        </div>
                                        <span class="value">{{ number_format($row->c) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="dashboard-empty">No vacancy data yet</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="dashboard-card">
                        <div class="dashboard-card__header">
                            <h3>Social radar</h3>
                            <span class="badge timeframe">Channel blend</span>
                        </div>
                        <div class="dashboard-card__body chart-lg" id="social-radar-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
</div>

<style>
    .dashboard-scene {
        display: flex;
        flex-direction: column;
        gap: 28px;
        margin: 1.5rem 1.5rem 1.5rem;
    }
    .dashboard-hero {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
        padding: 32px;
        border-radius: 28px;
        background: #ffffff;
        color: #0f172a;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
    }
    .dashboard-hero::after {
        content: '';
        position: absolute;
        inset: -20% 10% auto 35%;
        height: 160%;
        width: 60%;
        background: radial-gradient(circle at center, rgba(59, 130, 246, 0.15), transparent 70%);
        opacity: 0.85;
        pointer-events: none;
    }
    .dashboard-hero__meta {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .dashboard-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.82rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .dashboard-hero__title {
        margin: 0;
        font-size: clamp(2rem, 3vw, 2.6rem);
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .dashboard-hero__subtitle {
        margin: 0;
        max-width: 520px;
        color: #475569;
        line-height: 1.6;
    }
    .dashboard-hero__glance {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
        align-items: end;
    }
    .dashboard-hero__glance-item {
        padding: 16px 18px;
        border-radius: 18px;
        background: #f8fafc;
        backdrop-filter: blur(4px);
    }
    .dashboard-hero__glance-item .label {
        display: block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #94a3b8;
    }
    .dashboard-hero__glance-item .value {
        display: block;
        margin-top: 8px;
        font-size: 1.65rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dashboard-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }
    .dashboard-summary-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 22px;
        border-radius: 20px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .dashboard-summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 55px rgba(15, 23, 42, 0.1);
    }
    .dashboard-summary-card__icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--summary-accent) 12%, #eff6ff);
        color: var(--summary-accent);
        font-size: 1.35rem;
    }
    .dashboard-summary-card__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .dashboard-summary-card__label {
        font-size: 0.88rem;
        font-weight: 600;
        color: #475569;
    }
    .dashboard-summary-card__value {
        font-size: 1.9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .dashboard-summary-card__delta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        color: #0f766e;
    }
    .dashboard-summary-card__delta small {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
    }
    .dashboard-section {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.05);
    }
    .dashboard-section__body {
        padding: 26px 28px;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .dashboard-section__title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .dashboard-section__title h2 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dashboard-section__title .hint {
        font-size: 0.82rem;
        color: #94a3b8;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    .dashboard-card {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 20px;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        height: 100%;
    }
    .dashboard-card--compact {
        gap: 10px;
        padding: 18px;
    }
    .dashboard-card--list {
        background: #ffffff;
    }
    .dashboard-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .dashboard-card__header h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dashboard-card__header p {
        margin: 0;
        font-size: 0.82rem;
        color: #94a3b8;
    }
    .dashboard-card__header .metric {
        font-weight: 600;
        font-size: 1rem;
        color: #2563eb;
    }
    .dashboard-card__body {
        flex: 1;
        border-radius: 16px;
        background: #ffffff;
        border: 1px dashed rgba(148, 163, 184, 0.35);
        padding: 12px;
    }
    .dashboard-card__body.chart-lg {
        min-height: 320px;
    }
    .dashboard-card__body.chart-sm {
        min-height: 120px;
    }
    .dashboard-card__body--scroll {
        max-height: 280px;
        overflow-y: auto;
        padding: 0;
        border: none;
        background: transparent;
    }
    .dashboard-card .badge.timeframe {
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 999px;
        font-size: 0.75rem;
        padding: 4px 10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .dashboard-card .badge.total {
        background: #eef2ff;
        color: #4338ca;
        border-radius: 10px;
        padding: 4px 8px;
        font-size: 0.75rem;
    }
    .dashboard-card .link {
        font-size: 0.82rem;
        text-decoration: none;
        color: #2563eb;
    }
    .dashboard-stack {
        display: flex;
        flex-direction: column;
        gap: 14px;
        height: 100%;
    }
    .dashboard-tile {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 18px;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        height: 100%;
    }
    .dashboard-tile header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        color: #475569;
    }
    .dashboard-tile__body {
        flex: 1;
        min-height: 140px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px dashed rgba(148, 163, 184, 0.35);
        padding: 12px;
    }
    .dashboard-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 4px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dashboard-list-item:last-child {
        border-bottom: none;
    }
    .dashboard-list-item .avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: #eff6ff;
        color: #2563eb;
        display: grid;
        place-items: center;
    }
    .dashboard-list-item .info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .dashboard-list-item .name {
        font-weight: 600;
        color: #0f172a;
    }
    .dashboard-list-item .description {
        font-size: 0.78rem;
        color: #94a3b8;
    }
    .dashboard-list-item .value {
        font-weight: 600;
        color: #1d4ed8;
    }
    .dashboard-empty {
        padding: 32px 0;
        text-align: center;
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .dashboard-card__body .apexcharts-canvas {
        width: 100% !important;
        height: 100% !important;
    }
    .dashboard-card__body .apexcharts-svg {
        filter: drop-shadow(0 22px 34px rgba(37, 99, 235, 0.08));
    }
    .dashboard-card__body .apexcharts-tooltip {
        border-radius: 12px !important;
        backdrop-filter: blur(6px);
        border: 1px solid rgba(15, 23, 42, 0.08) !important;
        background: rgba(255, 255, 255, 0.86) !important;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12) !important;
        color: #0f172a !important;
    }
    .dashboard-card__body .apexcharts-xaxis text,
    .dashboard-card__body .apexcharts-yaxis text {
        fill: #64748b !important;
        font-weight: 500;
    }
    .dashboard-card__body .apexcharts-gridline,
    .dashboard-card__body .apexcharts-xaxis line,
    .dashboard-card__body .apexcharts-yaxis line {
        stroke: rgba(148, 163, 184, 0.28) !important;
    }
    .dashboard-card__body .apexcharts-legend text {
        font-weight: 600 !important;
        color: #1e293b !important;
    }
    .dashboard-card__body .apexcharts-toolbar {
        right: 18px !important;
        top: 16px !important;
    }
    .dashboard-card__body.chart-sm .apexcharts-toolbar {
        display: none;
    }
    .dashboard-tile__body .apexcharts-svg {
        filter: none;
    }
    .dashboard-tile__body .apexcharts-xaxis text,
    .dashboard-tile__body .apexcharts-yaxis text {
        fill: #94a3b8 !important;
    }
    @media (max-width: 991px) {
        .dashboard-section__body {
            padding: 22px 20px;
        }
        .dashboard-card__body.chart-lg {
            min-height: 260px;
        }
        .dashboard-card__body.chart-sm {
            min-height: 140px;
        }
    }
</style>

<script>
    window.visitorsChart = {
        labels: @json($visitorsLabels ?? []),
        series: @json($visitorsSeries ?? [])
    };
    window.dashboardMini = {
        labels: @json($dayLabels ?? []),
        series: {
            users: @json($miniUsers ?? []),
            applications: @json($miniApplications ?? []),
            resumes: @json($miniResumes ?? []),
        }
    };
    window.analyticsData = @json($analyticsData ?? []);
    window.socialRadar = @json($socialRadar ?? []);
</script>
<script>
    (function () {
        const target = document.querySelector('#social-radar-chart');
        const radarPayload = window.socialRadar || {};
        if (!target) {
            return;
        }

        if (target.dataset.chartRendered === '1') {
            return;
        }

        const labels = Array.isArray(radarPayload.labels) ? radarPayload.labels : [];
        const seriesSource = Array.isArray(radarPayload.series) && radarPayload.series.length
            ? radarPayload.series
            : [{ name: 'Totals', data: labels.map(() => 0) }];
        const rawValues = Array.isArray(radarPayload.rawValues) ? radarPayload.rawValues : (seriesSource[0]?.data ?? []);
        const colors = Array.isArray(radarPayload.colors) && radarPayload.colors.length
            ? radarPayload.colors
            : ['#3454D1', '#41B2C4', '#EA4D4D', '#25B865'];

        if (!labels.length) {
            target.innerHTML = '<div class="text-muted text-center py-5">No data available</div>';
            return;
        }

        const normalisedSeries = seriesSource.map((serie) => ({
            name: serie.name ?? 'Totals',
            data: (serie.data ?? []).map((value) => Number(value) || 0),
        }));

        const ensureApex = (callback) => {
            if (typeof ApexCharts !== 'undefined') {
                callback();
                return;
            }

            if (Array.isArray(window.__socialRadarLoadQueue)) {
                window.__socialRadarLoadQueue.push(callback);
                return;
            }

            window.__socialRadarLoadQueue = [callback];

            const finish = () => {
                const queue = window.__socialRadarLoadQueue || [];
                delete window.__socialRadarLoadQueue;
                queue.forEach((fn) => fn());
            };

            const cdnScript = document.createElement('script');
            cdnScript.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0';
            cdnScript.async = true;
            cdnScript.onload = finish;
            cdnScript.onerror = () => {
                const fallback = document.createElement('script');
                fallback.src = '/assets/vendors/js/apexcharts.min.js';
                fallback.async = true;
                fallback.onload = finish;
                document.head.appendChild(fallback);
            };
            document.head.appendChild(cdnScript);
        };

        const renderChart = () => {
            if (target.dataset.chartRendered === '1') {
                return;
            }

            ensureApex(() => {
                if (target.dataset.chartRendered === '1') {
                    return;
                }

                const chart = new ApexCharts(target, {
                    chart: {
                        type: 'radar',
                        height: 360,
                        toolbar: { show: false },
                    },
                    labels: labels,
                    xaxis: {
                        categories: labels,
                        labels: {
                            show: true,
                            style: { colors: '#94A3B8', fontFamily: 'Inter', fontSize: '12px' },
                        },
                    },
                    yaxis: {
                        show: true,
                        min: 0,
                        tickAmount: 4,
                        labels: {
                            style: { colors: '#CBD5F5', fontFamily: 'Inter', fontSize: '11px' },
                            formatter: (val) => Number(val).toFixed(0),
                        },
                    },
                    series: normalisedSeries,
                    colors: colors.slice(0, normalisedSeries.length),
                    stroke: {
                        show: true,
                        width: 2,
                        curve: 'straight',
                    },
                    fill: {
                        opacity: 0.25,
                    },
                    markers: {
                        size: 4,
                        strokeWidth: 2,
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        horizontalAlign: 'center',
                        fontFamily: 'Inter',
                    },
                    tooltip: {
                        y: {
                            formatter: (value, opts) => {
                                const index = opts?.dataPointIndex ?? 0;
                                const original = rawValues[index] !== undefined ? rawValues[index] : value;
                                return Number(original).toLocaleString();
                            },
                        },
                    },
                });

            chart.render();
            target.dataset.chartRendered = '1';
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderChart, { once: true });
        } else {
            renderChart();
        }
    })();
</script>
@endsection
