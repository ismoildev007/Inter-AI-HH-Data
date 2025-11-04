@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all', 'telegram', 'hh'], true)) { $currentFilter = 'all'; }
        $filterLabels = ['all' => 'All sources', 'telegram' => 'Telegram', 'hh' => 'HeadHunter'];
        $filterLabel = $filterLabels[$currentFilter] ?? ucfirst($currentFilter);
        $isPaginator = $vacancies instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $vacancies->getCollection() : collect($vacancies);
        $totalSkipped = $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $vacancies->total() : $collection->count();
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Vacancies</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories') }}">Categories</a></li>
                <li class="breadcrumb-item">Skipped</li>
            </ul>
        </div>
    </div>

    <div class="categories-hero">
        <div class="categories-hero__content">
            <div>
                <span class="categories-hero__badge">
                    <i class="feather-slash"></i>
                    Dedupe skipped
                </span>
                <h1 class="categories-hero__title">Skipped vacancies</h1>
                <div class="categories-hero__meta">
                    <span class="categories-hero__meta-item"><i class="feather-filter"></i>{{ $filterLabel }}</span>
                    <span class="categories-hero__meta-item"><i class="feather-eye-off"></i>Skipped: {{ number_format($totalSkipped) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card categories-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="filters">
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.vacancies.skipped', ['filter' => 'all']) }}" class="btn btn-sm btn-outline-primary {{ $currentFilter==='all' ? 'active' : '' }}">All</a>
                    <a href="{{ route('admin.vacancies.skipped', ['filter' => 'telegram']) }}" class="btn btn-sm btn-outline-primary {{ $currentFilter==='telegram' ? 'active' : '' }}">Telegram</a>
                    <a href="{{ route('admin.vacancies.skipped', ['filter' => 'hh']) }}" class="btn btn-sm btn-outline-primary {{ $currentFilter==='hh' ? 'active' : '' }}">HH</a>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted">#</th>
                        <th class="text-muted">Title</th>
                        <th class="text-muted">Created</th>
                        <th class="text-muted text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $firstNumber = method_exists($vacancies, 'firstItem') ? ($vacancies->firstItem() ?? 1) : 1; @endphp
                    @forelse($vacancies as $index => $vacancy)
                        @php
                            $viewParams = array_filter([
                                'id' => $vacancy->id,
                                'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                            ], fn ($v) => !is_null($v));
                        @endphp
                        <tr class="categories-list-row" onclick="window.location.href='{{ route('admin.vacancies.show', $viewParams) }}'">
                            <td class="text-center" data-label="#">{{ $firstNumber + $index }}</td>
                            <td class="fw-semibold text-dark" data-label="Title">{{ $vacancy->title ?? '—' }}</td>
                            <td class="text-nowrap" data-label="Created">{{ optional($vacancy->created_at)->format('M d, Y') ?? '—' }}</td>
                            <td class="text-end" data-label="Actions">
                                <a href="{{ route('admin.vacancies.show', $viewParams) }}" class="btn btn-sm btn-light" onClick="event.stopPropagation();">
                                    <i class="feather-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No skipped vacancies.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin::components.pagination', ['paginator' => $vacancies])
    </div>
@endsection

