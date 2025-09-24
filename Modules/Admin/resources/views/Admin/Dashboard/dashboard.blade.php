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
                    <a href="javascript:void(0);" class="btn btn-md btn-light-brand">
                        <i class="feather-filter me-3"></i>
                        <span>Manage Filter</span>
                    </a>
                </div>
                <div class="d-md-none d-flex align-items-center">
                    <a href="javascript:void(0)" class="page-header-right-open-toggle">
                        <i class="feather-align-right fs-20"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="crm"></div>
    <div class="row">
        <!-- Users Total -->
        <div class="col-xxl-3 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-users"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-dark">1,250</div>
                                <h3 class="fs-13 fw-semibold text-truncate-1-line">Users</h3>
                            </div>
                        </div>
                        <a href="javascript:void(0);"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="pt-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <a href="javascript:void(0);" class="fs-12 fw-medium text-muted text-truncate-1-line">Total Users</a>
                            <div class="w-100 text-end">
                                <span class="fs-12 text-dark">1,250</span>
                                <span class="fs-11 text-muted">(72%)</span>
                            </div>
                        </div>
                        <div class="progress mt-2 ht-3">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 72%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Resumes Total -->
        <div class="col-xxl-3 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-file-text"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-dark">840</div>
                                <h3 class="fs-13 fw-semibold text-truncate-1-line">Resumes</h3>
                            </div>
                        </div>
                        <a href="javascript:void(0);"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="pt-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <a href="javascript:void(0);" class="fs-12 fw-medium text-muted text-truncate-1-line">Total Resumes</a>
                            <div class="w-100 text-end">
                                <span class="fs-12 text-dark">840</span>
                                <span class="fs-11 text-muted">(58%)</span>
                            </div>
                        </div>
                        <div class="progress mt-2 ht-3">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 58%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Applications Total -->
        <div class="col-xxl-3 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-briefcase"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-dark">365</div>
                                <h3 class="fs-13 fw-semibold text-truncate-1-line">Applications</h3>
                            </div>
                        </div>
                        <a href="javascript:void(0);"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="pt-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <a href="javascript:void(0);" class="fs-12 fw-medium text-muted text-truncate-1-line">Total Applications </a>
                            <div class="w-100 text-end">
                                <span class="fs-12 text-dark">365</span>
                                <span class="fs-11 text-muted">(44%)</span>
                            </div>
                        </div>
                        <div class="progress mt-2 ht-3">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 44%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Telegram Channels -->
        <div class="col-xxl-3 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="avatar-text avatar-lg bg-gray-200">
                                <i class="feather-send"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-dark">26</div>
                                <h3 class="fs-13 fw-semibold text-truncate-1-line">Telegram Channels</h3>
                            </div>
                        </div>
                        <a href="javascript:void(0);"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="pt-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <a href="javascript:void(0);" class="fs-12 fw-medium text-muted text-truncate-1-line">Target: 1 • Source: 25</a>
                            <div class="w-100 text-end">
                                <span class="fs-12 text-dark">26</span>
                                <span class="fs-11 text-muted">(4% target)</span>
                            </div>
                        </div>
                        <div class="progress mt-2 ht-3">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 4%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xxl-8">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Payment Record</h5>
                    <div class="card-header-action">
                        <div class="card-header-btn">
                            <a href="javascript:void(0);" class="avatar-text avatar-xs bg-danger" data-bs-toggle="remove"></a>
                            <a href="javascript:void(0);" class="avatar-text avatar-xs bg-warning" data-bs-toggle="refresh"></a>
                            <a href="javascript:void(0);" class="avatar-text avatar-xs bg-success" data-bs-toggle="expand"></a>
                        </div>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown" data-bs-offset="25, 25">
                                <div data-bs-toggle="tooltip" title="Options">
                                    <i class="feather-more-vertical"></i>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-at-sign"></i>New</a>
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-calendar"></i>Event</a>
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-bell"></i>Snoozed</a>
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-trash-2"></i>Deleted</a>
                                <div class="dropdown-divider"></div>
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-settings"></i>Settings</a>
                                <a href="javascript:void(0);" class="dropdown-item"><i class="feather-life-buoy"></i>Tips & Tricks</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body custom-card-action p-0">
                    <div id="payment-records-chart"></div>
                </div>
                <div class="card-footer">
                    <div class="row g-4">
                        <div class="col-lg-3">
                            <div class="p-3 border border-dashed rounded">
                                <div class="fs-12 text-muted mb-1">Awaiting</div>
                                <h6 class="fw-bold text-dark">$5,486</h6>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 45%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="p-3 border border-dashed rounded">
                                <div class="fs-12 text-muted mb-1">Completed</div>
                                <h6 class="fw-bold text-dark">$12,320</h6>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 82%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="p-3 border border-dashed rounded">
                                <div class="fs-12 text-muted mb-1">Rejected</div>
                                <h6 class="fw-bold text-dark">$1,120</h6>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 22%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="p-3 border border-dashed rounded">
                                <div class="fs-12 text-muted mb-1">Refunded</div>
                                <h6 class="fw-bold text-dark">$680</h6>
                                <div class="progress mt-2 ht-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 18%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="card-title"><h5 class="mb-0">Visitors Overview</h5></div>
                </div>
                <div class="card-body"><div id="visitors-overview-statistics-chart"></div></div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card stretch">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h5 class="mb-0">Campaign Analytics</h5></div>
                </div>
                <div class="card-body"><div id="campaign-alytics-bar-chart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xxl-4 col-md-6">
            <div class="card"><div class="card-body"><h6 class="mb-2">Bounce Rate</h6><div id="bounce-rate"></div></div></div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card"><div class="card-body"><h6 class="mb-2">Page Views</h6><div id="page-views"></div></div></div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card"><div class="card-body"><h6 class="mb-2">Site Impressions</h6><div id="site-impressions"></div></div></div>
        </div>
        <div class="col-xxl-4 col-md-6">
            <div class="card"><div class="card-body"><h6 class="mb-2">Conversions Rate</h6><div id="conversions-rate"></div></div></div>
        </div>
        <div class="col-xxl-8 col-12">
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h5 class="mb-0">Leads Overview</h5></div>
                </div>
                <div class="card-body">
                    <div id="leads-overview-donut"></div>
                    <div class="row g-2">
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #3454d1"></span><span>New<span class="fs-10 text-muted ms-1">(20K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #0d519e"></span><span>Contacted<span class="fs-10 text-muted ms-1">(15K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #1976d2"></span><span>Qualified<span class="fs-10 text-muted ms-1">(10K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #1e88e5"></span><span>Working<span class="fs-10 text-muted ms-1">(18K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #2196f3"></span><span>Customer<span class="fs-10 text-muted ms-1">(10K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #42a5f5"></span><span>Proposal<span class="fs-10 text-muted ms-1">(15K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #64b5f6"></span><span>Leads<span class="fs-10 text-muted ms-1">(16K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #90caf9"></span><span>Progress<span class="fs-10 text-muted ms-1">(14K)</span></span></a></div>
                        <div class="col-4"><a href="javascript:void(0);" class="p-2 hstack gap-2 rounded border border-dashed border-gray-5"><span class="wd-7 ht-7 rounded-circle d-inline-block" style="background-color: #aad6fa"></span><span>Others<span class="fs-10 text-muted ms-1">(10K)</span></span></a></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-12">
            <div class="card stretch stretch-full">
                <div class="card-header"><h5 class="card-title">Team Progress</h5></div>
                <div class="card-body">
                    <div class="hstack justify-content-between border border-dashed rounded-3 p-3 mb-3">
                        <div class="hstack gap-3">
                            <div class="avatar-image"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/1.png') }}" alt="" class="img-fluid" /></div>
                            <div><a href="javascript:void(0);">James Cameron</a><div class="fs-11 text-muted">Frontend Developer</div></div>
                        </div>
                        <div class="team-progress-1"></div>
                    </div>
                    <div class="hstack justify-content-between border border-dashed rounded-3 p-3 mb-3">
                        <div class="hstack gap-3">
                            <div class="avatar-image"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/2.png') }}" alt="" class="img-fluid" /></div>
                            <div><a href="javascript:void(0);">Archie Cantones</a><div class="fs-11 text-muted">UI/UX Designer</div></div>
                        </div>
                        <div class="team-progress-2"></div>
                    </div>
                    <div class="hstack justify-content-between border border-dashed rounded-3 p-3 mb-3">
                        <div class="hstack gap-3">
                            <div class="avatar-image"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/3.png') }}" alt="" class="img-fluid" /></div>
                            <div><a href="javascript:void(0);">Malanie Hanvey</a><div class="fs-11 text-muted">Backend Developer</div></div>
                        </div>
                        <div class="team-progress-3"></div>
                    </div>
                    <div class="hstack justify-content-between border border-dashed rounded-3 p-3 mb-2">
                        <div class="hstack gap-3">
                            <div class="avatar-image"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/4.png') }}" alt="" class="img-fluid" /></div>
                            <div><a href="javascript:void(0);">Kenneth Hune</a><div class="fs-11 text-muted">Digital Marketer</div></div>
                        </div>
                        <div class="team-progress-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xxl-4">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Upcoming Schedule</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                        <div class="hstack gap-3">
                            <div class="avatar-text avatar-md bg-primary text-white"><i class="feather-calendar"></i></div>
                            <div>
                                <div class="fw-semibold">Sprint Planning</div>
                                <div class="fs-11 text-muted">Tomorrow • 10:00 AM</div>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="avatar-text avatar-sm"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                        <div class="hstack gap-3">
                            <div class="avatar-text avatar-md bg-warning text-white"><i class="feather-briefcase"></i></div>
                            <div>
                                <div class="fw-semibold">Client Demo</div>
                                <div class="fs-11 text-muted">Fri • 02:30 PM</div>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="avatar-text avatar-sm"><i class="feather-more-vertical"></i></a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-3">
                        <div class="hstack gap-3">
                            <div class="avatar-text avatar-md bg-success text-white"><i class="feather-users"></i></div>
                            <div>
                                <div class="fw-semibold">Team Retrospective</div>
                                <div class="fs-11 text-muted">Mon • 04:00 PM</div>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="avatar-text avatar-sm"><i class="feather-more-vertical"></i></a>
                    </div>
                </div>
                <a href="javascript:void(0);" class="card-footer fs-11 fw-bold text-uppercase text-center">View All</a>
            </div>
        </div>
        <div class="col-xxl-8 col-12">
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h5 class="mb-0">Social Radar</h5></div>
                </div>
                <div class="card-body"><div id="social-radar-chart"></div></div>
            </div>
            <div class="card mt-3">
                <div class="card-header align-items-center justify-content-between">
                    <div class="card-title"><h5 class="mb-0">Countdowns</h5></div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="border rounded p-3" data-time-countdown="countdown_1"></div></div>
                        <div class="col-md-6"><div class="border rounded p-3" data-time-countdown="countdown_2"></div></div>
                        <div class="col-md-6"><div class="border rounded p-3" data-time-countdown="countdown_3"></div></div>
                        <div class="col-md-6"><div class="border rounded p-3" data-time-countdown="countdown_4"></div></div>
                        <div class="col-12"><div class="border rounded p-3" data-time-countdown="countdown_5"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
