@extends('admin::components.layouts.master')

@section('content')
    @php
        $searchTerm = $search ?? request('q');
        $isPaginator = $applications instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $applications->getCollection() : collect($applications);
        $totalApplications = $applications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $applications->total() : $items->count();
        $latestTimestamp = $items
            ->map(fn ($application) => $application->submitted_at ?? $application->created_at)
            ->filter()
            ->max();
        $latestDate = $latestTimestamp ? $latestTimestamp->format('M d, Y') : '—';
        $latestAgo = $latestTimestamp ? $latestTimestamp->diffForHumans() : null;
    @endphp

    <style>
        .applications-hero { margin: 1.5rem 1.5rem 1.5rem; padding: 42px 46px; border-radius: 26px; background: #ffffff; color: #0f172a; position: relative; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06); }
        .applications-hero::before, .applications-hero::after { content: ''; position: absolute; border-radius: 50%; opacity: 0.2; pointer-events: none; }
        .applications-hero::before { width: 320px; height: 320px; background: rgba(59,130,246,0.18); top: -150px; right: -130px; }
        .applications-hero::after  { width: 260px; height: 260px; background: rgba(96,165,250,0.16); bottom: -140px; left: -110px; }
        .applications-hero-content { display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start; position: relative; z-index: 1; }
        .applications-hero-left { flex: 1 1 320px; }
        .applications-hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 999px; background: #eff6ff; font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 18px; color: #1d4ed8; }
        .applications-stats { flex: 1 1 300px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .applications-stat-card { background: #f8fafc; border-radius: 20px; padding: 20px 22px; border: 1px solid #e2e8f0; }
        .applications-stat-card .label { display: block; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; }
        .applications-stat-card .value { display: block; margin-top: 6px; font-size: 1.9rem; font-weight: 700; color: #0f172a; }
        .applications-stat-card .hint { display: block; margin-top: 8px; font-size: 0.85rem; color: #94a3b8; }

        .applications-filter-card { margin: 1.5rem 1.5rem 1.5rem; border: none; border-radius: 22px; box-shadow: 0 18px 45px rgba(31,68,148,.12); overflow: hidden; }
        .applications-filter-card .card-body { padding: 26px 32px; }
        .applications-filter-header { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .applications-search-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
        .applications-search-form .input-group { flex: 1 1 320px; background: #f1f4ff; border-radius: 16px; padding: 4px; box-shadow: inset 0 1px 0 rgba(255,255,255,.8); }
        .applications-search-form .input-group-text { border: none; background: transparent; color: #4063ff; }
        .applications-search-form .form-control { border: none; background: transparent; padding: 12px 16px; font-size: .95rem; }
        .applications-search-form .form-control:focus { box-shadow: none; }
        .applications-search-form .btn { border-radius: 14px; padding: 10px 20px; font-weight: 600; }
        .applications-search-form .clear-btn { color: #8a96b8; display: inline-flex; align-items: center; gap: 6px; font-size: .88rem; text-decoration: none; }
        .applications-search-form .clear-btn:hover { color: #1f3cfd; }

        .applications-table-card { margin: 1.5rem 1.5rem 2rem; border: none; border-radius: 26px; box-shadow: 0 28px 58px rgba(24,57,141,.16); background: linear-gradient(135deg, rgba(248,250,252,.85), rgba(232,240,255,.82)); padding: 26px 30px 32px; overflow: visible; }
        .applications-table-card .table { margin: 0; border-collapse: separate; border-spacing: 0 14px; }
        .applications-table-card .table thead th { padding: 0 20px 12px; background: transparent; border: none; font-size: .78rem; text-transform: uppercase; letter-spacing: .12em; color: #58618c; }
        .applications-table-card .table tbody tr { cursor: pointer; border-radius: 20px; border: 1px solid rgba(226,232,240,.9); background: #fff; box-shadow: 0 16px 32px rgba(15,23,42,.06); transition: transform .18s ease, box-shadow .2s ease, border-color .2s ease; }
        .applications-table-card .table tbody tr:hover { border-color: rgba(59,130,246,.28); box-shadow: 0 22px 44px rgba(59,130,246,.14); transform: translateY(-3px); }
        .applications-table-card .table tbody td { padding: 18px 22px; border: none; vertical-align: middle; }
        .applications-table-card .table tbody tr td:first-child { border-top-left-radius: 20px; border-bottom-left-radius: 20px; }
        .applications-table-card .table tbody tr td:last-child { border-top-right-radius: 20px; border-bottom-right-radius: 20px; }

        .applications-index-pill { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; background: linear-gradient(135deg, #eff3ff, #d9e1ff); border-radius: 14px; font-weight: 600; font-size: 1rem; color: #1f2f7a; box-shadow: 0 10px 20px rgba(31,51,126,.15), inset 0 1px 0 rgba(255,255,255,.85); }
        .applications-applicant { display: flex; gap: 14px; align-items: center; }
        .applications-applicant .avatar-image { width: 48px; height: 48px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 24px rgba(32,52,122,.18); }
        .applications-applicant .avatar-image img { width: 100%; height: 100%; object-fit: cover; }
        .applications-applicant .name { font-weight: 600; font-size: 1rem; color: #172655; }
        .applications-vacancy { font-weight: 600; color: #1b2f6f; }
        .applications-vacancy .company { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; padding: 4px 10px; background: rgba(64,99,255,.12); border-radius: 999px; font-size: .78rem; color: #4054c4; }
        .applications-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 12px; font-weight: 600; font-size: .85rem; }
        .applications-status--interview { background: rgba(60,214,133,.12); color: #25a566; }
        .applications-submitted { font-size: .9rem; color: #25335f; }
        .applications-submitted span { display: block; font-size: .78rem; color: #8a94b8; }
        .applications-empty { padding: 42px 0; font-size: .95rem; color: #7d88ad; }
        .applications-pagination { padding: 20px 32px 40px; border-top: 1px solid rgba(15,35,87,.06); background: #fff; display: flex; justify-content: center; }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Pipeline</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item">Interview</li>
            </ul>
        </div>
    </div>

    <div class="applications-hero">
        <div class="applications-hero-content">
            <div class="applications-hero-left">
                <span class="applications-hero-badge">
                    <i class="feather-user-check"></i>
                    Interview only
                </span>
                <h1>Interview applications</h1>
                <p>All applications currently in interview status.</p>
            </div>
            <div class="applications-stats">
                <div class="applications-stat-card">
                    <span class="label">Total</span>
                    <span class="value">{{ number_format($totalApplications) }}</span>
                    <span class="hint">Interview status</span>
                </div>
                <div class="applications-stat-card">
                    <span class="label">Last update</span>
                    <span class="value">{{ $latestDate }}</span>
                    <span class="hint">{{ $latestAgo ? 'Updated ' . $latestAgo : '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card applications-filter-card">
        <div class="card-body">
            <div class="applications-filter-header">
                <div>
                    <h6 class="mb-1">Search &amp; filter</h6>
                    <p class="mb-0">Find interview applications by user, vacancy, or email.</p>
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
                    <input type="search" name="q" value="{{ $searchTerm }}" class="form-control" placeholder="Search interview applications">
                    <button type="submit" class="btn btn-primary shadow-sm">Search</button>
                </div>
                @if(!empty($searchTerm))
                    <a href="{{ route('admin.applications.interview') }}" class="clear-btn">
                        <i class="feather-x-circle"></i>
                        Clear search
                    </a>
                @endif
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
                        <th class="text-muted">Status</th>
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
                            <td data-label="Status">
                                <div class="applications-status applications-status--interview">
                                    <i class="feather-activity"></i>
                                    {{ $app->status ?? 'interview' }}
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
                                No interview applications found.
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
