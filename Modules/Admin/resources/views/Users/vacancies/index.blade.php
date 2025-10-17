@extends('admin::components.layouts.master')

@section('content')
    @php
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $displayName = $fullName !== '' ? $fullName : 'User #' . $user->id;
        $totalVacancies = $vacancies->total();
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Matched Vacancies</h5>
                <h2 class="m-0">{{ $displayName }}</h2>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->id }}</a></li>
                <li class="breadcrumb-item active">Vacancies</li>
            </ul>
        </div>

    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1">Vacancy matches</h5>
                        <p class="text-muted mb-0">
                            Showing vacancies matched with {{ $displayName }}'s resumes via Match Results.
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="d-block text-uppercase text-muted small">Total matched vacancies</span>
                        <span class="fs-3 fw-semibold">{{ $totalVacancies }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;">ID</th>
                            <th>Vacancy</th>
                            <th style="width: 160px;">Best score</th>
                            <th>Resumes matched</th>
                            <th style="width: 220px;">Last matched</th>
                            <th style="width: 140px;" class="text-end">Actions</th>
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
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark fw-semibold">#{{ $vacancy->id }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $vacancy->title ?? '—' }}</div>
                                <div class="text-muted small">{{ ucfirst($vacancy->source ?? 'unknown') }}</div>
                            </td>
                            <td>
                                @if(!is_null($bestScore))
                                    <span class="fw-semibold">{{ number_format((float) $bestScore, 2) }}%</span>
                                    @if($bestMatch?->resume?->title)
                                        <div class="text-muted small">via "{{ $bestMatch->resume->title }}"</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($resumeTitles))
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($resumeTitles as $title)
                                            <span class="badge bg-primary">{{ $title }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($latestMatchedAt)
                                    <div>{{ $latestMatchedAt->format('M d, Y H:i') }}</div>
                                    <div class="text-muted small">{{ $latestMatchedAt->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.vacancies.show', ['id' => $vacancy->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="feather-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No vacancies have been matched to this user yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vacancies->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $vacancies->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
