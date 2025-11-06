@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all','telegram','hh','archived'], true)) {
            $currentFilter = 'all';
        }
        $categoryIndexParams = $currentFilter === 'all' ? [] : ['filter' => $currentFilter];
        $categoryRouteParams = array_filter([
            'category' => $categorySlug ?? 'other',
            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
        ], fn ($value) => !is_null($value));

        $isPublished = in_array($vacancy->status, [\App\Models\Vacancy::STATUS_PUBLISH, 'published'], true);
        $isArchived = in_array($vacancy->status, [\App\Models\Vacancy::STATUS_ARCHIVE, 'archived'], true);
        $statusLabel = ucfirst($vacancy->status ?? 'unknown');
        $contact = (array) ($vacancy->contact ?? []);
        $contactPhones = (array) ($contact['phones'] ?? []);
        $contactTelegram = collect((array) ($contact['telegram_usernames'] ?? []))
            ->map(fn ($username) => '@'.ltrim((string) $username, '@'))
            ->all();
        $createdAt = optional($vacancy->created_at);
        $createdFormatted = $createdAt ? $createdAt->format('M d, Y H:i') : '—';
        $createdAgo = $createdAt ? $createdAt->diffForHumans() : null;
        $description = $vacancy->description ?? '—';
    @endphp

    <style>
        .vacancy-show-hero {
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

        .vacancy-show-hero::before,
        .vacancy-show-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .vacancy-show-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -150px;
            right: -120px;
        }

        .vacancy-show-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -140px;
            left: -130px;
        }

        .vacancy-show-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .vacancy-show-hero__badge {
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

        .vacancy-show-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .vacancy-show-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }

        .vacancy-show-hero__meta-item {
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

        .vacancy-show-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
        }

        .vacancy-show-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .vacancy-show-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .vacancy-show-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.7rem;
            font-weight: 700;
            word-break: break-word;
            line-height: 1.2;
            color: #0f172a;
        }

        .vacancy-show-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: #94a3b8;
        }

        .vacancy-show-actions-card .vacancy-show-actions {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .vacancy-show-actions-card .vacancy-show-actions form {
            margin: 0;
        }

        .vacancy-show-sections {
            margin: 1.5rem 1.5rem 2rem;
        }

        .vacancy-show-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .vacancy-show-card .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .vacancy-show-card .card-body {
            padding: 24px 28px;
        }

        .vacancy-info-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .vacancy-info-chip {
            padding: 14px 18px;
            border-radius: 18px;
            background: #f4f6ff;
            border: 1px solid rgba(82, 97, 172, 0.12);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .vacancy-info-chip .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8a94b8;
        }

        .vacancy-info-chip .value {
            font-size: 1rem;
            font-weight: 600;
            color: #172655;
            word-break: break-word;
        }

        .vacancy-description {
            white-space: pre-wrap;
            font-weight: 500;
            color: #172655;
            background: #f8f9ff;
            border-radius: 18px;
            padding: 24px;
            border: 1px solid rgba(82, 97, 172, 0.12);
        }

        .vacancy-contact-list {
            display: grid;
            gap: 12px;
        }

        .vacancy-contact-item {
            padding: 12px 16px;
            border-radius: 14px;
            background: #f0f4ff;
            border: 1px solid rgba(82, 97, 172, 0.15);
            font-weight: 600;
            color: #193068;
        }

        @media (max-width: 991px) {
            .vacancy-show-hero {
                margin: 1.5rem 1rem;
                padding: 32px;
                border-radius: 24px;
            }

            .vacancy-show-sections {
                margin: 1.5rem 1rem;
            }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Vacancy</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories', $categoryIndexParams) }}">All categories</a></li>
                @if(!empty($categoryRouteParams['category']))
                    <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.by_category', $categoryRouteParams) }}">{{ $vacancy->category ?? 'Category' }}</a></li>
                @endif
                <li class="breadcrumb-item">Vacancy #{{ $vacancy->id }}</li>
            </ul>
        </div>
    </div>

    <div class="vacancy-show-hero">
        <div class="vacancy-show-hero__content">
            <div>
                <span class="vacancy-show-hero__badge">
                    <i class="feather-briefcase"></i>
                    {{ ucfirst($vacancy->source ?? 'Unknown') }} source
                </span>
                <h1 class="vacancy-show-hero__title">{{ $vacancy->title ?? 'Untitled vacancy' }}</h1>
                <div class="vacancy-show-hero__meta">
                    <span class="vacancy-show-hero__meta-item"><i class="feather-hash"></i>ID {{ $vacancy->id }}</span>
                    <span class="vacancy-show-hero__meta-item"><i class="feather-star"></i>{{ $statusLabel }}</span>
                    @if($vacancy->category)
                        <span class="vacancy-show-hero__meta-item text-capitalize"><i class="feather-layers"></i>{{ $vacancy->category }}</span>
                    @endif
                </div>
            </div>
            <div class="vacancy-show-stats">
                <div class="vacancy-show-stat-card">
                    <span class="label">Created</span>
                    <span class="value">{{ $createdFormatted }}</span>
                    <span class="hint">{{ $createdAgo ? 'Posted '.$createdAgo : '—' }}</span>
                </div>
                <div class="vacancy-show-stat-card">
                    <span class="label">Company</span>
                    <span class="value">{{ $vacancy->company ?? '—' }}</span>
                    <span class="hint">Employer name</span>
                </div>
                <div class="vacancy-show-stat-card">
                    <span class="label">Language</span>
                    <span class="value">{{ strtoupper($vacancy->language ?? '—') }}</span>
                    <span class="hint">Content locale</span>
                </div>
                <div class="vacancy-show-stat-card vacancy-show-actions-card">
                    <span class="label">Status &amp; actions</span>
                    
                   
                    <div class="vacancy-show-actions">
                        @if($isPublished || $isArchived)
                            <form method="POST" action="{{ route('admin.vacancies.update_status', ['vacancy' => $vacancy->id]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $isPublished ? \App\Models\Vacancy::STATUS_ARCHIVE : \App\Models\Vacancy::STATUS_PUBLISH }}">
                                <input type="hidden" name="return_filter" value="{{ $currentFilter }}">
                                <button type="submit" class="btn btn-sm {{ $isPublished ? 'btn-danger' : 'btn-success' }} shadow-sm">
                                    {{ $isPublished ? 'Archive' : 'Publish' }}
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.vacancies.destroy', ['vacancy' => $vacancy->id]) }}" onsubmit="return confirm('Delete this vacancy?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_filter" value="{{ $currentFilter }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm text-red justify-content-end">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="vacancy-show-sections">
        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="vacancy-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">General details</h6></div>
                    <div class="card-body">
                        <div class="vacancy-info-grid">
                            <div class="vacancy-info-chip">
                                <span class="label">Source</span>
                                <span class="value">{{ $vacancy->source ?? '—' }}</span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Source ID</span>
                                <span class="value">{{ $vacancy->source_id ?? '—' }}</span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Target message</span>
                                <span class="value">
                                    @if(!empty($vacancy->target_message_id))
                                        <a href="{{ $vacancy->target_message_id }}" target="_blank" rel="noopener">{{ $vacancy->target_message_id }}</a>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Apply URL</span>
                                <span class="value">
                                    @if(!empty($vacancy->apply_url))
                                        <a href="{{ $vacancy->apply_url }}" target="_blank" rel="noopener">{{ $vacancy->apply_url }}</a>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <!-- <div class="vacancy-info-chip">
                                <span class="label">Location</span>
                                <span class="value">{{ $vacancy->location ?? '—' }}</span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Salary</span>
                                <span class="value">{{ $vacancy->salary ?? '—' }}</span>
                            </div> -->
                            <div class="vacancy-info-chip">
                                <span class="label">Category</span>
                                <span class="value text-capitalize">{{ $vacancy->category ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="vacancy-show-card card h-100">
                    <div class="card-header"><h6 class="mb-0">Contacts</h6></div>
                    <div class="card-body">
                        <div class="vacancy-contact-list">
                            <div class="vacancy-info-chip">
                                <span class="label">Telegram</span>
                                <span class="value">
                                    @if(count($contactTelegram))
                                        {{ implode(', ', $contactTelegram) }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Phones</span>
                                <span class="value">
                                    @if(count($contactPhones))
                                        {{ implode(', ', $contactPhones) }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </span>
                            </div>
                            <div class="vacancy-info-chip">
                                <span class="label">Source message</span>
                                <span class="value">
                                    @if(!empty($vacancy->source_message_id))
                                        <a href="{{ $vacancy->source_message_id }}" target="_blank" rel="noopener">{{ $vacancy->source_message_id }}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="vacancy-show-card card">
                    <div class="card-header"><h6 class="mb-0">Description</h6></div>
                    <div class="card-body">
                        <div class="vacancy-description">{{ $description }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
