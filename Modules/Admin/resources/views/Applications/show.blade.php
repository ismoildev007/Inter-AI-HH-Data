@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Application Details</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item">#{{ $application->id }}</li>
            </ul>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.applications.index') }}" class="btn btn-light-brand"><i class="feather-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xxl-4 col-lg-5">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">User</h6></div>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="{{ $application->user && $application->user->avatar_path ? asset($application->user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" alt="" class="img-fluid">
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ trim(($application->user->first_name ?? '').' '.($application->user->last_name ?? '')) ?: '—' }}</h5>
                    <p class="text-muted mb-2">{{ $application->user->role->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $application->user->email ?? '—' }}</p>
                    @if(optional($application->user)->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $application->user->phone }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xxl-8 col-lg-7">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Application</h6></div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">Status</label>
                            @php($st=$application->status)
                            <div class="form-control">
                                <span class="badge {{ $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning') }}">{{ $st ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Match Score</label>
                            <div class="form-control">{{ $application->match_score !== null ? number_format($application->match_score, 2) : '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Submitted</label>
                            <div class="form-control">{{ optional($application->submitted_at)->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">External ID</label>
                            <div class="form-control">{{ $application->external_id ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">HH Status</label>
                            <div class="form-control">{{ $application->hh_status ?? '—' }}</div>
                        </div>
                        @if(!empty($application->notes))
                            <div class="col-12">
                                <label class="form-label text-muted">Notes</label>
                                <div class="form-control">{{ $application->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-xxl-6">
                    <div class="card stretch">
                        <div class="card-header align-items-center justify-content-between">
                            <div class="card-title"><h6 class="mb-0">Vacancy</h6></div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-muted">Title</label>
                                    <div class="form-control">{{ optional($application->vacancy)->title ?? '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted">Company</label>
                                    <div class="form-control">{{ optional($application->vacancy)->company ?? '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted">Language</label>
                                    <div class="form-control">{{ optional($application->vacancy)->language ?? '—' }}</div>
                                </div>
                                @if(optional($application->vacancy)->apply_url)
                                    <div class="col-12">
                                        <a class="btn btn-light-brand" href="{{ $application->vacancy->apply_url }}" target="_blank">
                                            <i class="feather-external-link me-1"></i> Apply URL
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card stretch">
                        <div class="card-header align-items-center justify-content-between">
                            <div class="card-title"><h6 class="mb-0">Resume</h6></div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-muted">Title</label>
                                    <div class="form-control">{{ optional($application->resume)->title ?? '—' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted">Created</label>
                                    <div class="form-control">{{ optional(optional($application->resume)->created_at)->format('Y-m-d H:i') }}</div>
                                </div>
                                @if(optional($application->resume)->file_path)
                                    <div class="col-12">
                                        <a class="btn btn-light-brand" href="{{ asset($application->resume->file_path) }}" target="_blank">
                                            <i class="feather-download me-1"></i> Open Resume
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
