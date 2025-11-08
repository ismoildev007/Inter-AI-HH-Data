@extends('admin::components.layouts.master')

@section('content')
    @php
        $candidate = $user;
        $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: '—';
        $candidateEmail = $candidate->email ?? '—';
        $totalApps = $applications->count();
        $latestAt = $applications->map(fn($a) => $a->submitted_at ?? $a->created_at)->filter()->max();
        $latestDate = $latestAt ? $latestAt->format('M d, Y H:i') : '—';
        $latestAgo = $latestAt ? $latestAt->diffForHumans() : null;
    @endphp

    <style>
        .app-show-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: linear-gradient(135deg, #1c6dfd, #6d9dff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(23, 71, 173, 0.28);
        }
        .app-show-hero::before, .app-show-hero::after { content: ''; position: absolute; border-radius: 50%; opacity: 0.22; }
        .app-show-hero::before { width: 320px; height: 320px; background: rgba(255,255,255,0.4); top: -150px; right: -130px; }
        .app-show-hero::after { width: 260px; height: 260px; background: rgba(255,255,255,0.24); bottom: -140px; left: -120px; }
        .app-show-hero__content { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 32px; align-items: center; }
        .app-show-hero__badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 999px; background: rgba(255,255,255,0.22); font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase; }
        .app-show-hero__title { margin: 18px 0 0; font-size: clamp(2.2rem, 3vw, 3rem); font-weight: 700; letter-spacing: -0.01em; color: #fff; }
        .app-show-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
        .app-show-stat-card { background: rgba(255,255,255,0.22); border-radius: 20px; padding: 20px 22px; border: 1px solid rgba(255,255,255,0.28); box-shadow: inset 0 1px 0 rgba(255,255,255,0.35); backdrop-filter: blur(8px); }
        .app-show-stat-card .label { display: block; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(255,255,255,0.74); }
        .app-show-stat-card .value { display: block; margin-top: 6px; font-size: 1.8rem; font-weight: 700; }
        .app-show-stat-card .hint { display: block; margin-top: 8px; font-size: 0.82rem; color: rgba(255,255,255,0.7); }
        .app-show-hero__meta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
        .app-show-hero__meta-item { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 12px; background: rgba(255,255,255,0.18); font-size: 0.9rem; font-weight: 500; }

        /* Table styles from Applications index */
        .applications-table-card { margin: 1.5rem 1.5rem 2rem; border: none; border-radius: 26px; box-shadow: 0 28px 58px rgba(24,57,141,0.16); background: linear-gradient(135deg, rgba(248,250,252,0.85), rgba(232,240,255,0.82)); padding: 26px 30px 32px; }
        .applications-table-card .table { margin: 0; border-collapse: separate; border-spacing: 0 14px; }
        .applications-table-card .table thead th { padding: 0 20px 12px; background: transparent; border: none; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: #58618c; }
        .applications-table-card .table tbody tr { cursor: pointer; border-radius: 20px; border: 1px solid rgba(226,232,240,0.9); background: #ffffff; box-shadow: 0 16px 32px rgba(15,23,42,0.06); transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
        .applications-table-card .table tbody tr:hover { border-color: rgba(59,130,246,0.28); box-shadow: 0 22px 44px rgba(59,130,246,0.14); transform: translateY(-3px); }
        .applications-table-card .table tbody td { padding: 18px 22px; border: none; vertical-align: middle; }
        .applications-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; }
        .applications-status--interview { background: rgba(60,214,133,0.12); color: #25a566; }
        .applications-status--responce { background: rgba(249,188,63,0.16); color: #ba7c0d; }
        .applications-status--discard { background: rgba(248,112,112,0.14); color: #d65454; }
        .applications-status--already_applied { background: rgba(249,188,63,0.16); color: #ba7c0d; }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Applications</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item">User #{{ $candidate->id }}</li>
            </ul>
        </div>
    </div>

    <div class="app-show-hero">
        <div class="app-show-hero__content">
            <div>
                <span class="app-show-hero__badge">
                    <i class="feather-briefcase"></i>
                    Candidate match
                </span>
                <h1 class="app-show-hero__title">{{ $candidateName }}</h1>
                <div class="app-show-hero__meta">
                    <!-- <span class="app-show-hero__meta-item"><i class="feather-mail"></i>{{ $candidateEmail }}</span> -->
                    <span class="app-show-hero__meta-item"><i class="feather-hash"></i>User ID {{ $candidate->id }}</span>
                </div>
            </div>
            <div class="app-show-stats">
                <div class="app-show-stat-card">
                    <span class="label">Total applications</span>
                    <span class="value">{{ number_format($totalApps) }}</span>
                    <span class="hint">For this candidate</span>
                </div>
                <div class="app-show-stat-card">
                    <span class="label">Last submission</span>
                    <span class="value">{{ $latestDate }}</span>
                    <span class="hint">{{ $latestAgo ? 'Submitted ' . $latestAgo : 'No recent submissions' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card applications-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted" style="width: 80px;">ID</th>
                        <th class="text-muted">Vacancy</th>
                        <th class="text-muted">Status</th>
                        <th class="text-muted">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $i => $app)
                        <tr onclick="window.location.href='{{ route('admin.applications.show', $app->id) }}'">
                            <td data-label="#" class="align-middle">{{ $i + 1 }}</td>
                            <td data-label="Vacancy">
                                <div class="applications-vacancy">
                                    {{ optional($app->vacancy)->title ?? '—' }}
                                    @if(optional($app->vacancy)->company)
                                        <div class="company" style="display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:4px 10px;background:rgba(64,99,255,0.12);border-radius:999px;font-size:0.78rem;color:#4054c4;">
                                            <i class="feather-briefcase"></i>
                                            {{ $app->vacancy->company }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Status">
                                @php($st = strtolower($app->status ?? ''))
                                @php(
                                    $cls = $st === 'interview' ? 'applications-status--interview'
                                        : ($st === 'responce' ? 'applications-status--responce'
                                        : ($st === 'discard' ? 'applications-status--discard'
                                        : ($st === 'already_applied' ? 'applications-status--already_applied' : 'applications-status--responce')))
                                )
                                <div class="applications-status {{ $cls }}">
                                    <i class="feather-activity"></i>
                                    {{ $st !== '' ? $st : 'responce' }}
                                </div>
                            </td>
                            <td data-label="Submitted">
                                <div class="applications-submitted">
                                    {{ optional($app->submitted_at)->format('M d, Y H:i') ?? '—' }}
                                    @if($app->submitted_at)
                                        <span class="text-muted small">{{ $app->submitted_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No applications found for this user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
