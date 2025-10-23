@extends('admin::components.layouts.master')

@section('content')
    <style>
        .applications-hero {
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

        .applications-hero::before,
        .applications-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.2;
            pointer-events: none;
        }

        .applications-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -150px;
            right: -130px;
        }

        .applications-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -140px;
            left: -110px;
        }

        .applications-hero-content {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }

        .applications-hero-left {
            flex: 1 1 320px;
        }

        .applications-hero-badge {
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

        .applications-hero-left h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .applications-hero-left p {
            margin: 0;
            max-width: 440px;
            line-height: 1.6;
            color: #475569;
        }

        .applications-stats {
            flex: 1 1 300px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .applications-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .applications-stat-card .label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .applications-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .applications-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .applications-filter-card {
            margin: 1.5rem 1.5rem 1.5rem;
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(31, 68, 148, 0.12);
            overflow: hidden;
        }

        .applications-filter-card .card-body {
            padding: 26px 32px;
        }

        .applications-filter-header {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .applications-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .applications-search-form .input-group {
            flex: 1 1 320px;
            background: #f1f4ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .applications-search-form .input-group-text {
            border: none;
            background: transparent;
            color: #4063ff;
        }

        .applications-search-form .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .applications-search-form .form-control:focus {
            box-shadow: none;
        }

        .applications-search-form .btn {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .applications-search-form .clear-btn {
            color: #8a96b8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .applications-search-form .clear-btn:hover {
            color: #1f3cfd;
        }

        .applications-table-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(24, 57, 141, 0.16);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.82));
            padding: 26px 30px 32px;
            overflow: visible;
        }

        .applications-table-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .applications-table-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .applications-table-card .table tbody tr {
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .applications-table-card .table tbody tr:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .applications-table-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .applications-table-card .table tbody tr td:first-child {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .applications-table-card .table tbody tr td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .applications-index-pill {
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

        .applications-applicant {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .applications-applicant .avatar-image {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(32, 52, 122, 0.18);
        }

        .applications-applicant .avatar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .applications-applicant .name {
            font-weight: 600;
            font-size: 1rem;
            color: #172655;
        }

        .applications-applicant .meta {
            font-size: 0.85rem;
            color: #707a9f;
        }

        .applications-vacancy {
            font-weight: 600;
            color: #1b2f6f;
        }

        .applications-vacancy .company {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            padding: 4px 10px;
            background: rgba(64, 99, 255, 0.12);
            border-radius: 999px;
            font-size: 0.78rem;
            color: #4054c4;
        }

        .applications-resume {
            font-size: 0.95rem;
            color: #1d2f63;
        }

        .applications-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .applications-status--approved {
            background: rgba(60, 214, 133, 0.12);
            color: #25a566;
        }

        .applications-status--rejected {
            background: rgba(248, 112, 112, 0.14);
            color: #d65454;
        }

        .applications-status--pending {
            background: rgba(249, 188, 63, 0.16);
            color: #ba7c0d;
        }

        .applications-match {
            font-weight: 600;
            color: #2140a5;
        }

        .applications-match span {
            display: inline-block;
            margin-top: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #9aa4c9;
        }

        .applications-submitted {
            font-size: 0.9rem;
            color: #25335f;
        }

        .applications-submitted span {
            display: block;
            font-size: 0.78rem;
            color: #8a94b8;
        }

        .applications-action .btn {
            border-radius: 999px;
            padding-inline: 18px;
            font-weight: 600;
        }

        .applications-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .applications-pagination {
            padding: 20px 32px 40px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .applications-pagination nav > ul,
        .applications-pagination nav > div > ul,
        .applications-pagination nav > div > div > ul,
        .applications-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .applications-pagination nav > ul li a,
        .applications-pagination nav > ul li span,
        .applications-pagination nav > div > ul li a,
        .applications-pagination nav > div > ul li span,
        .applications-pagination nav > div > div > ul li a,
        .applications-pagination nav > div > div > ul li span,
        .applications-pagination nav .pagination li a,
        .applications-pagination nav .pagination li span {
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

        .applications-pagination nav > ul li a:hover,
        .applications-pagination nav > div > ul li a:hover,
        .applications-pagination nav > div > div > ul li a:hover,
        .applications-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .applications-pagination nav > ul li span[aria-current="page"],
        .applications-pagination nav > div > ul li span[aria-current="page"],
        .applications-pagination nav > div > div > ul li span[aria-current="page"],
        .applications-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .applications-pagination nav > ul li:first-child a,
        .applications-pagination nav > ul li:last-child a,
        .applications-pagination nav > div > ul li:first-child a,
        .applications-pagination nav > div > ul li:last-child a,
        .applications-pagination nav > div > div > ul li:first-child a,
        .applications-pagination nav > div > div > ul li:last-child a,
        .applications-pagination nav .pagination li:first-child a,
        .applications-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .applications-hero {
                margin: 1.5rem 1rem;
                border-radius: 22px;
                padding: 30px;
            }

            .applications-filter-card,
            .applications-table-card {
                margin: 1.5rem 1rem;
                padding: 24px 20px 26px;
            }

            .applications-table-card .table {
                border-spacing: 0;
            }

            .applications-table-card .table thead {
                display: none;
            }

            .applications-table-card .table tbody tr {
                display: block;
                border-radius: 20px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                transform: none !important;
            }

            .applications-table-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
            }

            .applications-pagination nav > ul,
            .applications-pagination nav > div > ul,
            .applications-pagination nav > div > div > ul,
            .applications-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .applications-pagination nav > ul li a,
            .applications-pagination nav > ul li span,
            .applications-pagination nav > div > ul li a,
            .applications-pagination nav > div > ul li span,
            .applications-pagination nav > div > div > ul li a,
            .applications-pagination nav > div > div > ul li span,
            .applications-pagination nav .pagination li a,
            .applications-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .applications-pagination nav > ul li:first-child a,
            .applications-pagination nav > ul li:last-child a,
            .applications-pagination nav > div > ul li:first-child a,
            .applications-pagination nav > div > ul li:last-child a,
            .applications-pagination nav > div > div > ul li:first-child a,
            .applications-pagination nav > div > div > ul li:last-child a,
            .applications-pagination nav .pagination li:first-child a,
            .applications-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }

            .applications-table-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .applications-pagination nav > ul {
                gap: 6px;
                padding: 8px 10px;
            }

            .applications-pagination nav > ul li a,
            .applications-pagination nav > ul li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .applications-pagination nav > ul li:first-child a,
            .applications-pagination nav > ul li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }

            .applications-table-card .table tbody td:first-child {
                display: block;
                margin-bottom: 12px;
            }

            .applications-table-card .table tbody td:first-child::before {
                content: '';
            }

            .applications-action {
                justify-content: flex-start;
            }
        }
    </style>

    @php
        $searchTerm = $search ?? request('q');
        $isPaginator = $applications instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $applications->getCollection() : collect($applications);
        $totalApplications = $applications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $applications->total() : $items->count();
        $pageCount = $items->count();
        $latestTimestamp = $items
            ->map(fn ($application) => $application->submitted_at ?? $application->created_at)
            ->filter()
            ->max();
        $latestDate = $latestTimestamp ? $latestTimestamp->format('M d, Y') : '—';
        $latestAgo = $latestTimestamp ? $latestTimestamp->diffForHumans() : null;
        $approvedCount = $items->filter(fn ($application) => $application->status === 'approved')->count();
        $approvalRate = $pageCount > 0 ? round(($approvedCount / max($pageCount, 1)) * 100) : null;
        $matchAvg = $items
            ->filter(fn ($application) => $application->match_score !== null)
            ->avg('match_score');
        $matchAvgDisplay = $matchAvg !== null ? number_format($matchAvg, 1) . '%' : '—';
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Pipeline</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Applications</li>
            </ul>
        </div>
    </div>

    <div class="applications-hero">
        <div class="applications-hero-content">
            <div class="applications-hero-left">
                <span class="applications-hero-badge">
                    <i class="feather-briefcase"></i>
                    Hiring overview
                </span>
                <h1>Applications pipeline</h1>
                <p>Monitor every candidate across vacancies, track their progress, and follow up on the strongest
                    matches instantly.</p>
            </div>
            <div class="applications-stats">
                <div class="applications-stat-card">
                    <span class="label">Total applications</span>
                    <span class="value">{{ number_format($totalApplications) }}</span>
                    <span class="hint">Across the entire platform</span>
                </div>
                <div class="applications-stat-card">
                    <span class="label">Currently showing</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">On this page</span>
                </div>
                <div class="applications-stat-card">
                    <span class="label">Last submission</span>
                    <span class="value">{{ $latestDate }}</span>
                    <span class="hint">{{ $latestAgo ? 'Submitted ' . $latestAgo : 'No recent submissions' }}</span>
                </div>
                <div class="applications-stat-card">
                    <span class="label">Approval rate</span>
                    <span class="value">{{ $approvalRate !== null ? $approvalRate . '%' : '—' }}</span>
                    <span class="hint">On this page</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card applications-filter-card">
        <div class="card-body">
            <div class="applications-filter-header">
                <div>
                    <h6 class="mb-1">Search &amp; filter</h6>
                    <p class="mb-0">Find candidates using their name, email address, or target vacancy.</p>
                </div>
                @if($searchTerm)
                    <div class="text-muted small">Showing results for “{{ $searchTerm }}”</div>
                @endif
            </div>
            <form method="GET" class="applications-search-form">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="feather-search"></i>
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchTerm }}"
                        class="form-control"
                        placeholder="Search applications (user, vacancy, resume)">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        Search
                    </button>
                </div>
                @if(!empty($searchTerm))
                    <a href="{{ route('admin.applications.index') }}" class="clear-btn">
                        <i class="feather-x-circle"></i>
                        Clear search
                    </a>
                @endif
                <div class="text-muted small w-100 mt-2">
                    Average match score this page: <strong>{{ $matchAvgDisplay }}</strong>
                </div>
            </form>
        </div>
    </div>

    <div class="card applications-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">Candidate</th>
                        <th class="text-muted">Vacancy</th>
                        <th class="text-muted">Resume</th>
                        <th class="text-muted">Status</th>
                        <th class="text-muted">Match</th>
                        <th class="text-muted">Submitted</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr onclick="window.location.href='{{ route('admin.applications.show', $app->id) }}'">
                            <td data-label="#" class="text-center align-middle">
                                <div class="applications-index-pill">
                                    {{ (method_exists($applications, 'firstItem') ? ($applications->firstItem() ?? 1) : 1) + $loop->index }}
                                </div>
                            </td>
                            <td data-label="Candidate">
                                <div class="applications-applicant">
                                    <div class="avatar-image">
                                        <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                                    </div>
                                    <div>
                                        <div class="name">{{ trim((optional($app->user)->first_name ?? '').' '.(optional($app->user)->last_name ?? '')) ?: '—' }}</div>
                                        <div class="meta">{{ optional($app->user)->email ?? 'No email' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Vacancy">
                                <div class="applications-vacancy">
                                    {{ optional($app->vacancy)->title ?? '—' }}
                                    @if(optional($app->vacancy)->company)
                                        <div class="company">
                                            <i class="feather-briefcase"></i>
                                            {{ $app->vacancy->company }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Resume">
                                <div class="applications-resume">
                                    {{ optional($app->resume)->title ?? '—' }}
                                </div>
                            </td>
                            <td data-label="Status">
                                @php($st = $app->status)
                                <div class="applications-status
                                    {{ $st === 'approved' ? 'applications-status--approved' : ($st === 'rejected' ? 'applications-status--rejected' : 'applications-status--pending') }}">
                                    <i class="feather-activity"></i>
                                    {{ $st ?? 'pending' }}
                                </div>
                            </td>
                            <td data-label="Match">
                                <div class="applications-match">
                                    {{ $app->match_score !== null ? number_format($app->match_score, 0) . '%' : '—' }}
                                    <span>Fit score</span>
                                </div>
                            </td>
                            <td data-label="Submitted">
                                <div class="applications-submitted">
                                    {{ optional($app->submitted_at)->format('M d, Y H:i') ?? '—' }}
                                    @if($app->submitted_at)
                                        <span>{{ $app->submitted_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>
      
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center applications-empty">
                                No applications found. Try adjusting your filters or search keywords.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applications instanceof \Illuminate\Contracts\Pagination\Paginator || $applications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="applications-pagination">
                {{ $applications->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
