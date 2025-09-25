@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Users</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Users</li>
            </ul>
        </div>
    </div>

    <div class="card stretch">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title">
                <h6 class="mb-0">All Users</h6>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted">ID</th>
                            <th class="text-muted">User</th>
                            <th class="text-muted">Email</th>
                            <th class="text-muted">Role</th>
                            <th class="text-muted">Created</th>
                            <th class="text-end text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="fw-semibold text-dark">#{{ $u->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-image avatar-sm">
                                            <img src="{{ $u->avatar_path ? asset($u->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" class="img-fluid" alt="avatar">
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: '—' }}</div>
                                            @if($u->phone)
                                                <div class="fs-11 text-muted">{{ $u->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->role->name ?? '—' }}</td>
                                <td>{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-light-brand">
                                        <i class="feather-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users instanceof \Illuminate\Contracts\Pagination\Paginator || $users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="card-footer d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
