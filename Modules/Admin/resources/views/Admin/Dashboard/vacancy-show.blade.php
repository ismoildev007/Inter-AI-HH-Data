@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Vacancy #{{ $vacancy->id }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories') }}">All Categories</a></li>
                @if($vacancy->category)
                    <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.by_category', $categorySlug ?? 'other') }}">{{ $vacancy->category }}</a></li>
                @endif
                <li class="breadcrumb-item">Vacancy</li>
            </ul>
        </div>
    </div>

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title"><h6 class="mb-0">Details</h6></div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="mb-2"><span class="text-muted">ID</span><div class="fw-semibold">{{ $vacancy->id }}</div></div>
                    <div class="mb-2"><span class="text-muted">Source</span><div class="fw-semibold">{{ $vacancy->source ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Title</span><div class="fw-semibold">{{ $vacancy->title ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Company</span><div class="fw-semibold">{{ $vacancy->company ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Status</span><div class="fw-semibold">{{ $vacancy->status ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Category</span><div class="fw-semibold text-capitalize">{{ $vacancy->category ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Language</span><div class="fw-semibold">{{ $vacancy->language ?? '—' }}</div></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mb-2"><span class="text-muted">Apply URL</span>
                        <div class="fw-semibold">
                            @if(!empty($vacancy->apply_url))
                                <a href="{{ $vacancy->apply_url }}" target="_blank" rel="noopener">{{ $vacancy->apply_url }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="mb-2"><span class="text-muted">Source ID</span><div class="fw-semibold">{{ $vacancy->source_id ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Source Message</span>
                        <div class="fw-semibold">
                            @if(!empty($vacancy->source_message_id))
                                <a href="{{ $vacancy->source_message_id }}" target="_blank" rel="noopener">{{ $vacancy->source_message_id }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="mb-2"><span class="text-muted">Target Message</span>
                        <div class="fw-semibold">
                            @if(!empty($vacancy->target_message_id))
                                <a href="{{ $vacancy->target_message_id }}" target="_blank" rel="noopener">{{ $vacancy->target_message_id }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="mb-2"><span class="text-muted">Contact</span>
                        <div class="fw-semibold">
                            @php($c = (array)($vacancy->contact ?? []))
                            @php($phones = (array)($c['phones'] ?? []))
                            @php($users  = (array)($c['telegram_usernames'] ?? []))
                            @if(count($phones) || count($users))
                                @if(count($users))
                                    <div>Telegram: {{ collect($users)->map(fn($u) => '@'.ltrim((string)$u, '@'))->implode(', ') }}</div>
                                @endif
                                @if(count($phones))
                                    <div>Phones: {{ collect($phones)->implode(', ') }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-2"><span class="text-muted">Description</span>
                        <div class="fw-semibold" style="white-space: pre-wrap;">{{ $vacancy->description ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
