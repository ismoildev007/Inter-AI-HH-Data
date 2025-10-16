@extends('admin::components.layouts.master')

@section('content')
    @php
        $adminEmail = config('admin.seeder.email', 'admin@gmail.com');
    @endphp

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

    <div class="mb-5 mt-1 ms-4 me-4">
        <div class="mt-4">
            <div class="card stretch">
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                                                <img src="{{ asset('assets/images/avatar/5.svg') }}"
                            alt=""
                            class="img-fluid">
            
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

  

        @if ($user && $user->email === $adminEmail)
            <div class="mt-4">
                <div class="card stretch">
                    <div class="card-header align-items-center justify-content-between">
                        <div class="card-title"><h6 class="mb-0">Update Password</h6></div>
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.profile.password') }}" class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label for="old_password" class="form-label">Current Password</label>
                                <input
                                    type="password"
                                    name="old_password"
                                    id="old_password"
                                    class="form-control @error('old_password') is-invalid @enderror"
                                    required
                                    autocomplete="current-password"
                                >
                                @error('old_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input
                                    type="password"
                                    name="new_password"
                                    id="new_password"
                                    class="form-control @error('new_password') is-invalid @enderror"
                                    required
                                    autocomplete="new-password"
                                >
                                @error('new_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    class="form-control @error('confirm_password') is-invalid @enderror"
                                    required
                                    autocomplete="new-password"
                                >
                                @error('confirm_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
