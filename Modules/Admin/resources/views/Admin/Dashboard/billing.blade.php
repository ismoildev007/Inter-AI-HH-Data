@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Billing Dashboard</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Billing Dashboard</li>
        </ul>
    </div>
</div>

<div class="billing-dashboard">
    <div class="card">
        <div class="card-body text-center py-5">
            <h6 class="text-muted mb-1">Billing analytics will appear here soon.</h6>
        </div>
    </div>
</div>

<style>
    .billing-dashboard {
        margin: 1.5rem 1.5rem 1.5rem;
    }

    @media (max-width: 991px) {
        .billing-dashboard {
            margin: 1.5rem 1rem;
        }
    }
</style>
@endsection
