@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Vacancies by Category</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Vacancies by Category</li>
            </ul>
        </div>
    </div>

    @php
        $currentFilter = $filter ?? 'all';
        $searchTerm = $search ?? request('q', '');
    @endphp
    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
            <div class="card-title mb-0"><h6 class="mb-0">All Categories @isset($totalCount)<span class="text-muted">(Total: {{ $totalCount }})</span>@endisset</h6></div>
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 ms-lg-auto w-100 w-lg-auto">
<!-- 🔍 Search Categories Form -->
<div class="d-flex justify-content-center flex-grow-1">
    <form method="GET" class="d-flex justify-content-center" style="width: 400px;">
        @if($currentFilter !== 'all')
            <input type="hidden" name="filter" value="{{ $currentFilter }}">
        @endif
        <div class="input-group input-group-sm w-100">
            <input type="search"
                   name="q"
                   value="{{ $searchTerm }}"
                   class="form-control"
                   placeholder="Search categories">
            @if(!empty($searchTerm))
                <a href="{{ route('admin.vacancies.categories', $currentFilter === 'all' ? [] : ['filter' => $currentFilter]) }}"
                   class="btn btn-outline-secondary">
                    <i class="feather-x"></i>
                </a>
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="feather-search"></i>
            </button>
        </div>
    </form>
</div>
                <div class="d-flex align-items-center gap-2 justify-content-md-end">
                    <span class="text-muted small">Filter</span>
                    <div class="btn-group btn-group-sm" role="group">
                        @foreach(['all' => 'All', 'telegram' => 'Telegram', 'hh' => 'HH'] as $value => $label)
                            @php
                                $isActive = $currentFilter === $value;
                                $filterParams = [];
                                if ($value !== 'all') {
                                    $filterParams['filter'] = $value;
                                }
                                if (!empty($searchTerm)) {
                                    $filterParams['q'] = $searchTerm;
                                }
                                $filterUrl = route('admin.vacancies.categories', $filterParams);
                            @endphp
                            <a
                                href="{{ $filterUrl }}"
                                class="btn {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted">#</th>
                            <th class="text-muted">Category</th>
                            <th class="text-end text-muted">Vacancies</th>
                            <th class="text-end text-muted" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $i + 1 }}</td>
                                <td class="text-capitalize">{{ $row->category ?: 'other' }}</td>
                                <td class="text-end fw-bold">{{ $row->c }}</td>
                                <td class="text-end">
                                    @php
                                    $categoryRouteParams = array_filter([
                                            'category' => $row->slug ?? 'other',
                                            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                                            'q' => !empty($searchTerm) ? $searchTerm : null,
                                        ], function ($value) {
                                            return !is_null($value);
                                        });
                                    @endphp
                                    <a href="{{ route('admin.vacancies.by_category', $categoryRouteParams) }}" class="btn btn-sm btn-success">
                                        <i class="feather-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
