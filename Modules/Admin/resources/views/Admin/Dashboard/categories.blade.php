@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all', 'telegram', 'hh', 'archived'], true)) {
            $currentFilter = 'all';
        }
        $searchTerm = $search ?? request('q', '');
        $isPaginator = $rows instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $rows->getCollection() : collect($rows);
        $pageCount = $collection->count();
        $totalVacanciesOnPage = $collection->sum('count');
        $rangeTotalVacancies = $totalCount ?? $totalVacanciesOnPage;
        $totalVacancies = $rangeTotalVacancies;
        $activeFilterLabel = [
            'all' => 'All sources',
            'telegram' => 'Telegram',
            'hh' => 'HeadHunter',
            'archived' => 'Archived',
        ][$currentFilter] ?? ucfirst($currentFilter);
        $dateFilter = $dateFilter ?? ['from' => request('from', ''), 'to' => request('to', '')];
        $dateRangeFrom = $dateFilter['from'] ?? '';
        $dateRangeTo = $dateFilter['to'] ?? '';
    @endphp

    <style>
        .categories-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: #ffffff;
            color: #0f172a;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .categories-hero::before,
        .categories-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .categories-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -150px;
            right: -130px;
        }

        .categories-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -140px;
            left: -140px;
        }

        .categories-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .categories-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: #eff6ff;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1d4ed8;
        }

        .categories-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .categories-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .categories-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: #f8fafc;
            font-size: 0.9rem;
            font-weight: 500;
            color: #475569;
        }

        .categories-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .categories-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .categories-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .categories-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }

        .categories-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: #94a3b8;
        }

        .categories-filter-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .categories-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(21, 37, 97, 0.14);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.8));
            overflow: visible;
        }

        .categories-filter-card {
            margin-bottom: 1.5rem;
        }

        .categories-filter-card .card-body {
            padding: 26px 32px;
        }

        .categories-filter-header {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .categories-card .card-header {
            padding: 26px 32px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .categories-card .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .categories-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            width: 100%;
            justify-content: flex-start;
        }

        .categories-search-form .input-group {
            flex: 1 1 100%;
            max-width: 100%;
            background: #f5f7ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .categories-search-form .input-group-text {
            border: none;
            background: transparent;
            color: #4f6bff;
        }

        .categories-search-form .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .categories-search-form .form-control:focus {
            box-shadow: none;
        }

        .categories-search-form .btn {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .categories-clear-btn {
            color: #8a96b8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .categories-clear-btn:hover {
            color: #1f3cfd;
        }

        .categories-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .categories-filter-card-item {
            padding: 18px 22px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.78), rgba(207, 220, 255, 0.82));
            box-shadow: 0 12px 30px rgba(26, 44, 104, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .categories-filter-card-item.active {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 14px 32px rgba(38, 91, 255, 0.3);
        }

        .categories-filter-card-item .content {
            display: grid;
            gap: 4px;
        }

        .categories-filter-card-item .label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(17, 38, 96, 0.65);
        }

        .categories-filter-card-item.active .label {
            color: rgba(255, 255, 255, 0.78);
        }

        .categories-filter-card-item .value {
            font-weight: 600;
            font-size: 1.05rem;
            color: #172655;
        }

        .categories-filter-card-item.active .value {
            color: #fff;
        }

        .categories-filter-card-item .btn {
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            background: rgba(255, 255, 255, 0.8);
            color: #1a2f70;
        }

        .categories-filter-card-item.active .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
        }

        .categories-range-filter {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .categories-range-filter .range-field {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f5f7ff;
            border-radius: 14px;
            padding: 8px 12px;
            border: 1px solid rgba(79, 107, 255, 0.1);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .categories-range-filter .range-field i {
            color: #4f6bff;
            font-size: 1rem;
        }

        .categories-range-filter .range-field input[type="date"] {
            border: none;
            background: transparent;
            padding: 6px 0;
            font-size: 0.92rem;
            color: #1f2f7a;
            min-width: 150px;
        }

        .categories-range-filter .range-field input[type="date"]:focus {
            outline: none;
        }

        .categories-range-filter .range-field input[type="date"]::-webkit-calendar-picker-indicator {
            filter: hue-rotate(210deg);
        }

        .categories-range-filter .divider {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .categories-range-filter .btn {
            border-radius: 12px;
            padding: 8px 18px;
            font-weight: 600;
        }

        .categories-results-meta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .categories-range-summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(79, 107, 255, 0.16), rgba(38, 91, 255, 0.18));
            color: #1a2f70;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.18);
        }

        .categories-range-summary i {
            color: #4f6bff;
            font-size: 0.95rem;
        }

        .categories-card .table-responsive {
            padding: 0 32px 32px;
        }

        .categories-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .categories-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .categories-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .categories-list-row {
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .categories-list-row:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .categories-list-row td:first-child {
            width: 70px;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .categories-list-row td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .categories-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            font-weight: 600;
            font-size: 0.95rem;
            color: #1f2f7a;
            box-shadow: 0 8px 18px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .categories-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .categories-pagination {
            padding: 20px 32px 34px;
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .categories-pagination nav > ul,
        .categories-pagination nav > div > ul,
        .categories-pagination nav > div > div > ul,
        .categories-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .categories-pagination nav > ul li a,
        .categories-pagination nav > ul li span,
        .categories-pagination nav > div > ul li a,
        .categories-pagination nav > div > ul li span,
        .categories-pagination nav > div > div > ul li a,
        .categories-pagination nav > div > div > ul li span,
        .categories-pagination nav .pagination li a,
        .categories-pagination nav .pagination li span {
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

        .categories-pagination nav > ul li a:hover,
        .categories-pagination nav > div > ul li a:hover,
        .categories-pagination nav > div > div > ul li a:hover,
        .categories-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .categories-pagination nav > ul li span[aria-current="page"],
        .categories-pagination nav > div > ul li span[aria-current="page"],
        .categories-pagination nav > div > div > ul li span[aria-current="page"],
        .categories-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .categories-pagination nav > ul li:first-child a,
        .categories-pagination nav > ul li:last-child a,
        .categories-pagination nav > div > ul li:first-child a,
        .categories-pagination nav > div > ul li:last-child a,
        .categories-pagination nav > div > div > ul li:first-child a,
        .categories-pagination nav > div > div > ul li:last-child a,
        .categories-pagination nav .pagination li:first-child a,
        .categories-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
        .categories-hero {
            margin: 1.5rem 1rem 0;
            padding: 32px;
            border-radius: 24px;
        }

        .categories-filter-card,
        .categories-card {
            margin: 1.5rem 1rem;
        }

            .categories-card {
                padding-bottom: 26px;
            }

            .categories-card .table-responsive {
                padding: 0 20px 24px;
            }

            .categories-card .table {
                border-spacing: 0;
            }

            .categories-filter-header {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }

            .categories-search-form {
                max-width: 100%;
            }

            .categories-range-filter {
                width: 100%;
                justify-content: flex-start;
            }

            .categories-range-filter .range-field {
                flex: 1 1 auto;
            }

            .categories-range-filter .btn {
                width: 100%;
                justify-content: center;
            }

            .categories-results-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .categories-range-summary {
                width: 100%;
                justify-content: flex-start;
            }

            .categories-card .table thead {
                display: none;
            }

            .categories-list-row {
                display: block;
                cursor: pointer;
                margin-bottom: 18px;
                border-radius: 20px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            }

            .categories-list-row:hover {
                border-color: rgba(59, 130, 246, 0.28);
                box-shadow: 0 20px 40px rgba(59, 130, 246, 0.16);
            }

            .categories-card .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 18px;
            }

            .categories-card .table tbody td:first-child,
            .categories-card .table tbody td:last-child {
                border-radius: 0;
            }

            .categories-pagination nav > ul,
            .categories-pagination nav > div > ul,
            .categories-pagination nav > div > div > ul,
            .categories-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .categories-pagination nav > ul li a,
            .categories-pagination nav > ul li span,
            .categories-pagination nav > div > ul li a,
            .categories-pagination nav > div > ul li span,
            .categories-pagination nav > div > div > ul li a,
            .categories-pagination nav > div > div > ul li span,
            .categories-pagination nav .pagination li a,
            .categories-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .categories-pagination nav > ul li:first-child a,
            .categories-pagination nav > ul li:last-child a,
            .categories-pagination nav > div > ul li:first-child a,
            .categories-pagination nav > div > ul li:last-child a,
            .categories-pagination nav > div > div > ul li:first-child a,
            .categories-pagination nav > div > div > ul li:last-child a,
            .categories-pagination nav .pagination li:first-child a,
            .categories-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Vacancies</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Categories</li>
            </ul>
        </div>
    </div>

    <div class="categories-hero">
        <div class="categories-hero__content">
            <div>
                <span class="categories-hero__badge">
                    <i class="feather-layers"></i>
                    Category overview
                </span>
                <h1 class="categories-hero__title">Vacancies by category</h1>
                <div class="categories-hero__meta">
                    <span class="categories-hero__meta-item"><i class="feather-filter"></i>{{ $activeFilterLabel }}</span>
                    <span class="categories-hero__meta-item"><i class="feather-users"></i>Total Vacancies: {{ number_format($totalVacancies) }}</span>
                    <a class="categories-hero__meta-item" href="{{ route('admin.vacancies.failed', request()->only(['filter','from','to'])) }}" style="text-decoration:none;">
                        <i class="feather-clock"></i>Queued: {{ number_format($queuedCount ?? 0) }}
                    </a>
                </div>
            </div>
            <div class="categories-stats">
                <div class="categories-stat-card">
                    <span class="label">Categories on this page</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">Filtered list size</span>
                </div>
                <div class="categories-stat-card">
                    <span class="label">Vacancies on this page</span>
                    <span class="value">{{ number_format($totalVacanciesOnPage) }}</span>
                    <span class="hint">Total roles in shown categories</span>
                </div>
            </div>
        </div>
    </div>
        
    <div class="card categories-filter-card">
        <div class="card-body">
            <div class="categories-filter-grid">
                @foreach([
                    ['value' => 'all', 'label' => 'All', 'description' => 'Show all sources'],
                    ['value' => 'telegram', 'label' => 'Telegram', 'description' => 'Telegram collected vacancies'],
                    ['value' => 'hh', 'label' => 'HH', 'description' => 'HeadHunter imports'],
                    ['value' => 'archived', 'label' => 'Archived', 'description' => 'Archived vacancies only'],
                ] as $card)
                    @php
                        $isActive = $currentFilter === $card['value'];
                        $params = [];
                        if ($card['value'] !== 'all') {
                            $params['filter'] = $card['value'];
                        }
                        if (!empty($searchTerm)) {
                            $params['q'] = $searchTerm;
                        }
                    @endphp
                    <div class="categories-filter-card-item {{ $isActive ? 'active' : '' }}">
                        <div class="content">
                            <span class="label">{{ strtoupper($card['label']) }}</span>
                            <span class="value">{{ $card['description'] }}</span>
                        </div>
                        <a href="{{ route('admin.vacancies.categories', $params) }}" class="btn">
                            {{ $isActive ? 'Selected' : 'View' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card categories-card">
        <div class="card-header">
            @php
                $rangeClearParams = array_filter([
                    'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                    'q' => $searchTerm !== '' ? $searchTerm : null,
                ], static fn ($value) => !is_null($value) && $value !== '');
                $rangeActive = ($dateRangeFrom !== '') || ($dateRangeTo !== '');
            @endphp
            <div class="filters">
                <div class="categories-results-meta">
                    <h6 class="mb-0">Results</h6>
                    <span class="categories-range-summary">
                        <i class="feather-bar-chart-2"></i>
                        <span>Filtered vacancies: {{ number_format($rangeTotalVacancies) }}</span>
                    </span>
                </div>
                <form method="GET" action="{{ route('admin.vacancies.categories') }}" class="categories-range-filter">
                    @if($currentFilter !== 'all')
                        <input type="hidden" name="filter" value="{{ $currentFilter }}">
                    @endif
                    @if($searchTerm !== '')
                        <input type="hidden" name="q" value="{{ $searchTerm }}">
                    @endif
                    <div class="range-field">
                        <i class="feather-calendar"></i>
                        <input type="date" name="from" value="{{ $dateRangeFrom }}" aria-label="From date">
                    </div>
                    <span class="divider">to</span>
                    <div class="range-field">
                        <i class="feather-calendar"></i>
                        <input type="date" name="to" value="{{ $dateRangeTo }}" aria-label="To date">
                    </div>
                    <button type="submit" class="btn btn-primary shadow-sm">Apply</button>
                    @if($rangeActive)
                        <a href="{{ route('admin.vacancies.categories', $rangeClearParams) }}" class="categories-clear-btn">
                            <i class="feather-x-circle"></i>
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted">ID</th>
                        
                        <th class="text-muted">Category</th>
                        <th class="text-end text-muted">Vacancies</th>
                    </tr>
                </thead>
                <tbody>
                    @php $firstNumber = method_exists($rows, 'firstItem') ? ($rows->firstItem() ?? 1) : 1; @endphp
                    @forelse($rows as $index => $row)
                        @php
                            $categorySlug = $row->slug ?? ($row->category ?: 'other');
                            $viewParams = array_filter([
                                'category' => $categorySlug,
                                'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                                'q' => !empty($searchTerm) ? $searchTerm : null,
                                'from' => $dateRangeFrom !== '' ? $dateRangeFrom : null,
                                'to' => $dateRangeTo !== '' ? $dateRangeTo : null,
                            ], fn ($value) => !is_null($value));
                        @endphp
                        <tr class="categories-list-row" onclick="window.location.href='{{ route('admin.vacancies.by_category', $viewParams) }}'">
                            <td class="text-center" data-label="#">
                                <div class="categories-index-pill">{{ $firstNumber + $index }}</div>
                            </td>
                            
                            <td class="text-capitalize fw-semibold text-dark" data-label="Category">{{ $row->category ?: 'other' }}</td>
                            <td class="text-end fw-semibold" data-label="Vacancies">{{ number_format($row->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center categories-empty">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator || $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="categories-pagination">
                {{ $rows->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
