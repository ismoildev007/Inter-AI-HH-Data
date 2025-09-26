@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Add Telegram Channel</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.telegram_channels.index') }}">Telegram Channels</a></li>
                <li class="breadcrumb-item">Create</li>
            </ul>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.telegram_channels.index') }}" class="btn btn-light-brand">
                <i class="feather-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xxl-6 col-lg-7 justify-content-center mx-auto mt-5">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h6 class="mb-0">Add Channel </h6></div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('admin.telegram_channels.store') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Channel ID or Username<span class="text-danger">*</span></label>
                            <input type="text" name="channel_id" class="form-control @error('channel_id') is-invalid @enderror" value="{{ old('channel_id') }}" placeholder="e.g. -1001234567890" required>
                            @error('channel_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" placeholder="e.g. my_channel">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role" id="role_source" value="source" {{ old('role', 'source') === 'source' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role_source">
                                            Source (incoming: manba shuyerdan olinadi) 
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role" id="role_target" value="target" {{ old('role') === 'target' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role_target">
                                            Target (outgoing: manba shuyerga yuboriladi)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('role')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Only one Target is allowed. Saving a new target will unset the previous.</div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="feather-save me-1"></i> Save Channel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
