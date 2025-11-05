@extends('admin::components.layouts.master')

@section('content')
    @php
        $isPaginator = $rows instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $rows->getCollection() : collect($rows);
        $totalVisitors = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $rows->total() : $collection->count();
        $pageVisitors = $collection->count();
        $pageVisits = $collection->sum('visits_count');
        $topVisit = $collection->max('visits_count');
    @endphp

    <style>
        .visitors-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: linear-gradient(135deg, #0048ff, #53a0ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(8, 51, 153, 0.28);
        }

        .visitors-hero::before,
        .visitors-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.2;
        }

        .visitors-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.4);
            top: -150px;
            right: -130px;
        }

        .visitors-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
            bottom: -140px;
            left: -140px;
        }

        .visitors-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .visitors-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .visitors-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .visitors-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .visitors-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .visitors-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .visitors-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .visitors-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .visitors-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .visitors-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .visitors-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(21, 37, 97, 0.14);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.8));
            padding: 26px 30px 32px;
            overflow: visible;
        }

        .visitors-card .table-responsive {
            padding: 0 32px 32px;
        }

        .visitors-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .visitors-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .visitors-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .visitors-card .table tbody tr {
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .visitors-card .table tbody tr:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .visitors-card .table tbody tr td:first-child {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .visitors-card .table tbody tr td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .visitors-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            font-weight: 600;
            font-size: 1rem;
            color: #1f2f7a;
            box-shadow: 0 10px 20px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .visitors-card .avatar-text {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
        }

        .visitors-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .visitors-pagination {
            padding: 20px 32px 34px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .visitors-pagination nav > ul,
        .visitors-pagination nav > div > ul,
        .visitors-pagination nav > div > div > ul,
        .visitors-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .visitors-pagination nav > ul li a,
        .visitors-pagination nav > ul li span,
        .visitors-pagination nav > div > ul li a,
        .visitors-pagination nav > div > ul li span,
        .visitors-pagination nav > div > div > ul li a,
        .visitors-pagination nav > div > div > ul li span,
        .visitors-pagination nav .pagination li a,
        .visitors-pagination nav .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a2f70;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .visitors-pagination nav > ul li a:hover,
        .visitors-pagination nav > div > ul li a:hover,
        .visitors-pagination nav > div > div > ul li a:hover,
        .visitors-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .visitors-pagination nav > ul li span[aria-current="page"],
        .visitors-pagination nav > div > ul li span[aria-current="page"],
        .visitors-pagination nav > div > div > ul li span[aria-current="page"],
        .visitors-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .visitors-pagination nav > ul li:first-child a,
        .visitors-pagination nav > ul li:last-child a,
        .visitors-pagination nav > div > ul li:first-child a,
        .visitors-pagination nav > div > ul li:last-child a,
        .visitors-pagination nav > div > div > ul li:first-child a,
        .visitors-pagination nav > div > div > ul li:last-child a,
        .visitors-pagination nav .pagination li:first-child a,
        .visitors-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .visitors-hero {
                margin: 1.5rem 1rem;
                border-radius: 24px;
                padding: 32px;
            }

            .visitors-card {
                margin: 1.5rem 1rem 2rem;
                padding: 24px 20px 26px;
            }

            .visitors-card .table-responsive {
                padding: 0;
            }

            .visitors-card .table {
                border-spacing: 0;
            }

            .visitors-card .table thead {
                display: none;
            }

            .visitors-card .table tbody tr {
                display: block;
                margin-bottom: 18px;
                border-radius: 20px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                padding: 18px;
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                transform: none !important;
            }

            .visitors-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
                gap: 12px;
            }

            .visitors-card .table tbody td:first-child,
            .visitors-card .table tbody td:last-child {
                border-radius: 0;
            }

            .visitors-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .visitors-pagination nav > ul,
            .visitors-pagination nav > div > ul,
            .visitors-pagination nav > div > div > ul,
            .visitors-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .visitors-pagination nav > ul li a,
            .visitors-pagination nav > ul li span,
            .visitors-pagination nav > div > ul li a,
            .visitors-pagination nav > div > ul li span,
            .visitors-pagination nav > div > div > ul li a,
            .visitors-pagination nav > div > div > ul li span,
            .visitors-pagination nav .pagination li a,
            .visitors-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .visitors-pagination nav > ul li:first-child a,
            .visitors-pagination nav > ul li:last-child a,
            .visitors-pagination nav > div > ul li:first-child a,
            .visitors-pagination nav > div > ul li:last-child a,
            .visitors-pagination nav > div > div > ul li:first-child a,
            .visitors-pagination nav > div > div > ul li:last-child a,
            .visitors-pagination nav .pagination li:first-child a,
            .visitors-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Analytics</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Top visitors</li>
            </ul>
        </div>
    </div>

    <div class="visitors-hero">
        <div class="visitors-hero__content">
            <div>
                <span class="visitors-hero__badge">
                    <i class="feather-trending-up"></i>
                    Traffic insight
                </span>
                <h1 class="visitors-hero__title">Top visitors</h1>
                <div class="visitors-hero__meta">
                    <span class="visitors-hero__meta-item"><i class="feather-users"></i>Total records: {{ number_format($totalVisitors) }}</span>
                    <span class="visitors-hero__meta-item"><i class="feather-list"></i>On this page: {{ number_format($pageVisitors) }}</span>
                </div>
            </div>
                <div class="visitors-stats">
                    <div class="visitors-stat-card">
                        <span class="label">Visits on this page</span>
                        <span class="value">{{ number_format($pageVisits) }}</span>
                        <span class="hint">Sum across listed visitors</span>
                    </div>
                    <div class="visitors-stat-card">
                        <span class="label">Top visitor</span>
                        <span class="value">{{ $topVisit ? number_format($topVisit) : '—' }}</span>
                        <span class="hint">Highest visits count</span>
                    </div>
                    <div class="visitors-stat-card">
                        <span class="label">Average per user</span>
                        <span class="value">{{ isset($avgPerUser) ? number_format($avgPerUser, 2) : '—' }}</span>
                        <span class="hint">{{ isset($totalVisits,$totalUsers) ? number_format($totalVisits).' / '.number_format($totalUsers) : 'All users' }}</span>
                    </div>
                </div>
        </div>
    </div>

    <div class="card visitors-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 110px;" class="text-muted">Listing</th>
                        <th class="text-muted">User</th>
                        <th class="text-muted">Last visit</th>
                        <th class="text-end text-muted">Visits</th>
                    </tr>
                </thead>
                <tbody>
                    @php $firstNumber = method_exists($rows, 'firstItem') ? ($rows->firstItem() ?? 1) : 1; @endphp
                    @forelse($rows as $index => $visitor)
                        <tr>
                            <td data-label="#" class="text-center">
                                <div class="visitors-index-pill">{{ $firstNumber + $index }}</div>
                            </td>
                            <td data-label="User">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="avatar-text"><i class="feather-user"></i></div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ trim(($visitor->first_name ?? '').' '.($visitor->last_name ?? '')) ?: ($visitor->email ?? 'User #'.$visitor->id) }}</div>
                                        <div class="text-muted small">ID: {{ $visitor->id }}</div>
                                    </div>
                                </div>
                            </td>
                            @php
                                $last = $visitor->last_visited_at ?? null;
                                $lastFormatted = $last ? \Illuminate\Support\Carbon::parse($last)->format('Y-m-d H:i') : '—';
                            @endphp
                            <td data-label="Last visit">{{ $lastFormatted }}</td>
                            <td data-label="Visits" class="text-end fw-semibold">{{ number_format($visitor->visits_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center visitors-empty">No visitor data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator || $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="visitors-pagination">
                {{ $rows->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
