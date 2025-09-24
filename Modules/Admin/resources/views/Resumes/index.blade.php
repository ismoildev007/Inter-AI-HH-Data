@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Resumes</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Resumes</li>
            </ul>
        </div>
    </div>

    <div class="card stretch">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title">
                <h6 class="mb-0">All Resumes</h6>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted">ID</th>
                            <th class="text-muted">Title</th>
                            <th class="text-muted">User</th>
                            <th class="text-muted">Created</th>
                            <th class="text-end text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumes as $r)
                            <tr>
                                <td class="fw-semibold text-dark">#{{ $r->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-dark">{{ $r->title ?? '—' }}</div>
                                        @if($r->is_primary)
                                            <span class="badge bg-primary">Primary</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-image avatar-sm">
                                            <img src="{{ optional($r->user)->avatar_path ? asset($r->user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" class="img-fluid" alt="avatar">
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ trim((optional($r->user)->first_name ?? '').' '.(optional($r->user)->last_name ?? '')) ?: '—' }}</div>
                                            <div class="fs-11 text-muted">{{ optional($r->user)->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.resumes.show', $r->id) }}" class="btn btn-sm btn-light-brand">
                                        <i class="feather-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No resumes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($resumes instanceof \Illuminate\Contracts\Pagination\Paginator || $resumes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer">
                {{ $resumes->links() }}
            </div>
        @endif
    </div>
@endsection
