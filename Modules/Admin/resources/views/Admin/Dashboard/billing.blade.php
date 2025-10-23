@extends('admin::components.layouts.master')

@section('content')
@php
    $metrics = $metrics ?? [];
    $planOverview = collect($planOverview ?? []);
    $planUserTotals = collect($planUserTotals ?? []);
    $monthlyPayers = collect($monthlyPayers ?? []);
    $lifetimePayers = collect($lifetimePayers ?? []);
    $planRevenueChart = $planRevenueChart ?? ['labels' => [], 'revenue' => [], 'unique_users' => []];

    $formatMoney = fn ($value) => number_format((float) $value, 2, '.', ' ');
    $formatNumber = fn ($value) => number_format((int) $value, 0, '.', ' ');

    $maxSubscriptions = max(1, $planOverview->max('subscriptions'));
@endphp

<div class="billing-dashboard">
    <header class="billing-dashboard__hero">
        <div class="billing-dashboard__hero-copy">
            <span class="badge badge-primary-soft">Billing Suite</span>
            <h1>Yangi avlod to‘lov analitikasi</h1>
            <p>
                Planlar, obunalar va foydalanuvchi xarajatlarini zamonaviy ko‘rinishda kuzating.
                Hamma muhim metrikalar bitta boshqaruv panelida jamlangan.
            </p>
        </div>
        <div class="billing-dashboard__hero-stats">
            <div class="hero-stat">
                <span class="label">Umumiy tushum</span>
                <span class="value">UZS {{ $formatMoney($metrics['overall_revenue'] ?? 0) }}</span>
            </div>
            <div class="hero-stat">
                <span class="label">Aktiv to‘lovchilar</span>
                <span class="value">{{ $formatNumber($metrics['total_paying_users'] ?? 0) }}</span>
            </div>
        </div>
    </header>

    <section class="billing-dashboard__metrics">
        <article class="metric-card">
            <div class="metric-card__icon metric-card__icon--primary">
                <i class="feather-layers"></i>
            </div>
            <div>
                <span class="metric-card__label">Umumiy tariflar</span>
                <span class="metric-card__value">{{ $formatNumber($metrics['total_plans'] ?? 0) }}</span>
            </div>
        </article>
        <article class="metric-card">
            <div class="metric-card__icon metric-card__icon--success">
                <i class="feather-users"></i>
            </div>
            <div>
                <span class="metric-card__label">Umumiy sotib olishlar</span>
                <span class="metric-card__value">{{ $formatNumber($metrics['total_subscriptions'] ?? 0) }}</span>
            </div>
        </article>
        <article class="metric-card">
            <div class="metric-card__icon metric-card__icon--warning">
                <i class="feather-credit-card"></i>
            </div>
            <div>
                <span class="metric-card__label">To‘lov qilgan foydalanuvchilar</span>
                <span class="metric-card__value">{{ $formatNumber($metrics['total_paying_users'] ?? 0) }}</span>
            </div>
        </article>
        <article class="metric-card">
            <div class="metric-card__icon metric-card__icon--info">
                <i class="feather-activity"></i>
            </div>
            <div>
                <span class="metric-card__label">Tarif bo‘yicha o‘rtacha tushum</span>
                <span class="metric-card__value">UZS {{ $formatMoney($planOverview->avg('revenue') ?? 0) }}</span>
            </div>
        </article>
    </section>

    <section class="billing-dashboard__grid">
        <div class="billing-card billing-card--stretch">
            <div class="billing-card__header">
                <div>
                    <h2>Tariflar kesimidagi tushum</h2>
                    <span>Har bir tarifdan tushayotgan daromad va noyob foydalanuvchilar</span>
                </div>
                <div class="billing-card__legend">
                    <span class="legend-dot legend-dot--primary"></span> Daromad
                    <span class="legend-dot legend-dot--accent"></span> Unikal foydalanuvchilar
                </div>
            </div>
            <div class="billing-card__body">
                <div id="plan-revenue-chart" class="billing-chart"></div>
            </div>
        </div>

        <div class="billing-card">
            <div class="billing-card__header">
                <div>
                    <h2>Tariflar bo‘yicha sotib olishlar</h2>
                    <span>Qaysi tarif nechta foydalanuvchi tomonidan sotib olingan</span>
                </div>
            </div>
            <div class="billing-card__body billing-card__body--scrollable">
                <ul class="plan-progress">
                    @forelse($planOverview as $plan)
                        @php
                            $percentage = $maxSubscriptions > 0
                                ? round(($plan['subscriptions'] / $maxSubscriptions) * 100)
                                : 0;
                        @endphp
                        <li class="plan-progress__item">
                            <div class="plan-progress__header">
                                <div>
                                    <span class="plan-name">{{ $plan['name'] }}</span>
                                    <span class="plan-meta">UZS {{ $formatMoney($plan['price']) }}</span>
                                </div>
                                <div class="plan-counts">
                                    <span>{{ $formatNumber($plan['subscriptions']) }} sotib olish</span>
                                    <span>{{ $formatNumber($plan['unique_users']) }} foydalanuvchi</span>
                                </div>
                            </div>
                            <div class="plan-progress__bar">
                                <div class="plan-progress__fill" style="width: {{ $percentage }}%"></div>
                            </div>
                        </li>
                    @empty
                        <li class="plan-progress__empty">Ma'lumot topilmadi</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="billing-card">
            <div class="billing-card__header">
                <div>
                    <h2>Oyma-oy eng katta to‘lovchilar</h2>
                    <span>Oxirgi 30 kun davomida eng ko‘p to‘lov qilgan foydalanuvchilar</span>
                </div>
            </div>
            <div class="billing-card__body billing-card__body--scrollable">
                <ul class="payer-list">
                    @forelse($monthlyPayers as $payer)
                        <li class="payer-card">
                            <div class="payer-card__avatar">
                                <span>{{ strtoupper(mb_substr($payer['user_name'], 0, 2)) }}</span>
                            </div>
                            <div class="payer-card__meta">
                                <span class="payer-card__name">{{ $payer['user_name'] }}</span>
                                @if(!empty($payer['email']))
                                    <span class="payer-card__email">{{ $payer['email'] }}</span>
                                @endif
                                <span class="payer-card__payments">{{ $payer['payments_count'] }} ta to‘lov</span>
                            </div>
                            <div class="payer-card__amount">
                                <span>UZS {{ $formatMoney($payer['total_amount']) }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="payer-card payer-card--empty">Hozircha to‘lovlar aniqlanmadi</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="billing-card billing-card--stretch">
            <div class="billing-card__header">
                <div>
                    <h2>Top 10 - Eng ko‘p to‘lov qilganlar</h2>
                    <span>Foydalanuvchilarning to‘lov tarixi bo‘yicha umumiy sarfi</span>
                </div>
            </div>
            <div class="billing-card__body billing-card__body--scrollable">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Foydalanuvchi</th>
                            <th>Kontakt</th>
                            <th>To‘lovlar soni</th>
                            <th>Jami summa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lifetimePayers as $index => $payer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $payer['user_name'] }}</td>
                                <td>
                                    <span class="table-contact">{{ $payer['email'] ?? '—' }}</span>
                                </td>
                                <td>{{ $payer['payments_count'] }}</td>
                                <td>UZS {{ $formatMoney($payer['total_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Ma'lumot yo‘q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="billing-card billing-card--wide">
            <div class="billing-card__header">
                <div>
                    <h2>Tarif kesimidagi foydalanuvchilar sarfi</h2>
                    <span>Har bir tarif bo‘yicha eng faol foydalanuvchilar</span>
                </div>
            </div>
            <div class="billing-card__body billing-card__body--collapsible">
                @forelse($planUserTotals as $planUsers)
                    <div class="plan-accordion">
                        <button type="button" class="plan-accordion__toggle" data-accordion>
                            <div>
                                <span class="plan-accordion__title">{{ $planUsers['plan_name'] }}</span>
                                <span class="plan-accordion__subtitle">{{ $planUsers['users']->count() }} ta foydalanuvchi</span>
                            </div>
                            <i class="feather-chevron-down"></i>
                        </button>
                        <div class="plan-accordion__content">
                            <ul class="plan-accordion__list">
                                @foreach($planUsers['users'] as $user)
                                    <li>
                                        <span class="user-name">{{ $user['user_name'] }}</span>
                                        <span class="user-meta">{{ $user['payments_count'] }} ta tranzaksiya</span>
                                        <span class="user-amount">UZS {{ $formatMoney($user['total_amount']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @empty
                    <div class="plan-accordion plan-accordion--empty">
                        Ma'lumot mavjud emas
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>

<style>
    .billing-dashboard {
        display: flex;
        flex-direction: column;
        gap: 28px;
        padding-bottom: 36px;
        padding-left: clamp(12px, 2vw, 16px);
        padding-right: clamp(12px, 2vw, 16px);
    }
    .billing-dashboard__hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 32px;
        padding: 32px clamp(24px, 4vw, 40px);
        border-radius: 24px;
        background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.16), rgba(14, 116, 144, 0.12));
        border: 1px solid rgba(59, 130, 246, 0.18);
        position: relative;
        overflow: hidden;
    }
    .billing-dashboard__hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%);
        pointer-events: none;
    }
    .billing-dashboard__hero-copy {
        max-width: 560px;
        position: relative;
        z-index: 1;
    }
    .billing-dashboard__hero h1 {
        font-size: clamp(26px, 2.4vw, 34px);
        font-weight: 700;
        margin: 12px 0 14px;
        color: #0f172a;
    }
    .billing-dashboard__hero p {
        margin: 0;
        color: #1e293b;
        font-size: 16px;
        line-height: 1.6;
    }
    .billing-dashboard__hero-stats {
        display: grid;
        gap: 16px;
        min-width: 260px;
        position: relative;
        z-index: 1;
    }
    .hero-stat {
        background: rgba(15, 23, 42, 0.78);
        color: #fff;
        border-radius: 18px;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        backdrop-filter: blur(12px);
    }
    .hero-stat .label {
        font-size: 13px;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        opacity: 0.75;
    }
    .hero-stat .value {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .billing-dashboard__metrics {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .metric-card {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #fff;
        border-radius: 20px;
        padding: 20px clamp(18px, 3vw, 24px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    .metric-card__icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 22px;
    }
    .metric-card__icon--primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .metric-card__icon--success { background: linear-gradient(135deg, #059669, #047857); }
    .metric-card__icon--warning { background: linear-gradient(135deg, #ca8a04, #a16207); }
    .metric-card__icon--info { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .metric-card__label {
        font-size: 13px;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
    }
    .metric-card__value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
    }

    .billing-dashboard__grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 22px;
    }
    .billing-card {
        background: #fff;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        grid-column: span 4 / span 4;
        min-height: 320px;
    }
    .billing-card--stretch { grid-column: span 8 / span 8; min-height: 380px; }
    .billing-card--wide { grid-column: span 12 / span 12; }

    .billing-card__header {
        padding: 22px clamp(22px, 3vw, 28px) 12px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }
    .billing-card__header h2 {
        font-size: 20px;
        margin-bottom: 4px;
        font-weight: 700;
        color: #0f172a;
    }
    .billing-card__header span {
        font-size: 14px;
        color: #64748b;
        display: block;
    }
    .billing-card__legend {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #475569;
    }
    .legend-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }
    .legend-dot--primary { background: #2563eb; }
    .legend-dot--accent { background: #f97316; }

    .billing-card__body {
        padding: 0 clamp(22px, 3vw, 28px) 26px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .billing-card__body--scrollable {
        overflow-y: auto;
        max-height: 360px;
        padding-right: 6px;
    }
    .billing-card__body--collapsible {
        gap: 16px;
    }

    .billing-chart {
        width: 100%;
        height: 340px;
    }

    .plan-progress {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .plan-progress__item {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .plan-progress__header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }
    .plan-name {
        font-weight: 700;
        font-size: 16px;
        color: #0f172a;
    }
    .plan-meta {
        font-size: 13px;
        color: #64748b;
    }
    .plan-counts {
        display: flex;
        gap: 16px;
        font-size: 13px;
        color: #475569;
    }
    .plan-progress__bar {
        height: 10px;
        width: 100%;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }
    .plan-progress__fill {
        height: 100%;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: inherit;
        transition: width 0.6s ease;
    }
    .plan-progress__empty {
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    .payer-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .payer-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 18px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.7), rgba(219, 234, 254, 0.5));
    }
    .payer-card--empty {
        justify-content: center;
        color: #94a3b8;
    }
    .payer-card__avatar {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #1d4ed8;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        display: grid;
        place-items: center;
    }
    .payer-card__meta {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .payer-card__name {
        font-weight: 700;
        color: #0f172a;
    }
    .payer-card__email,
    .payer-card__payments {
        font-size: 13px;
        color: #475569;
    }
    .payer-card__amount {
        font-weight: 700;
        color: #0f172a;
    }

    .billing-table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 16px;
        overflow: hidden;
    }
    .billing-table thead {
        background: #f1f5f9;
    }
    .billing-table th,
    .billing-table td {
        padding: 14px 16px;
        text-align: left;
        font-size: 14px;
        color: #1f2937;
    }
    .billing-table tbody tr:nth-child(odd) {
        background: #fbfdff;
    }
    .billing-table tbody tr:hover {
        background: #eef2ff;
    }
    .table-contact {
        color: #475569;
        font-size: 13px;
    }

    .plan-accordion {
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 18px;
        overflow: hidden;
        background: #f8fafc;
    }
    .plan-accordion + .plan-accordion {
        margin-top: 14px;
    }
    .plan-accordion__toggle {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 20px;
        background: none;
        border: none;
        outline: none;
        font-family: inherit;
        cursor: pointer;
        font-weight: 600;
        color: #0f172a;
        transition: background 0.3s ease;
    }
    .plan-accordion__toggle i {
        transition: transform 0.3s ease;
    }
    .plan-accordion__toggle[aria-expanded="true"] i {
        transform: rotate(-180deg);
    }
    .plan-accordion__title {
        font-size: 16px;
        display: block;
    }
    .plan-accordion__subtitle {
        font-size: 13px;
        color: #475569;
        display: block;
    }
    .plan-accordion__content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s ease;
        background: #fff;
    }
    .plan-accordion__content.is-open {
        max-height: 520px;
    }
    .plan-accordion__list {
        list-style: none;
        margin: 0;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .plan-accordion__list li {
        display: grid;
        grid-template-columns: 1fr auto auto;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .plan-accordion__list li:last-child {
        border-bottom: none;
    }
    .plan-accordion--empty {
        padding: 22px;
        text-align: center;
        color: #94a3b8;
    }
    .user-name {
        font-weight: 600;
        color: #0f172a;
    }
    .user-meta {
        font-size: 13px;
        color: #64748b;
    }
    .user-amount {
        font-weight: 700;
        color: #0f172a;
    }

    @media (max-width: 1280px) {
        .billing-dashboard__metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .billing-dashboard__grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
        .billing-card--stretch {
            grid-column: span 6 / span 6;
        }
        .billing-card {
            grid-column: span 6 / span 6;
        }
    }

    @media (max-width: 992px) {
        .billing-dashboard__hero {
            flex-direction: column;
            align-items: flex-start;
        }
        .billing-dashboard__hero-stats {
            width: 100%;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .hero-stat {
            text-align: center;
        }
        .billing-dashboard__metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .billing-dashboard__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .billing-card,
        .billing-card--stretch,
        .billing-card--wide {
            grid-column: span 2 / span 2;
        }
        .plan-accordion__list li {
            grid-template-columns: 1fr;
            align-items: flex-start;
        }
        .plan-accordion__list li span {
            display: block;
        }
    }

    @media (max-width: 680px) {
        .billing-dashboard__metrics {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .billing-dashboard__grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .billing-card,
        .billing-card--stretch,
        .billing-card--wide {
            grid-column: span 1 / span 1;
        }
        .billing-dashboard__hero-stats {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
    }
</style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        (function () {
            const chartData = @json($planRevenueChart);

            const revenueChartEl = document.querySelector('#plan-revenue-chart');
            if (revenueChartEl && chartData.labels.length) {
                const revenueChart = new ApexCharts(revenueChartEl, {
                    chart: {
                        type: 'line',
                        height: 360,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    stroke: {
                        width: [0, 4],
                        curve: 'smooth',
                    },
                    colors: ['#2563eb', '#f97316'],
                    series: [
                        {
                            name: 'Daromad',
                            type: 'column',
                            data: chartData.revenue,
                        },
                        {
                            name: 'Unikal foydalanuvchilar',
                            type: 'line',
                            data: chartData.unique_users,
                        },
                    ],
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [1],
                        background: {
                            enabled: true,
                            foreColor: '#0f172a',
                            borderRadius: 6,
                            padding: 6,
                            opacity: 0.9,
                        },
                        formatter: function (val, opts) {
                            if (opts.seriesIndex === 0) {
                                return 'UZS ' + Number(val).toLocaleString();
                            }
                            return Number(val).toLocaleString();
                        },
                    },
                    xaxis: {
                        categories: chartData.labels,
                        labels: {
                            style: { colors: '#475569', fontSize: '13px' },
                        },
                    },
                    yaxis: [
                        {
                            labels: {
                                style: { colors: '#475569' },
                                formatter: val => Number(val).toLocaleString(),
                            },
                            title: { text: 'Daromad', style: { color: '#2563eb' } },
                        },
                        {
                            opposite: true,
                            labels: {
                                style: { colors: '#475569' },
                                formatter: val => Number(val).toLocaleString(),
                            },
                            title: { text: 'Foydalanuvchilar', style: { color: '#f97316' } },
                        },
                    ],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 10,
                        },
                    },
                    grid: {
                        borderColor: '#e2e8f0',
                        strokeDashArray: 3,
                    },
                    legend: {
                        show: false,
                    },
                });
                revenueChart.render();
            }

            document.querySelectorAll('[data-accordion]').forEach((toggle) => {
                const content = toggle.parentElement.querySelector('.plan-accordion__content');
                toggle.setAttribute('aria-expanded', 'false');

                toggle.addEventListener('click', () => {
                    const isOpen = content.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });
        })();
    </script>
@endpush
