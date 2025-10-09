@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Resume Details</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.resumes.index') }}">Resumes</a></li>
                <li class="breadcrumb-item">#{{ $resume->id }}</li>
            </ul>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.resumes.index') }}" class="btn btn-light-brand"><i class="feather-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-3 mt-4 ms-4 me-4">
        <div class="col-xxl-4 col-lg-5">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">User</h6></div>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-text avatar-xxl mx-auto mb-3">
                        <img src="{{ $resume->user?->avatar_path ? asset($resume->user->avatar_path) : module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" alt="" class="img-fluid">
                    </div>
                    @php
                        $userFullName = trim(($resume->user?->first_name ?? '') . ' ' . ($resume->user?->last_name ?? ''));
                    @endphp
                    <h5 class="fw-bold text-dark mb-1">{{ $userFullName !== '' ? $userFullName : '—' }}</h5>
                    <p class="text-muted mb-2">{{ $resume->user?->role?->name ?? '—' }}</p>
                    <p class="mb-0"><i class="feather-mail me-1"></i> {{ $resume->user?->email ?? '—' }}</p>
                    @if($resume->user?->phone)
                        <p class="mb-0"><i class="feather-phone me-1"></i> {{ $resume->user?->phone }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xxl-8 col-lg-7 mt-3">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Resume</h6></div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-muted">Title</label>
                            <div class="form-control d-flex align-items-center gap-2">
                                <span>{{ $resume->title ?? '—' }}</span>
                                @if($resume->is_primary)
                                    <span class="badge bg-primary">Primary</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Created</label>
                            <div class="form-control">{{ optional($resume->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">File</label>
                            @php
                                $filePath = $resume->file_path;
                                $openUrl = null;
                                if ($filePath) {
                                    $isAbsolute = preg_match('#^(https?:)?//#', $filePath) === 1;
                                    $openUrl = $isAbsolute
                                        ? $filePath
                                        : route('admin.resumes.download', $resume->id);
                                }
                            @endphp
                            @if($openUrl)
                                <div class="d-flex align-items-center gap-2">
                                    <a class="btn btn-sm btn-light-brand" href="{{ $openUrl }}" target="_blank" rel="noopener">
                                        <i class="feather-download me-1"></i> Open
                                    </a>
                                    <span class="fs-11 text-muted">{{ $resume->file_mime }} • {{ number_format((int) $resume->file_size / 1024, 0) }} KB</span>
                                </div>
                            @else
                                <div class="form-control">—</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Language</label>
                            <div class="form-control">{{ $resume->analysis->language ?? '—' }}</div>
                        </div>
                        @if(!empty($resume->description))
                            <div class="col-12">
                                <label class="form-label text-muted">Description</label>
                                <div class="form-control">{{ $resume->description }}</div>
                            </div>
                        @endif
                    </div>

                    @if($resume->analysis)
                        <div class="row g-3">
                            @if($resume->analysis->skills)
                                <div class="col-12">
                                    <h6 class="mb-2">Skills</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($resume->analysis->skills as $s)
                                            <span class="badge bg-light text-dark">{{ $s }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($resume->analysis->strengths)
                                <div class="col-12">
                                    <h6 class="mb-2">Strengths</h6>
                                    <ul class="mb-0">
                                        @foreach($resume->analysis->strengths as $it)
                                            <li>{{ $it }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if($resume->analysis->weaknesses)
                                <div class="col-12">
                                    <h6 class="mb-2">Weaknesses</h6>
                                    <ul class="mb-0">
                                        @foreach($resume->analysis->weaknesses as $it)
                                            <li>{{ $it }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if($resume->analysis->keywords)
                                <div class="col-12">
                                    <h6 class="mb-2">Keywords</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($resume->analysis->keywords as $kw)
                                            <span class="badge bg-light text-dark">{{ $kw }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
