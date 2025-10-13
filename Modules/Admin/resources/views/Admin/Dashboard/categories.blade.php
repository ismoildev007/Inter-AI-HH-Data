@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all', 'telegram', 'hh'], true)) {
            $currentFilter = 'all';
        }
        $searchTerm = $search ?? request('q', '');
        $isPaginator = $rows instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $rows->getCollection() : collect($rows);
        $pageCount = $collection->count();
        $totalCategories = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $rows->total() : ($totalCount ?? $collection->count());
        $totalVacanciesOnPage = $collection->sum('c');
        $activeFilterLabel = [
            'all' => 'All sources',
            'telegram' => 'Telegram',
            'hh' => 'HeadHunter',
        ][$currentFilter] ?? ucfirst($currentFilter);
    @endphp

    <style>
        .categories-hero {
            margin: 0 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: linear-gradient(135deg, #0f56ff, #5ea6ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(9, 57, 170, 0.28);
        }

        .categories-hero::before,
        .categories-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.2;
        }

        .categories-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.4);
            top: -150px;
            right: -130px;
        }

        .categories-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
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
            background: rgba(255, 255, 255, 0.22);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .categories-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
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
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .categories-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .categories-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .categories-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .categories-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .categories-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .categories-card {
            margin: 0 1.5rem 2rem;
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(21, 37, 97, 0.12);
            overflow: hidden;
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

        .categories-card .search-form {
            display: flex;
            flex-grow: 1;
            max-width: 420px;
        }

        .categories-card .search-form .input-group {
            background: #f5f7ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .categories-card .search-form input {
            border: none;
            background: transparent;
            padding: 10px 16px;
        }

        .categories-card .search-form input:focus {
            box-shadow: none;
        }

        .categories-card .filter-group .btn {
            min-width: 88px;
            border-radius: 999px;
        }

        .categories-card .table thead th {
            padding: 18px 20px;
            background: rgba(31, 60, 253, 0.08);
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .categories-card .table tbody td {
            padding: 18px 20px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            vertical-align: middle;
        }

        .categories-card .table tbody tr:hover {
            background: rgba(86, 134, 255, 0.08);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .categories-index-pill {
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
                margin-inline: 1rem;
                padding: 32px;
                border-radius: 24px;
            }

            .categories-card {
                margin-inline: 1rem;
            }

            .categories-card .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .categories-card .search-form {
                max-width: 100%;
            }

            .categories-card .filter-group {
                display: flex;
                gap: 10px;
            }

            .categories-card .table thead {
                display: none;
            }

            .categories-card .table tbody tr {
                display: block;
                margin-bottom: 18px;
                border-radius: 20px;
                border: 1px solid rgba(15, 35, 87, 0.08);
                padding: 18px;
                background: #fff;
                transform: none !important;
            }

            .categories-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border: none;
            }

            .categories-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
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
                    <span class="categories-hero__meta-item"><i class="feather-users"></i>Total categories: {{ number_format($totalCategories) }}</span>
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

    <div class="card categories-card">
        <div class="card-header">
            <div class="filters">
                <div>
                    <h6 class="mb-1">All categories</h6>
                    <span class="text-muted small">Manage vacancy distribution across sources</span>
                </div>
                <form method="GET" class="search-form">
                    @if($currentFilter !== 'all')
                        <input type="hidden" name="filter" value="{{ $currentFilter }}">
                    @endif
                    <div class="input-group input-group-sm w-100">
                        <input type="search" name="q" value="{{ $searchTerm }}" class="form-control" placeholder="Search categories">
                        @if(!empty($searchTerm))
                            <a href="{{ route('admin.vacancies.categories', $currentFilter === 'all' ? [] : ['filter' => $currentFilter]) }}" class="btn btn-outline-secondary">
                                <i class="feather-x"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="feather-search"></i>
                        </button>
                    </div>
                </form>
                <div class="filter-group btn-group btn-group-sm" role="group">
                    @foreach(['all' => 'All', 'telegram' => 'Telegram', 'hh' => 'HH'] as $value => $label)
                        @php
                            $isActive = $currentFilter === $value;
                            $params = [];
                            if ($value !== 'all') {
                                $params['filter'] = $value;
                            }
                            if (!empty($searchTerm)) {
                                $params['q'] = $searchTerm;
                            }
                        @endphp
                        <a href="{{ route('admin.vacancies.categories', $params) }}" class="btn {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted">Listing</th>
                        <th class="text-muted">Category</th>
                        <th class="text-end text-muted">Vacancies</th>
                        <th class="text-end text-muted" style="width: 1%;">Actions</th>
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
                            ], fn ($value) => !is_null($value));
                        @endphp
                        <tr>
                            <td data-label="#" class="text-center">
                                <div class="categories-index-pill">{{ $firstNumber + $index }}</div>
                            </td>
                            <td data-label="Category" class="text-capitalize fw-semibold text-dark">{{ $row->category ?: 'other' }}</td>
                            <td data-label="Vacancies" class="text-end fw-semibold">{{ number_format($row->c) }}</td>
                            <td data-label="Actions" class="text-end">
                                <a href="{{ route('admin.vacancies.by_category', $viewParams) }}" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="feather-eye me-1"></i> View
                                </a>
                            </td>
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
