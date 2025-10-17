@extends('admin::components.layouts.master')

@section('content')
    <style>
        .user-vacancies-hero {
            margin: 0 1.5rem 1.5rem;
            padding: 40px 44px;
            border-radius: 26px;
            background: #ffffff;
            color: #0f172a;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .user-vacancies-hero::before,
        .user-vacancies-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .user-vacancies-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -140px;
            right: -110px;
        }

        .user-vacancies-hero::after {
            width: 240px;
            height: 240px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -130px;
            left: -130px;
        }

        .user-vacancies-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: flex-start;
        }

        .user-vacancies-hero-left {
            flex: 1 1 320px;
        }

        .user-vacancies-hero-badge {
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

        .user-vacancies-hero-left h1 {
            margin: 0 0 12px;
            font-size: clamp(2.1rem, 3vw, 2.8rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .user-vacancies-hero-left p {
            margin: 0 0 20px;
            max-width: 460px;
            line-height: 1.6;
            color: #475569;
        }

        .user-vacancies-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
        }

        .user-vacancies-hero-meta div {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 14px;
            background: #f8fafc;
            font-size: 0.9rem;
            color: #3f4c72;
        }

        .user-vacancies-hero-meta a {
            color: inherit;
            text-decoration: none;
        }

        .user-vacancies-hero-meta a:hover {
            text-decoration: underline;
        }

        .user-vacancies-stats {
            flex: 1 1 280px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .user-vacancies-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .user-vacancies-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .user-vacancies-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .user-vacancies-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .user-vacancies-table-card {
            margin: 0 1.5rem 2rem;
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(21, 37, 97, 0.14);
            overflow: hidden;
        }

        .user-vacancies-table-card .table {
            margin: 0;
        }

        .user-vacancies-table-card .table thead th {
            padding: 18px 20px;
            background: rgba(31, 60, 253, 0.08);
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .user-vacancies-table-card .table tbody td {
            padding: 20px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            vertical-align: middle;
        }

        .user-vacancies-table-card .table tbody tr {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            cursor: pointer;
        }

        .user-vacancies-table-card .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(21, 37, 97, 0.12);
        }

        .user-vacancies-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            box-shadow: 0 15px 30px rgba(38, 91, 255, 0.25);
        }

        .user-vacancies-title .title {
            font-weight: 600;
            font-size: 1rem;
            color: #0f172a;
        }

        .user-vacancies-title .meta {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            font-size: 0.85rem;
            color: #5e6a85;
        }

        .user-vacancies-title .meta .id-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            background: #eef2ff;
            color: #1d4ed8;
            font-weight: 600;
        }

        .user-vacancies-score {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .user-vacancies-score span {
            font-size: 0.82rem;
            color: #64748b;
        }

        .user-vacancies-resumes {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .user-vacancies-resume-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 14px;
            background: #ecf2ff;
            color: #1f3cfd;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .user-vacancies-timestamp {
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .user-vacancies-timestamp span {
            font-size: 0.82rem;
            color: #8090b1;
        }

        .user-vacancies-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1f3cfd;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-vacancies-empty {
            padding: 80px 20px;
            color: #94a3b8;
            font-size: 1rem;
        }

        .user-vacancies-pagination {
            padding: 24px;
            display: flex;
            justify-content: center;
        }

        .user-vacancies-pagination nav > ul,
        .user-vacancies-pagination nav > div > ul,
        .user-vacancies-pagination nav > div > div > ul,
        .user-vacancies-pagination nav .pagination {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 999px;
            box-shadow: 0 18px 36px rgba(26, 44, 104, 0.12);
        }

        .user-vacancies-pagination nav > ul li,
        .user-vacancies-pagination nav > div > ul li,
        .user-vacancies-pagination nav > div > div > ul li,
        .user-vacancies-pagination nav .pagination li {
            list-style: none;
        }

        .user-vacancies-pagination nav > ul li a,
        .user-vacancies-pagination nav > ul li span,
        .user-vacancies-pagination nav > div > ul li a,
        .user-vacancies-pagination nav > div > ul li span,
        .user-vacancies-pagination nav > div > div > ul li a,
        .user-vacancies-pagination nav > div > div > ul li span,
        .user-vacancies-pagination nav .pagination li a,
        .user-vacancies-pagination nav .pagination li span {
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

        .user-vacancies-pagination nav > ul li a:hover,
        .user-vacancies-pagination nav > div > ul li a:hover,
        .user-vacancies-pagination nav > div > div > ul li a:hover,
        .user-vacancies-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .user-vacancies-pagination nav > ul li span[aria-current="page"],
        .user-vacancies-pagination nav > div > ul li span[aria-current="page"],
        .user-vacancies-pagination nav > div > div > ul li span[aria-current="page"],
        .user-vacancies-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .user-vacancies-pagination nav > ul li:first-child a,
        .user-vacancies-pagination nav > ul li:last-child a,
        .user-vacancies-pagination nav > div > ul li:first-child a,
        .user-vacancies-pagination nav > div > ul li:last-child a,
        .user-vacancies-pagination nav > div > div > ul li:first-child a,
        .user-vacancies-pagination nav > div > div > ul li:last-child a,
        .user-vacancies-pagination nav .pagination li:first-child a,
        .user-vacancies-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .user-vacancies-hero {
                margin-inline: 1rem;
                border-radius: 22px;
                padding: 30px;
            }

            .user-vacancies-table-card {
                margin-inline: 1rem;
            }

            .user-vacancies-table-card .table thead {
                display: none;
            }

            .user-vacancies-table-card .table tbody tr {
                display: block;
                border-radius: 20px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid rgba(15, 35, 87, 0.08);
                background: #fff;
                transform: none !important;
            }

            .user-vacancies-table-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
            }

            .user-vacancies-table-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .user-vacancies-table-card .table tbody td:first-child {
                display: block;
                margin-bottom: 12px;
            }

            .user-vacancies-table-card .table tbody td:first-child::before {
                content: '';
            }

            .user-vacancies-action {
                justify-content: flex-start;
            }

            .user-vacancies-pagination nav > ul,
            .user-vacancies-pagination nav > div > ul,
            .user-vacancies-pagination nav > div > div > ul,
            .user-vacancies-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .user-vacancies-pagination nav > ul li a,
            .user-vacancies-pagination nav > ul li span,
            .user-vacancies-pagination nav > div > ul li a,
            .user-vacancies-pagination nav > div > ul li span,
            .user-vacancies-pagination nav > div > div > ul li a,
            .user-vacancies-pagination nav > div > div > ul li span,
            .user-vacancies-pagination nav .pagination li a,
            .user-vacancies-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .user-vacancies-pagination nav > ul li:first-child a,
            .user-vacancies-pagination nav > ul li:last-child a,
            .user-vacancies-pagination nav > div > ul li:first-child a,
            .user-vacancies-pagination nav > div > ul li:last-child a,
            .user-vacancies-pagination nav > div > div > ul li:first-child a,
            .user-vacancies-pagination nav > div > div > ul li:last-child a,
            .user-vacancies-pagination nav .pagination li:first-child a,
            .user-vacancies-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }
        }
    </style>

    @php
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $displayName = $fullName !== '' ? $fullName : 'User #' . $user->id;
        $totalVacancies = $vacancies->total();
        $pageCount = $vacancies->count();
        $firstListingNumber = method_exists($vacancies, 'firstItem') ? ($vacancies->firstItem() ?? 1) : 1;

        $latestMatchRecord = $matchSummaries
            ->map(fn ($summary) => $summary['latest_match'] ?? null)
            ->filter()
            ->sortByDesc(fn ($match) => optional($match->created_at ?? $match->updated_at)->timestamp ?? 0)
            ->first();
        $latestMatchMoment = $latestMatchRecord ? ($latestMatchRecord->created_at ?? $latestMatchRecord->updated_at) : null;
        $latestMatchFormatted = $latestMatchMoment ? $latestMatchMoment->format('M d, Y H:i') : '—';
        $latestMatchAgo = $latestMatchMoment ? $latestMatchMoment->diffForHumans() : null;

        $bestScoreValue = $matchSummaries
            ->map(fn ($summary) => optional($summary['best_match'])->score_percent)
            ->filter()
            ->max();
        $bestScoreDisplay = is_null($bestScoreValue) ? '—' : number_format((float) $bestScoreValue, 2) . '%';
        $telegramCount = (int) ($sourceTotals['telegram'] ?? 0);
        $hhCount = (int) ($sourceTotals['hh'] ?? 0);
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Matches</h5>
                
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->id }}</a></li>
                <li class="breadcrumb-item active">Vacancies</li>
            </ul>
        </div>

    </div>

    <div class="user-vacancies-hero">
        <div class="user-vacancies-hero-content">
            <div class="user-vacancies-hero-left">
                <span class="user-vacancies-hero-badge">
                    <i class="feather-briefcase"></i>
                    Vacancy matches
                </span>
                <h1>Matched vacancies for {{ $displayName }}</h1>
                <p>Every vacancy that this user has been paired with through Match Results. Select any row to open the
                    admin vacancy details screen.</p>
                <div class="user-vacancies-hero-meta">
                    <div>
                        <i class="feather-hash"></i>
                        <span>User ID: #{{ $user->id }}</span>
                    </div>
                    @if($user->email)
                        <div>
                            <i class="feather-mail"></i>
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </div>
                    @endif
                    @if($user->phone)
                        <div>
                            <i class="feather-phone"></i>
                            +998{{ $user->phone }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="user-vacancies-stats">
                <div class="user-vacancies-stat-card">
                    <span class="label">Total matched</span>
                    <span class="value">{{ number_format($totalVacancies) }}</span>
                    <span class="hint">Across all pages</span>
                </div>
                <div class="user-vacancies-stat-card">
                    <span class="label">Currently showing</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">Vacancies on this page</span>
                </div>
                <div class="user-vacancies-stat-card">
                    <span class="label">Top match score</span>
                    <span class="value">{{ $bestScoreDisplay }}</span>
                    <span class="hint">Highest percentage recorded</span>
                </div>
                <div class="user-vacancies-stat-card">
                    <span class="label">Last matched</span>
                    <span class="value">{{ $latestMatchFormatted }}</span>
                    <span class="hint">{{ $latestMatchAgo ? 'Updated ' . $latestMatchAgo : 'No matches yet' }}</span>
                </div>
                <div class="user-vacancies-stat-card">
                    <span class="label">Telegram sourced</span>
                    <span class="value">{{ number_format($telegramCount) }}</span>
                    <span class="hint">Matches originating from Telegram</span>
                </div>
                <div class="user-vacancies-stat-card">
                    <span class="label">HH sourced</span>
                    <span class="value">{{ number_format($hhCount) }}</span>
                    <span class="hint">Matches originating from hh.uz</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card user-vacancies-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">Vacancy</th>
                        <th class="text-muted">Best match</th>
                        <th class="text-muted">Resumes matched</th>
                        <th class="text-muted">Last matched</th>
                        <th class="text-muted text-end" style="width: 120px;">Open</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vacancies as $vacancy)
                        @php
                            $summary = $matchSummaries->get($vacancy->id);
                            $bestMatch = $summary['best_match'] ?? null;
                            $latestMatch = $summary['latest_match'] ?? $bestMatch;
                            $bestScore = $bestMatch?->score_percent;
                            $latestMatchedAt = $latestMatch?->created_at ?? $latestMatch?->updated_at;
                            $resumeTitles = collect($summary['resume_titles'] ?? [])->filter()->all();
                        @endphp
                        <tr onclick="window.location.href='{{ route('admin.vacancies.show', ['id' => $vacancy->id]) }}'">
                            <td data-label="#" class="text-center align-middle">
                                <div class="user-vacancies-index-pill">
                                    {{ $firstListingNumber + $loop->index }}
                                </div>
                            </td>
                            <td data-label="Vacancy">
                                <div class="user-vacancies-title">
                                    <div class="title">{{ $vacancy->title ?? '—' }}</div>
                                    <div class="meta">
                                        <span class="id-pill">#{{ $vacancy->id }}</span>
                                        <span class="source">{{ ucfirst($vacancy->source ?? 'unknown') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Best match">
                                @if(!is_null($bestScore))
                                    <div class="user-vacancies-score">
                                        <strong>{{ number_format((float) $bestScore, 2) }}%</strong>
                                        @if($bestMatch?->resume?->title)
                                            <span>via "{{ \Illuminate\Support\Str::limit($bestMatch->resume->title, 40) }}"</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Resumes">
                                @if(!empty($resumeTitles))
                                    <div class="user-vacancies-resumes">
                                        @foreach($resumeTitles as $title)
                                            <span class="user-vacancies-resume-pill">{{ \Illuminate\Support\Str::limit($title, 32) }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Last matched">
                                @if($latestMatchedAt)
                                    <div class="user-vacancies-timestamp">
                                        {{ $latestMatchedAt->format('M d, Y H:i') }}
                                        <span>{{ $latestMatchedAt->diffForHumans() }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Open" class="text-end">
                                <div class="user-vacancies-action">
                                    View
                                    <i class="feather-arrow-up-right"></i>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center user-vacancies-empty">
                                No vacancies have been matched to this user yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vacancies->hasPages())
            <div class="user-vacancies-pagination">
                {{ $vacancies->withQueryString()->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
