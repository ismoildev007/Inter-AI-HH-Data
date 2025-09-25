@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Profile</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Profile</li>
            </ul>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xxl-4 col-lg-5">
            <div class="card stretch">
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="{{ $user->avatar_path ? asset($user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" alt="" class="img-fluid">
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}</h5>
                    <p class="text-muted mb-2">{{ $user->role->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $user->email }}</p>
                    @if($user->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $user->phone }}</p>
                    @endif
                    <div class="row mt-4 g-2">
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $resumesCount }}</div>
                                <div class="fs-11 text-muted">Resumes</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $applicationsCount }}</div>
                                <div class="fs-11 text-muted">Applications</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded py-2">
                                <div class="fs-6 fw-bold text-dark">{{ $profileViewsCount }}</div>
                                <div class="fs-11 text-muted">Views</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="badge bg-primary">Credits: {{ $user->credit->balance ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-8 col-lg-7">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Account Details</h6></div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">First Name</label>
                            <div class="form-control">{{ $user->first_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Last Name</label>
                            <div class="form-control">{{ $user->last_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Email</label>
                            <div class="form-control">{{ $user->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone</label>
                            <div class="form-control">{{ $user->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Birth Date</label>
                            <div class="form-control">{{ $user->birth_date ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Role</label>
                            <div class="form-control">{{ $user->role->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card stretch mt-3">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Settings</h6></div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Language</label>
                            <div class="form-control">{{ $user->settings->language ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Notifications</label>
                            <div class="form-control">{{ ($user->settings->notifications_enabled ?? false) ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply</label>
                            <div class="form-control">{{ ($user->settings->auto_apply_enabled ?? false) ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply Limit</label>
                            <div class="form-control">{{ $user->settings->auto_apply_limit ?? 0 }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Auto Apply Count</label>
                            <div class="form-control">{{ $user->settings->auto_apply_count ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
