@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Applications</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Applications</li>
            </ul>
        </div>
    </div>

    @php($searchTerm = $search ?? request('q'))

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <!-- Chap tomonda sarlavha -->
            <div class="card-title mb-0">
                <h6 class="mb-0">All Applications</h6>
            </div>

            <!-- Markazda qidiruv formasi -->
            <div class="d-flex justify-content-center flex-grow-1">
                <form method="GET" class="d-flex justify-content-center" style="width: 400px;">
                    <div class="input-group input-group-sm w-100">
                        <input type="search"
                               name="q"
                               value="{{ $searchTerm }}"
                               class="form-control"
                               placeholder="Search applications (user, vacancy, resume)">
                        @if(!empty($searchTerm))
                            <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">
                                <i class="feather-x"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="feather-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                           
                            <th class="text-muted">User</th>
                            <th class="text-muted">Vacancy</th>
                            <th class="text-muted">Resume</th>
                            <th class="text-muted">Status</th>
                            <th class="text-muted">Match</th>
                            <th class="text-muted">Submitted</th>
                            <th class="text-end text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr>
                                
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-image avatar-sm">
                                            <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ trim((optional($app->user)->first_name ?? '').' '.(optional($app->user)->last_name ?? '')) ?: '—' }}</div>
                                            <div class="fs-11 text-muted">{{ optional($app->user)->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-dark">{{ optional($app->vacancy)->title ?? '—' }}</div>
                                        @if(optional($app->vacancy)->company)
                                            <span class="badge bg-light text-dark">{{ $app->vacancy->company }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ optional($app->resume)->title ?? '—' }}</td>
                                <td>
                                    @php($st = $app->status)
                                    <span class="badge 
                                        {{ $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $st ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $app->match_score !== null ? number_format($app->match_score, 2) : '—' }}</td>
                                <td>{{ optional($app->submitted_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.applications.show', $app->id) }}" class="btn btn-sm btn-light-brand">
                                        <i class="feather-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($applications instanceof \Illuminate\Contracts\Pagination\Paginator || $applications instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer d-flex justify-content-center">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
@endsection