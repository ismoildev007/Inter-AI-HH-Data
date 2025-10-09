@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-capitalize">Category: {{ $category }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories') }}">All Categories</a></li>
                 <li class="breadcrumb-item text-capitalize">{{ $category }}</li>
            </ul>
        </div>
        <div class="ms-auto">
            <span class="badge bg-light text-dark">Total: {{ $count }}</span>
        </div>
    </div>

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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vacancies as $i => $v)
                            <tr>
                                <td class="fw-semibold text-dark">{{ ($vacancies->currentPage()-1)*$vacancies->perPage() + $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('admin.vacancies.show', $v->id) }}" class="text-decoration-none">
                                        {{ $v->title ?? '—' }}
                                    </a>
                                </td>
                                <td class="text-nowrap">{{ optional($v->created_at)->format('Y-m-d') }}</td>
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
