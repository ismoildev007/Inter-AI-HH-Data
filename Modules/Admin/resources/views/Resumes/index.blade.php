@extends('admin::components.layouts.master')

@section('content')
    <style>
        .resumes-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 40px 44px;
            border-radius: 26px;
            background: #ffffff;
            color: #0f172a;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .resumes-hero::before,
        .resumes-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .resumes-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -140px;
            right: -110px;
        }

        .resumes-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -130px;
            left: -140px;
        }

        .resumes-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: flex-start;
        }

        .resumes-hero-left {
            flex: 1 1 320px;
        }

        .resumes-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: #eff6ff;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 18px;
            color: #1d4ed8;
        }

        .resumes-hero-left h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 3vw, 2.8rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .resumes-hero-left p {
            margin: 0;
            max-width: 440px;
            line-height: 1.6;
            color: #475569;
        }

        /* Action stat card (clickable) */
        .resumes-stat-card--action {
            display: block;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .resumes-stat-card--action:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-2px);
        }

        .resumes-stats {
            flex: 1 1 300px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .resumes-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .resumes-stat-card .label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .resumes-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .resumes-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .resumes-filter-card {
            margin: 1.5rem 1.5rem 1.5rem;
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(25, 58, 142, 0.12);
            overflow: hidden;
        }

        .resumes-filter-card .card-body {
            padding: 26px 32px;
        }

        .resumes-filter-header {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .resumes-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .resumes-search-form .input-group {
            flex: 1 1 320px;
            background: #f1f4ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .resumes-search-form .input-group-text {
            border: none;
            background: transparent;
            color: #2d5bff;
        }

        .resumes-search-form .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .resumes-search-form .form-control:focus {
            box-shadow: none;
        }

        .resumes-search-form .btn {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .resumes-search-form .clear-btn {
            color: #8a96b8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .resumes-table-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(19, 48, 132, 0.16);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.82));
            padding: 26px 30px 32px;
            overflow: visible;
        }

        .resumes-table-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .resumes-table-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .resumes-table-card .table tbody tr {
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .resumes-table-card .table tbody tr:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .resumes-table-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .resumes-table-card .table tbody tr td:first-child {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .resumes-table-card .table tbody tr td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .resumes-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            color: #1f2f7a;
            box-shadow: 0 10px 20px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .resumes-title {
            font-weight: 600;
            color: #172655;
        }

        .resumes-title .badge {
            margin-left: 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            padding: 5px 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .resumes-owner {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .resumes-owner .avatar-image {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(32, 52, 122, 0.18);
        }

        .resumes-owner .avatar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .resumes-owner .name {
            font-weight: 600;
            font-size: 1rem;
            color: #172655;
        }

        .resumes-owner .meta {
            font-size: 0.85rem;
            color: #707a9f;
        }

        .resumes-created {
            font-size: 0.9rem;
            color: #25335f;
        }

        .resumes-created span {
            display: block;
            font-size: 0.78rem;
            color: #8a94b8;
        }

        .resumes-action .btn {
            border-radius: 999px;
            padding-inline: 18px;
            font-weight: 600;
        }

        .resumes-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .resumes-pagination {
            padding: 20px 32px 40px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .resumes-pagination nav > ul,
        .resumes-pagination nav > div > ul,
        .resumes-pagination nav > div > div > ul,
        .resumes-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .resumes-pagination nav > ul li a,
        .resumes-pagination nav > ul li span,
        .resumes-pagination nav > div > ul li a,
        .resumes-pagination nav > div > ul li span,
        .resumes-pagination nav > div > div > ul li a,
        .resumes-pagination nav > div > div > ul li span,
        .resumes-pagination nav .pagination li a,
        .resumes-pagination nav .pagination li span {
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

        .resumes-pagination nav > ul li a:hover,
        .resumes-pagination nav > div > ul li a:hover,
        .resumes-pagination nav > div > div > ul li a:hover,
        .resumes-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .resumes-pagination nav > ul li span[aria-current="page"],
        .resumes-pagination nav > div > ul li span[aria-current="page"],
        .resumes-pagination nav > div > div > ul li span[aria-current="page"],
        .resumes-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .resumes-pagination nav > ul li:first-child a,
        .resumes-pagination nav > ul li:last-child a,
        .resumes-pagination nav > div > ul li:first-child a,
        .resumes-pagination nav > div > ul li:last-child a,
        .resumes-pagination nav > div > div > ul li:first-child a,
        .resumes-pagination nav > div > div > ul li:last-child a,
        .resumes-pagination nav .pagination li:first-child a,
        .resumes-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .resumes-hero {
                margin: 1.5rem 1rem;
                border-radius: 22px;
                padding: 30px;
            }

            .resumes-filter-card,
            .resumes-table-card {
                margin: 1.5rem 1rem;
                padding: 24px 20px 26px;
            }

            .resumes-table-card .table {
                border-spacing: 0;
            }

            .resumes-table-card .table thead {
                display: none;
            }

            .resumes-table-card .table tbody tr {
                display: block;
                border-radius: 20px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                transform: none !important;
            }

            .resumes-table-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
            }

            .resumes-table-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .resumes-pagination nav > ul,
            .resumes-pagination nav > div > ul,
            .resumes-pagination nav > div > div > ul,
            .resumes-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .resumes-pagination nav > ul li a,
            .resumes-pagination nav > ul li span,
            .resumes-pagination nav > div > ul li a,
            .resumes-pagination nav > div > ul li span,
            .resumes-pagination nav > div > div > ul li a,
            .resumes-pagination nav > div > div > ul li span,
            .resumes-pagination nav .pagination li a,
            .resumes-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .resumes-pagination nav > ul li:first-child a,
            .resumes-pagination nav > ul li:last-child a,
            .resumes-pagination nav > div > ul li:first-child a,
            .resumes-pagination nav > div > ul li:last-child a,
            .resumes-pagination nav > div > div > ul li:first-child a,
            .resumes-pagination nav > div > div > ul li:last-child a,
            .resumes-pagination nav .pagination li:first-child a,
            .resumes-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }

            .resumes-table-card .table tbody td:first-child {
                display: block;
                margin-bottom: 12px;
            }

            .resumes-table-card .table tbody td:first-child::before {
                content: '';
            }

            .resumes-action {
                justify-content: flex-start;
            }
        }
    </style>

    @php
        $searchTerm = $search ?? request('q');
        $isPaginator = $resumes instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $resumes->getCollection() : collect($resumes);
        $totalResumes = $resumes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $resumes->total() : $items->count();
        $pageCount = $items->count();
        $primaryCount = $items->filter(fn ($resume) => (bool) $resume->is_primary)->count();
        $uniqueAuthors = $items
            ->map(fn ($resume) => optional($resume->user)->id ?? $resume->user_id ?? null)
            ->filter()
            ->unique()
            ->count();
        $latestTimestamp = $items
            ->map(fn ($resume) => $resume->updated_at ?? $resume->created_at)
            ->filter()
            ->max();
        $latestDate = $latestTimestamp ? $latestTimestamp->format('M d, Y') : '—';
        $latestAgo = $latestTimestamp ? $latestTimestamp->diffForHumans() : null;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Library</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Resumes</li>
            </ul>
        </div>
    </div>

    <div class="resumes-hero">
        <div class="resumes-hero-content">
            <div class="resumes-hero-left">
                <span class="resumes-hero-badge">
                    <i class="feather-file-text"></i>
                    Talent archive
                </span>
                <h1>Resumes catalogue</h1>
                <p>Track every saved profile, highlight primary resumes, and connect candidates with the right
                    opportunities in seconds.</p>
                
            </div>
            <div class="resumes-stats">
                <div class="resumes-stat-card">
                    <span class="label">Total resumes</span>
                    <span class="value">{{ number_format($totalResumes) }}</span>
                    <span class="hint">Across the entire platform</span>
                </div>
                <!-- <div class="resumes-stat-card">
                    <span class="label">Currently showing</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">On this page</span>
                </div> -->
                <!-- <div class="resumes-stat-card">
                    <span class="label">Primary resumes</span>
                    <span class="value">{{ number_format($primaryCount) }}</span>
                    <span class="hint">Marked as primary</span>
                </div> -->
                <div class="resumes-stat-card">
                    <span class="label">Unique authors</span>
                    <span class="value">{{ number_format($uniqueAuthors) }}</span>
                    <span class="hint">Represented on this page</span>
                </div>
                <a href="{{ route('admin.resumes.categories') }}" class="resumes-stat-card resumes-stat-card--action">
                    <span class="label">Browse</span>
                    <span class="value" style="display:flex;align-items:center;gap:8px;">
                         Categories
                    </span>
                    <span class="hint">Open categories list</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card resumes-filter-card">
        <div class="card-body">
            <div class="resumes-filter-header">
                <div>
                    <h6 class="mb-1">Search &amp; filter</h6>
                    <p class="mb-0">Search resume titles to quickly locate specific candidate profiles.</p>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    @if($searchTerm)
                        <div class="text-muted small">Showing results for “{{ $searchTerm }}”</div>
                    @endif
                    <a href="{{ route('admin.resumes.download_all') }}" class="btn btn-outline-primary shadow-sm">
                        <i class="feather-download-cloud"></i>
                        Download All (ZIP)
                    </a>
                </div>
            </div>
            <form method="GET" class="resumes-search-form">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="feather-search"></i>
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchTerm }}"
                        class="form-control"
                        placeholder="Search resumes by title">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        Search
                    </button>
                </div>
                @if(!empty($searchTerm))
                    <a href="{{ route('admin.resumes.index') }}" class="clear-btn">
                        <i class="feather-x-circle"></i>
                        Clear search
                    </a>
                @endif
                <div class="text-muted small w-100 mt-2">
                    Last update on this page: <strong>{{ $latestDate }}</strong> {{ $latestAgo ? '(' . $latestAgo . ')' : '' }}
                </div>
            </form>
        </div>
    </div>

    <div class="card resumes-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">Resume</th>
                        <th class="text-muted">Owner</th>
                        <th class="text-muted">Created</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumes as $r)
                        <tr onclick="window.location.href='{{ route('admin.resumes.show', $r->id) }}'">
                            <td data-label="#" class="text-center align-middle">
                                <div class="resumes-index-pill">
                                    {{ (method_exists($resumes, 'firstItem') ? ($resumes->firstItem() ?? 1) : 1) + $loop->index }}
                                </div>
                            </td>
                            <td data-label="Resume">
                                <div class="resumes-title">
                                    {{ $r->title ?? '—' }}
                                    @if($r->is_primary)
                                        <span class="badge bg-light text-primary border border-primary">Primary</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Owner">
                                <div class="resumes-owner">
                                    <div class="avatar-image">
                                        <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                                    </div>
                                    <div>
                                        <div class="name">{{ trim((optional($r->user)->first_name ?? '').' '.(optional($r->user)->last_name ?? '')) ?: '—' }}</div>
                                        <!-- <div class="meta">{{ optional($r->user)->email ?? 'No email' }}</div> -->
                                    </div>
                                </div>
                            </td>
                            <td data-label="Created">
                                <div class="resumes-created">
                                    {{ optional($r->created_at)->format('M d, Y H:i') ?? '—' }}
                                    @if($r->created_at)
                                        <span>{{ $r->created_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center resumes-empty">
                                No resumes found. Try adjusting your filters or search keywords.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($resumes instanceof \Illuminate\Contracts\Pagination\Paginator || $resumes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="resumes-pagination">
                {{ $resumes->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
