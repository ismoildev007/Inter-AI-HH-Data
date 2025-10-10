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

    @php($searchTerm = $search ?? request('q'))

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <!-- Chap tomonda sarlavha -->
            <div class="card-title mb-0">
                <h6 class="mb-0">All Users</h6>
            </div>

            <!-- Markazda qidiruv formasi -->
            <div class="d-flex justify-content-center flex-grow-1">
                <form method="GET" class="d-flex justify-content-center" style="width: 400px;">
                    <div class="input-group input-group-sm w-100">
                        <input type="search"
                               name="q"
                               value="{{ $searchTerm }}"
                               class="form-control"
                               placeholder="Search users (name, email, phone)">
                        @if(!empty($searchTerm))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
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
                                            <img src="/assets/images/avatar/1.png" class="img-fluid" alt="avatar">
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