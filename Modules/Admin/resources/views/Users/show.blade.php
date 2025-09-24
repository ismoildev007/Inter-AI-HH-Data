@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">User Details</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item">#{{ $user->id }}</li>
            </ul>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light-brand"><i class="feather-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xxl-4 col-lg-5">
            <div class="card stretch">
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="{{ $user->avatar_path ? asset($user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" alt="" class="img-fluid">
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: '—' }}</h5>
                    <p class="text-muted mb-2">{{ $user->role->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $user->email }}</p>
                    @if($user->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $user->phone }}</p>
                    @endif
                    <div class="mt-3 fs-11 text-muted">Joined: {{ optional($user->created_at)->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xxl-8 col-lg-7">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Resumes</h6></div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-muted">ID</th>
                                    <th class="text-muted">Title</th>
                                    <th class="text-muted">Created</th>
                                    <th class="text-end text-muted">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->resumes as $resume)
                                    <tr>
                                        <td class="fw-semibold text-dark">#{{ $resume->id }}</td>
                                        <td>{{ $resume->title ?? '—' }}</td>
                                        <td>{{ optional($resume->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.resumes.show', $resume->id) }}" class="btn btn-sm btn-light-brand">
                                                <i class="feather-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No resumes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
