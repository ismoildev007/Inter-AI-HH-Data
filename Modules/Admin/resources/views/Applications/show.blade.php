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
</div>

<div class="container-fluid mt-4">
    <div class="row g-3 equal-grid ms-3 me-3">

        <!-- 🧍 USER -->
        <div class="col-xxl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">User</h6>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                    </div>
                    <h5 class="fw-bold text-dark mb-1">
                        {{ trim(($application->user->first_name ?? '').' '.($application->user->last_name ?? '')) ?: '—' }}
                    </h5>
                    <p class="text-muted mb-2">{{ $application->user->role->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $application->user->email ?? '—' }}</p>
                    @if(optional($application->user)->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $application->user->phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 🗂 APPLICATION -->
        <div class="col-xxl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Application</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">Status</label>
                            @php $st = $application->status; @endphp
                            <div class="form-control">
                                <span class="badge {{ $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                    {{ $st ?? '—' }}
                                </span>
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
        </div>

        <!-- 💼 VACANCY -->
        <div class="col-xxl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Vacancy</h6>
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
                                <a class="btn btn-light-brand w-100" href="{{ $application->vacancy->apply_url }}" target="_blank">
                                    <i class="feather-external-link me-1"></i> Apply URL
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 📄 RESUME -->
        <div class="col-xxl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Resume</h6>
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
                        @php $resume = $application->resume; @endphp
                        @if($resume && $resume->file_path)
                            @php
                                $fileUrl = $resume->file_path;
                                $openUrl = preg_match('#^(https?:)?//#', $fileUrl) === 1
                                    ? $fileUrl
                                    : route('admin.resumes.download', $resume->id);
                            @endphp
                            <div class="col-12">
                                <a class="btn btn-light-brand w-100" href="{{ $openUrl }}" target="_blank" rel="noopener">
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

<style>
/* 🔹 Teng balandlik barcha kartalar uchun */
@media (min-width: 992px) {
    .equal-grid > [class*='col-'] {
        display: flex;
    }
    .equal-grid > [class*='col-'] > .card {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }
    .equal-grid .card-body {
        flex-grow: 1;
    }
}
</style>
@endsection