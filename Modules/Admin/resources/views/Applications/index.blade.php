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

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title">
                <h6 class="mb-0">All Applications</h6>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted">ID</th>
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
                                <td class="fw-semibold text-dark">#{{ $app->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-image avatar-sm">
                                            <img src="{{ optional($app->user)->avatar_path ? asset($app->user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" class="img-fluid" alt="avatar">
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
                                    @php($st=$app->status)
                                    <span class="badge {{ $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning') }}">{{ $st ?? '—' }}</span>
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
