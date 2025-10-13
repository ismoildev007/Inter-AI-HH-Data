@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all', 'telegram', 'hh'], true)) {
            $currentFilter = 'all';
        }
        $filterLabels = [
            'all' => 'All sources',
            'telegram' => 'Telegram',
            'hh' => 'HeadHunter',
        ];
        $filterLabel = $filterLabels[$currentFilter] ?? ucfirst($currentFilter);
        $searchTerm = $search ?? request('q', '');
        $formRouteParams = array_filter([
            'category' => $categorySlug,
            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
        ], fn ($value) => !is_null($value));
        $indexParams = array_filter([
            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
            'q' => !empty($searchTerm) ? $searchTerm : null,
        ], fn ($value) => !is_null($value));
        $isPaginator = $vacancies instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $vacancies->getCollection() : collect($vacancies);
        $pageCount = $collection->count();
        $totalVacancies = $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $vacancies->total() : $collection->count();
        $latestPublished = optional($collection->filter(fn ($vacancy) => $vacancy->created_at)->max('created_at'));
        $latestFormatted = $latestPublished ? $latestPublished->format('M d, Y') : '—';
    @endphp

    <style>
        .category-vacancies-hero {
            margin: 0 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: linear-gradient(135deg, #1f3ffd, #61a4ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(11, 49, 157, 0.28);
        }

        .category-vacancies-hero::before,
        .category-vacancies-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.2;
        }

        .category-vacancies-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.4);
            top: -150px;
            right: -120px;
        }

        .category-vacancies-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
            bottom: -140px;
            left: -140px;
        }

        .category-vacancies-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .category-vacancies-hero__badge {
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

        .category-vacancies-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .category-vacancies-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }

        .category-vacancies-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .category-vacancies-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .category-vacancies-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .category-vacancies-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .category-vacancies-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .category-vacancies-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .category-vacancies-card {
            margin: 0 1.5rem 2rem;
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .category-vacancies-card .card-header {
            padding: 26px 32px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .category-vacancies-card .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .category-vacancies-card .search-form {
            display: flex;
            flex-grow: 1;
            max-width: 420px;
        }

        .category-vacancies-card .search-form .input-group {
            background: #f5f7ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .category-vacancies-card .search-form input {
            border: none;
            background: transparent;
            padding: 10px 16px;
        }

        .category-vacancies-card .search-form input:focus { box-shadow: none; }

        .category-vacancies-card .table thead th {
            padding: 18px 20px;
            background: rgba(31, 60, 253, 0.08);
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .category-vacancies-card .table tbody td {
            padding: 18px 20px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            vertical-align: middle;
        }

        .category-vacancies-card .table tbody tr:hover {
            background: rgba(86, 134, 255, 0.08);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .category-vacancies-index-pill {
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

        .category-vacancies-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .category-vacancies-pagination {
            padding: 20px 32px 34px;
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .category-vacancies-pagination nav > ul,
        .category-vacancies-pagination nav > div > ul,
        .category-vacancies-pagination nav > div > div > ul,
        .category-vacancies-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .category-vacancies-pagination nav > ul li a,
        .category-vacancies-pagination nav > ul li span,
        .category-vacancies-pagination nav > div > ul li a,
        .category-vacancies-pagination nav > div > ul li span,
        .category-vacancies-pagination nav > div > div > ul li a,
        .category-vacancies-pagination nav > div > div > ul li span,
        .category-vacancies-pagination nav .pagination li a,
        .category-vacancies-pagination nav .pagination li span {
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

        .category-vacancies-pagination nav > ul li a:hover,
        .category-vacancies-pagination nav > div > ul li a:hover,
        .category-vacancies-pagination nav > div > div > ul li a:hover,
        .category-vacancies-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .category-vacancies-pagination nav > ul li span[aria-current="page"],
        .category-vacancies-pagination nav > div > ul li span[aria-current="page"],
        .category-vacancies-pagination nav > div > div > ul li span[aria-current="page"],
        .category-vacancies-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .category-vacancies-pagination nav > ul li:first-child a,
        .category-vacancies-pagination nav > ul li:last-child a,
        .category-vacancies-pagination nav > div > ul li:first-child a,
        .category-vacancies-pagination nav > div > ul li:last-child a,
        .category-vacancies-pagination nav > div > div > ul li:first-child a,
        .category-vacancies-pagination nav > div > div > ul li:last-child a,
        .category-vacancies-pagination nav .pagination li:first-child a,
        .category-vacancies-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .category-vacancies-hero {
                margin-inline: 1rem;
                padding: 32px;
                border-radius: 24px;
            }

            .category-vacancies-card {
                margin-inline: 1rem;
            }

            .category-vacancies-card .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .category-vacancies-card .search-form {
                max-width: 100%;
            }

            .category-vacancies-card .table thead {
                display: none;
            }

            .category-vacancies-card .table tbody tr {
                display: block;
                margin-bottom: 18px;
                border-radius: 20px;
                border: 1px solid rgba(15, 35, 87, 0.08);
                padding: 18px;
                background: #fff;
                transform: none !important;
            }

            .category-vacancies-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border: none;
            }

            .category-vacancies-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .category-vacancies-pagination nav > ul,
            .category-vacancies-pagination nav > div > ul,
            .category-vacancies-pagination nav > div > div > ul,
            .category-vacancies-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .category-vacancies-pagination nav > ul li a,
            .category-vacancies-pagination nav > ul li span,
            .category-vacancies-pagination nav > div > ul li a,
            .category-vacancies-pagination nav > div > ul li span,
            .category-vacancies-pagination nav > div > div > ul li a,
            .category-vacancies-pagination nav > div > div > ul li span,
            .category-vacancies-pagination nav .pagination li a,
            .category-vacancies-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .category-vacancies-pagination nav > ul li:first-child a,
            .category-vacancies-pagination nav > ul li:last-child a,
            .category-vacancies-pagination nav > div > ul li:first-child a,
            .category-vacancies-pagination nav > div > ul li:last-child a,
            .category-vacancies-pagination nav > div > div > ul li:first-child a,
            .category-vacancies-pagination nav > div > div > ul li:last-child a,
            .category-vacancies-pagination nav .pagination li:first-child a,
            .category-vacancies-pagination nav .pagination li:last-child a {
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
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories', $indexParams) }}">Categories</a></li>
                <li class="breadcrumb-item text-capitalize">{{ $category }}</li>
            </ul>
        </div>
    </div>

    <div class="category-vacancies-hero">
        <div class="category-vacancies-hero__content">
            <div>
                <span class="category-vacancies-hero__badge">
                    <i class="feather-briefcase"></i>
                    {{ $filterLabel }}
                </span>
                <h1 class="category-vacancies-hero__title text-capitalize">{{ $category }}</h1>
                <div class="category-vacancies-hero__meta">
                    <span class="category-vacancies-hero__meta-item"><i class="feather-hash"></i>ID {{ $categorySlug }}</span>
                    <span class="category-vacancies-hero__meta-item"><i class="feather-layers"></i>Total vacancies: {{ number_format($count) }}</span>
                </div>
            </div>
            <div class="category-vacancies-stats">
                <div class="category-vacancies-stat-card">
                    <span class="label">Vacancies on this page</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">Current page size</span>
                </div>
                <div class="category-vacancies-stat-card">
                    <span class="label">All vacancies</span>
                    <span class="value">{{ number_format($totalVacancies) }}</span>
                    <span class="hint">Across pagination</span>
                </div>
                <div class="category-vacancies-stat-card">
                    <span class="label">Latest published</span>
                    <span class="value">{{ $latestFormatted }}</span>
                    <span class="hint">Most recent created</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card category-vacancies-card">
        <div class="card-header">
            <div class="filters">
                <div>
                    <h6 class="mb-1">Vacancies list</h6>
                    <span class="text-muted small">Explore titles within this category</span>
                </div>
                <form method="GET" action="{{ route('admin.vacancies.by_category', $formRouteParams) }}" class="search-form">
                    <div class="input-group input-group-sm w-100">
                        <input type="search" name="q" value="{{ $searchTerm }}" class="form-control" placeholder="Search vacancies (title or ID)">
                        @if(!empty($searchTerm))
                            <a href="{{ route('admin.vacancies.by_category', array_filter([
                                    'category' => $categorySlug,
                                    'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                                ], fn ($value) => !is_null($value))) }}" class="btn btn-outline-secondary">
                                <i class="feather-x"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="feather-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted">Listing</th>
                        <th class="text-muted">Title</th>
                        <th class="text-muted">Created</th>
                        <th class="text-end text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $firstNumber = method_exists($vacancies, 'firstItem') ? ($vacancies->firstItem() ?? 1) : 1; @endphp
                    @forelse($vacancies as $index => $vacancy)
                        @php
                            $viewParams = array_filter([
                                'id' => $vacancy->id,
                                'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                            ], fn ($value) => !is_null($value));
                        @endphp
                        <tr>
                            <td data-label="#" class="text-center">
                                <div class="category-vacancies-index-pill">{{ $firstNumber + $index }}</div>
                            </td>
                            <td data-label="Title" class="fw-semibold text-dark">{{ $vacancy->title ?? '—' }}</td>
                            <td data-label="Created" class="text-nowrap">{{ optional($vacancy->created_at)->format('M d, Y') ?? '—' }}</td>
                            <td data-label="Actions" class="text-end">
                                <a href="{{ route('admin.vacancies.show', $viewParams) }}" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="feather-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center category-vacancies-empty">No vacancies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vacancies instanceof \Illuminate\Contracts\Pagination\Paginator || $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="category-vacancies-pagination">
                {{ $vacancies->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
