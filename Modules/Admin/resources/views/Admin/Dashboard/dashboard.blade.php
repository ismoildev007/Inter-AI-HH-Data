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

<div class="main-content">
    <!-- Statistik kartalar -->
    <div class="row g-3">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <a href="{{ route('admin.users.index') }}" class="nxl-link">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-users"></i>
                            </div>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $usersCount }}</h4>
                            <small class="text-muted">Users</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <a href="{{ route('admin.resumes.index') }}" class="nxl-link">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-file-text"></i>
                            </div>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $resumesCount }}</h4>
                            <small class="text-muted">Resumes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <a href="{{ route('admin.applications.index') }}" class="nxl-link">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-briefcase"></i>
                            </div>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $applicationsCount }}</h4>
                            <small class="text-muted">Applications</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <a href="{{ route('admin.telegram_channels.index') }}" class="nxl-link">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-send"></i>
                            </div>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $telegramChannelsCount }}</h4>
                            <small class="text-muted">Telegram Channels</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 👇 pastdagi barcha qismlar sizdagi original ko‘rinishda qoldi -->
    <!-- Visitors + Tasks -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Visitors Overview</h5>
                </div>
                <div class="card-body">
                    <div id="visitors-overview-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Users (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['users'] ?? 0 }}</span>
                            </div>
                            <div id="task-completed-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Applications (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['applications'] ?? 0 }}</span>
                            </div>
                            <div id="new-tasks-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Resumes (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['resumes'] ?? 0 }}</span>
                            </div>
                            <div id="project-done-area-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics + Leads + Team -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Hourly Visitors</h6>
                    <div id="bounce-rate"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Daily Visitors</h6>
                    <div id="page-views"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Monthly Visitors</h6>
                    <div id="site-impressions"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Yearly Visitors</h6>
                    <div id="conversions-rate"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vacancies analytics -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Hourly Vacancies</h6>
                    <div id="vacancy-hourly"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Daily Vacancies</h6>
                    <div id="vacancy-daily"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Weekly Vacancies</h6>
                    <div id="vacancy-weekly"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Monthly Vacancies</h6>
                    <div id="vacancy-monthly"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Schedule + Social Radar -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Top Visitors</h5>
                </div>
                <div class="card-body">
                    @forelse($topUsers as $u)
                    <div class="d-flex justify-content-between border-bottom py-2 align-items-center">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="avatar-text bg-primary text-white"><i class="feather-user"></i></div>
                            <div>
                                <b>{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'User #'.$u->id) }}</b><br>
                                <small class="text-muted">{{ $u->email }}</small>
                            </div>
                        </div>
                        <div class="text-end"><b>{{ $u->visits_count }}</b></div>
                    </div>
                    @empty
                    <div class="text-muted">No visitor data yet</div>
                    @endforelse
                </div>
                <a href="{{ route('admin.visits.top_users') }}" class="card-footer text-center">View All</a>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Vacancies by Category @isset($vacanciesTotal)<span class="text-muted fs-12">(Total: {{ $vacanciesTotal }})</span>@endisset</h5>
                </div>
                <div class="card-body">
                    @if(!empty($vacancyCategories) && count($vacancyCategories))
                    @foreach($vacancyCategories as $row)
                    <div class="d-flex justify-content-between border-bottom py-2 align-items-center">
                        <div class="text-capitalize">{{ $row->category ?: 'other' }}</div>
                        <div class="fw-bold">{{ $row->c }}</div>
                    </div>
                    @endforeach
                    @else
                    <div class="text-muted">No vacancy data yet</div>
                    @endif
                </div>
                <a href="{{ route('admin.vacancies.categories') }}" class="card-footer text-center">View All</a>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Social Radar</h5>
                </div>
                <div class="card-body">
                    <div id="social-radar-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

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
