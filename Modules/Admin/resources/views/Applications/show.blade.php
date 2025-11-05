@extends('admin::components.layouts.master')

@section('content')
    @php
        $candidate = $application->user;
        $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: '—';
        $candidateEmail = $candidate->email ?? '—';
        $candidatePhone = $candidate->phone ?? null;
        $candidateRole = $candidate->role->name ?? 'Member';

        $vacancy = $application->vacancy;
        $resume = $application->resume;

        $status = $application->status ?? 'pending';
        $statusClass = $status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending');
        $matchScore = $application->match_score !== null ? number_format($application->match_score, 1) . '%' : '—';
        $submittedAt = optional($application->submitted_at);
        $submittedFormatted = $submittedAt ? $submittedAt->format('M d, Y H:i') : '—';
        $submittedAgo = $submittedAt ? $submittedAt->diffForHumans() : null;

        $resumeFileUrl = null;
        if ($resume && $resume->file_path) {
            $resumeFileUrl = preg_match('#^(https?:)?//#', $resume->file_path) === 1
                ? $resume->file_path
                : route('admin.resumes.download', $resume->id);
        }
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

        .app-show-hero::before,
        .app-show-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
        }

        .app-show-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.4);
            top: -150px;
            right: -130px;
        }

        .app-show-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
            bottom: -140px;
            left: -120px;
        }

        .app-show-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .app-show-hero__badge {
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

        .app-show-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.2rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .app-show-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .app-show-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .app-show-hero__meta-item a { color: inherit; text-decoration: none; }
        .app-show-hero__meta-item a:hover { text-decoration: underline; }

        .app-show-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .app-show-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .app-show-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .app-show-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .app-show-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .app-show-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .app-show-stat-pill.approved { background: rgba(60, 214, 133, 0.12); color: #25a566; }
        .app-show-stat-pill.rejected { background: rgba(248, 112, 112, 0.14); color: #d65454; }
        .app-show-stat-pill.pending { background: rgba(249, 188, 63, 0.16); color: #ba7c0d; }

        .app-show-sections {
            margin: 1.5rem 1.5rem 2rem;
        }

        .app-show-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .app-show-card .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .app-show-card .card-body {
            padding: 24px 28px;
        }

        .info-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .info-chip {
            padding: 14px 18px;
            border-radius: 18px;
            background: #f4f6ff;
            border: 1px solid rgba(82, 97, 172, 0.12);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-chip .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8a94b8;
        }

        .info-chip .value {
            font-size: 1rem;
            font-weight: 600;
            color: #172655;
            word-break: break-word;
        }

        .info-chip .value a {
            color: inherit;
            text-decoration: none;
        }

        .info-chip .value a:hover {
            text-decoration: underline;
            color: #2140ff;
        }

        .resume-link {
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

        .resume-link:hover { color: #1c36c9; text-decoration: none; }

        @media (max-width: 991px) {
            .app-show-hero { margin: 1.5rem 1rem; padding: 32px; border-radius: 24px; }
            .app-show-sections { margin: 1.5rem 1rem; }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Application</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item">#{{ $application->id }}</li>
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
                <h1 class="app-show-hero__title">{{ $vacancy->title ?? 'Application #' . $application->id }}</h1>
                <div class="app-show-hero__meta">
                    <span class="app-show-hero__meta-item"><i class="feather-user"></i>
                        @if(!empty($candidate?->id))
                            <a href="{{ route('admin.users.show', $candidate->id) }}">{{ $candidateName }}</a>
                        @else
                            {{ $candidateName }}
                        @endif
                    </span>
                    <span class="app-show-hero__meta-item"><i class="feather-mail"></i>{{ $candidateEmail }}</span>
                    <span class="app-show-hero__meta-item"><i class="feather-hash"></i>ID {{ $application->id }}</span>
                    @if($vacancy && $vacancy->company)
                        <span class="app-show-hero__meta-item"><i class="feather-layers"></i>{{ $vacancy->company }}</span>
                    @endif
                </div>
            </div>
            <div class="app-show-stats">
                <div class="app-show-stat-card">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="app-show-stat-pill {{ $statusClass }}">
                            <i class="feather-activity"></i>{{ $status }}
                        </span>
                    </span>
                    <span class="hint">Current application state</span>
                </div>
                <div class="app-show-stat-card">
                    <span class="label">Match score</span>
                    <span class="value">{{ $matchScore }}</span>
                    <span class="hint">Fit against vacancy</span>
                </div>
                <div class="app-show-stat-card">
                    <span class="label">Submitted at</span>
                    <span class="value">{{ $submittedFormatted }}</span>
                    <span class="hint">{{ $submittedAgo ? 'Received ' . $submittedAgo : 'Not submitted yet' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-show-sections">
        <div class="row g-4">
            <div class="col-xl-4 col-lg-6">
                <div class="app-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Candidate</h6></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-text avatar-xl">
                                <img src="/assets/images/avatar/ava.svg" alt="avatar" class="img-fluid">
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">
                                    @if(!empty($candidate?->id))
                                        <a href="{{ route('admin.users.show', $candidate->id) }}" style="color: inherit; text-decoration: none;">{{ $candidateName }}</a>
                                    @else
                                        {{ $candidateName }}
                                    @endif
                                </div>
                                <div class="text-muted small">{{ ucfirst($candidateRole) }}</div>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-chip">
                                <span class="label">Email</span>
                                <span class="value"><a href="mailto:{{ $candidateEmail }}">{{ $candidateEmail }}</a></span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Phone</span>
                                <span class="value">
                                    @if($candidatePhone)
                                        <a href="tel:{{ preg_replace('/\s+/', '', $candidatePhone) }}">+998{{ $candidatePhone }}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-chip">
                                <span class="label">User ID</span>
                                <span class="value">{{ $candidate->id ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Profile updated</span>
                                <span class="value">{{ optional($candidate->updated_at)->format('M d, Y H:i') ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="app-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Application</h6></div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-chip">
                                <span class="label">External ID</span>
                                <span class="value">{{ $application->external_id ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">HH status</span>
                                <span class="value">{{ $application->hh_status ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Submitted</span>
                                <span class="value">{{ $submittedFormatted }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Notes</span>
                                <span class="value">{{ $application->notes ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="app-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Resume</h6></div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-chip">
                                <span class="label">Title</span>
                                <span class="value">{{ $resume->title ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Created</span>
                                <span class="value">{{ optional($resume->created_at)->format('M d, Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Language</span>
                                <span class="value">{{ $resume->analysis->language ?? '—' }}</span>
                            </div>
                        </div>
                        @if($resumeFileUrl)
                            <div class="mt-3">
                                <a class="resume-link" href="{{ $resumeFileUrl }}" target="_blank" rel="noopener">
                                    <i class="feather-download"></i> Open resume
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-6">
                <div class="app-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Vacancy</h6></div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-chip">
                                <span class="label">Title</span>
                                <span class="value">{{ $vacancy->title ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Company</span>
                                <span class="value">{{ $vacancy->company ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Category</span>
                                <span class="value">{{ $vacancy->category ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Language</span>
                                <span class="value">{{ $vacancy->language ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Location</span>
                                <span class="value">{{ $vacancy->location ?? '—' }}</span>
                            </div>
                            <div class="info-chip">
                                <span class="label">Salary</span>
                                <span class="value">{{ $vacancy->salary ?? '—' }}</span>
                            </div>
                        </div>
                        @if($vacancy && $vacancy->apply_url)
                            <div class="mt-4">
                                <a class="btn btn-light-brand" href="{{ $vacancy->apply_url }}" target="_blank" rel="noopener">
                                    <i class="feather-external-link me-1"></i>
                                    Open application page
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
