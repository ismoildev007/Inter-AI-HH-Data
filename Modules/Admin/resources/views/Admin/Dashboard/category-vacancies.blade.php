@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? 'all';
        $filterLabels = [
            'all' => 'All sources',
            'telegram' => 'Telegram only',
            'hh' => 'HH only',
        ];
        $categoryIndexParams = $currentFilter === 'all' ? [] : ['filter' => $currentFilter];
    @endphp
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-capitalize">Category: {{ $category }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories', $categoryIndexParams) }}">All Categories</a></li>
                 <li class="breadcrumb-item text-capitalize">{{ $category }}</li>
            </ul>
        </div>
        <div class="ms-auto">
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark">Total: {{ $count }}</span>
                @if($currentFilter !== 'all')
                    <span class="badge bg-secondary text-white">{{ $filterLabels[$currentFilter] ?? $currentFilter }}</span>
                @endif
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success mt-4 ms-4 me-4">{{ session('status') }}</div>
    @endif

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title"><h6 class="mb-0">Vacancies (titles)</h6></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted" style="width:1%">#</th>
                            <th class="text-muted">Title</th>
                            <th class="text-muted" style="width:1%">Created</th>
                            <th class="text-end text-muted" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vacancies as $i => $v)
                            <tr>
                                <td class="fw-semibold text-dark">{{ ($vacancies->currentPage()-1)*$vacancies->perPage() + $i + 1 }}</td>
                                <td>{{ $v->title ?? '—' }}</td>
                                <td class="text-nowrap">{{ optional($v->created_at)->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    @php
                                        $vacancyRouteParams = ['id' => $v->id];
                                        if ($currentFilter !== 'all') {
                                            $vacancyRouteParams['filter'] = $currentFilter;
                                        }
                                    @endphp
                                    <a href="{{ route('admin.vacancies.show', $vacancyRouteParams) }}" class="btn btn-sm btn-success">
                                        <i class="feather-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No vacancies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vacancies instanceof \Illuminate\Contracts\Pagination\Paginator || $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer d-flex justify-content-center">
                {{ $vacancies->links() }}
            </div>
        @endif
    </div>
@endsection
