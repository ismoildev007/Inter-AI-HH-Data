@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Dashboard</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <div id="reportrange" class="reportrange-picker d-flex align-items-center">
                        <span class="reportrange-picker-field"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="crm"></div>
    <div class="row g-3">
        <div class="col-xxl-8">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title">
                        <h5 class="mb-0">Payment Records</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="payment-records-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card stretch">
                <div class="card-body">
                    <h6 class="mb-3">Total Sales</h6>
                    <div id="total-sales-color-graph"></div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Task Completed</span>
                                <span class="fw-bold">44</span>
                            </div>
                            <div id="task-completed-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">New Tasks</span>
                                <span class="fw-bold">55</span>
                            </div>
                            <div id="new-tasks-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Project Done</span>
                                <span class="fw-bold">60</span>
                            </div>
                            <div id="project-done-area-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="analytics" class="mt-4"></div>
    <div class="row g-3">
        <div class="col-xxl-8">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title">
                        <h5 class="mb-0">Visitors Overview</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="visitors-overview-statistics-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title">
                        <h5 class="mb-0">Campaign Analytics</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="campaign-alytics-bar-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Bounce Rate</h6>
                    <div id="bounce-rate"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Page Views</h6>
                    <div id="page-views"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Site Impressions</h6>
                    <div id="site-impressions"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Conversions Rate</h6>
                    <div id="conversions-rate"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-8 col-12">
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title">
                        <h5 class="mb-0">Social Radar</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="social-radar-chart"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
