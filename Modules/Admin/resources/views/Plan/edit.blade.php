@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Plan</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
            <li class="breadcrumb-item">Edit</li>
        </ul>
    </div>
    <!-- <div class="ms-auto d-flex gap-2">
        <a href="{{ route('admin.plans.show', $plan) }}" class="btn btn-light">
            <i class="feather-eye me-1"></i> View
        </a>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
            <i class="feather-arrow-left me-1"></i> Back
        </a>
    </div> -->
</div>

<div class="row g-3 justify-content-center mt-3">
    <div class="col-xxl-7 col-xl-8 col-lg-9">
        <div class="card stretch plan-card">
            <div class="card-header align-items-center justify-content-between bg-white">
                <div>
                    <h6 class="mb-1 fw-semibold">Update Blueprint</h6>
                    <p class="text-muted mb-0 small">Adjust pricing or limits — existing subscribers keep prior terms.</p>
                </div>
                <i class="feather-edit-3 text-primary fs-4"></i>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="feather-alert-triangle me-1"></i>{{ $errors->first() }}
                    </div>
                @endif
                <form action="{{ route('admin.plans.update', $plan) }}" method="POST" class="plan-form">
                    @csrf
                    @method('PUT')
                    @include('admin::Plan.partials.form-fields', [
                        'plan' => $plan,
                        'submitLabel' => 'Update Plan',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .plan-card {
        border-radius: 24px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(148, 163, 184, 0.2);
    }
    .plan-card .card-header {
        padding: 24px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.7);
    }
    .plan-card .card-body {
        padding: 28px 26px 32px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.55), rgba(255, 255, 255, 0.95));
    }
    .plan-form .form-label {
        letter-spacing: 0.08em;
    }
    .plan-form .form-text {
        color: #6b7280;
    }
    .plan-form .btn-primary {
        border-radius: 14px;
        padding: 12px 28px;
        font-weight: 600;
    }
    .plan-form .btn-outline-secondary {
        border-radius: 14px;
        padding: 12px 24px;
        font-weight: 600;
    }
</style>
@endsection
