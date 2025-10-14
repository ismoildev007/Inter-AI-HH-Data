@extends('admin::components.layouts.master')

@section('content')
    @php
        $user = $resume->user;
        $ownerName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '—';
        $ownerEmail = $user->email ?? '—';
        $ownerPhone = $user->phone ?? null;
        $ownerRole = $user->role->name ?? 'Member';

        $createdAt = optional($resume->created_at);
        $createdFormatted = $createdAt ? $createdAt->format('M d, Y H:i') : '—';
        $createdAgo = $createdAt ? $createdAt->diffForHumans() : null;
        $updatedAt = optional($resume->updated_at);
        $updatedFormatted = $updatedAt ? $updatedAt->format('M d, Y H:i') : '—';

        $analysis = $resume->analysis;
        $language = $analysis->language ?? '—';

        $fileUrl = null;
        if ($resume->file_path) {
            $fileUrl = preg_match('#^(https?:)?//#', $resume->file_path) === 1
                ? $resume->file_path
                : route('admin.resumes.download', $resume->id);
        }
        $fileMime = $resume->file_mime ?? '—';
        $fileSize = $resume->file_size ? number_format((int) $resume->file_size / 1024, 0) . ' KB' : '—';

        $skills = is_iterable($analysis->skills ?? null) ? $analysis->skills : [];
        $strengths = is_iterable($analysis->strengths ?? null) ? $analysis->strengths : [];
        $weaknesses = is_iterable($analysis->weaknesses ?? null) ? $analysis->weaknesses : [];
        $keywords = is_iterable($analysis->keywords ?? null) ? $analysis->keywords : [];
    @endphp

    <style>
        .resume-show-hero {
            margin: 0 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: linear-gradient(135deg, #1f4af9, #44a1ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(19, 56, 160, 0.28);
        }

        .resume-show-hero::before,
        .resume-show-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
        }

        .resume-show-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.38);
            top: -150px;
            right: -130px;
        }

        .resume-show-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
            bottom: -140px;
            left: -130px;
        }

        .resume-show-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .resume-show-hero__main {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .resume-show-hero__badge {
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

        .resume-show-hero__title {
            margin: 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .resume-show-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 6px;
        }

        .resume-show-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .resume-show-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .resume-show-stat {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .resume-show-stat .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .resume-show-stat .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .resume-show-stat .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .resume-show-sections {
            margin: 0 1.5rem 2rem;
        }

        .resume-show-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .resume-show-card .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .resume-show-card .card-body {
            padding: 24px 28px;
        }

        .summary-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .summary-chip {
            padding: 14px 18px;
            border-radius: 18px;
            background: #f4f6ff;
            border: 1px solid rgba(82, 97, 172, 0.12);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .summary-chip .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8a94b8;
        }

        .summary-chip .value {
            font-size: 1rem;
            font-weight: 600;
            color: #172655;
            word-break: break-word;
        }

        .summary-chip .value a { color: inherit; text-decoration: none; }
        .summary-chip .value a:hover { text-decoration: underline; color: #2140ff; }

        .analysis-block { margin-top: 1.8rem; }
        .analysis-block h6 { font-weight: 600; margin-bottom: 12px; }
        .analysis-block .badge { font-weight: 500; padding: 8px 12px; border-radius: 12px; }
        .analysis-block ul { padding-left: 18px; margin-bottom: 0; }

        .resume-show-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .resume-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f0f3ff, #dae3ff);
            color: #1f2f7a;
            font-weight: 600;
            text-decoration: none;
        }

        .resume-action-btn:hover { color: #1c36c9; text-decoration: none; }

        @media (max-width: 991px) {
            .resume-show-hero { margin-inline: 1rem; padding: 32px; border-radius: 24px; }
            .resume-show-sections { margin-inline: 1rem; }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Resume</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.resumes.index') }}">Resumes</a></li>
                <li class="breadcrumb-item">#{{ $resume->id }}</li>
            </ul>
        </div>

    </div>

    <div class="resume-show-hero">
        <div class="resume-show-hero__content">
            <div class="resume-show-hero__main">
                <span class="resume-show-hero__badge">
                    <i class="feather-file-text"></i>
                    Talent profile
                </span>
                <h1 class="resume-show-hero__title">{{ $resume->title ?? 'Untitled resume' }}</h1>
                <div class="resume-show-hero__meta">
                    <span class="resume-show-chip"><i class="feather-user"></i>{{ $ownerName }}</span>
                    <span class="resume-show-chip"><i class="feather-mail"></i>{{ $ownerEmail }}</span>
                    <span class="resume-show-chip"><i class="feather-hash"></i>Resume ID {{ $resume->id }}</span>
                    <span class="resume-show-chip"><i class="feather-shield"></i>{{ ucfirst($ownerRole) }}</span>
                    @if($ownerPhone)
                        <span class="resume-show-chip"><i class="feather-phone"></i>+998{{ $ownerPhone }}</span>
                    @endif
                    @if($language !== '—')
                        <span class="resume-show-chip"><i class="feather-globe"></i>{{ strtoupper($language) }}</span>
                    @endif
                    @if($resume->is_primary)
                        <span class="resume-show-chip"><i class="feather-star"></i>Primary</span>
                    @endif
                </div>
            </div>
            <div class="resume-show-stats">
                <div class="resume-show-stat">
                    <span class="label">Created</span>
                    <span class="value">{{ $createdFormatted }}</span>
                    <span class="hint">{{ $createdAgo ? 'Submitted ' . $createdAgo : '—' }}</span>
                </div>
                <div class="resume-show-stat">
                    <span class="label">Last update</span>
                    <span class="value">{{ $updatedFormatted }}</span>
                    <span class="hint">Most recent change</span>
                </div>
                <div class="resume-show-stat">
                    <span class="label">File size</span>
                    <span class="value">{{ $fileSize }}</span>
                    <span class="hint">{{ $fileMime }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="resume-show-sections">
        <div class="row g-4">
            <div class="col-xl-4 col-lg-6">
                <div class="resume-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Owner</h6></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-text avatar-xl">
                                <img src="/assets/images/avatar/ava.svg" alt="avatar" class="img-fluid">
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">{{ $ownerName }}</div>
                                <div class="text-muted small">{{ ucfirst($ownerRole) }}</div>
                            </div>
                        </div>
                        <div class="summary-grid">
                            <div class="summary-chip">
                                <span class="label">Email</span>
                                <span class="value"><a href="mailto:{{ $ownerEmail }}">{{ $ownerEmail }}</a></span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Phone</span>
                                <span class="value">
                                    @if($ownerPhone)
                                        <a href="tel:{{ preg_replace('/\s+/', '', $ownerPhone) }}">+998{{ $ownerPhone }}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">User ID</span>
                                <span class="value">{{ $user->id ?? '—' }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Profile updated</span>
                                <span class="value">{{ optional($user->updated_at)->format('M d, Y H:i') ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="resume-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">File</h6></div>
                    <div class="card-body">
                        <div class="summary-grid">
                            <div class="summary-chip">
                                <span class="label">File type</span>
                                <span class="value">{{ $fileMime }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">File size</span>
                                <span class="value">{{ $fileSize }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Created</span>
                                <span class="value">{{ $createdFormatted }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Last update</span>
                                <span class="value">{{ $updatedFormatted }}</span>
                            </div>
                        </div>
                        @if($fileUrl)
                            <div class="resume-show-actions mt-3">
                                <a class="resume-action-btn" href="{{ $fileUrl }}" target="_blank" rel="noopener">
                                    <i class="feather-download"></i> Open resume
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="resume-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Summary</h6></div>
                    <div class="card-body">
                        <div class="summary-grid">
                            <div class="summary-chip">
                                <span class="label">Language</span>
                                <span class="value">{{ $language }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Description</span>
                                <span class="value">{{ $resume->description ?? '—' }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Keywords</span>
                                <span class="value">{{ count($keywords) }}</span>
                            </div>
                            <div class="summary-chip">
                                <span class="label">Skills</span>
                                <span class="value">{{ count($skills) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($skills)
                <div class="col-12 analysis-block">
                    <div class="resume-show-card card">
                        <div class="card-header"><h6 class="mb-0">Skills</h6></div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($skills as $skill)
                                    <span class="badge bg-light text-dark">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($strengths)
                <div class="col-12 analysis-block">
                    <div class="resume-show-card card">
                        <div class="card-header"><h6 class="mb-0">Strengths</h6></div>
                        <div class="card-body">
                            <ul>
                                @foreach($strengths as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if($weaknesses)
                <div class="col-12 analysis-block">
                    <div class="resume-show-card card">
                        <div class="card-header"><h6 class="mb-0">Weaknesses</h6></div>
                        <div class="card-body">
                            <ul>
                                @foreach($weaknesses as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if($keywords)
                <div class="col-12 analysis-block">
                    <div class="resume-show-card card">
                        <div class="card-header"><h6 class="mb-0">Keywords</h6></div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($keywords as $keyword)
                                    <span class="badge bg-light text-dark">{{ $keyword }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
