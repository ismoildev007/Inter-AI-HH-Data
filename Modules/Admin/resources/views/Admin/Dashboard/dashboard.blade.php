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
        <div class="d-flex align-items-center gap-2">
            <div id="reportrange" class="reportrange-picker d-flex align-items-center">
                <span class="reportrange-picker-field"></span>
            </div>
            <a href="javascript:void(0);" class="btn btn-md btn-light-brand">
                <i class="feather-filter me-2"></i> Manage Filter
            </a>
        </div>
    </div>
</div>

<div class="main-content">
    <!-- Statistik kartalar -->
    <div class="row g-3">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="avatar-text avatar-lg bg-gray-200"><i class="feather-users"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $usersCount }}</h4>
                            <small class="text-muted">Users</small>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="avatar-text avatar-lg bg-gray-200"><i class="feather-file-text"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $resumesCount }}</h4>
                            <small class="text-muted">Resumes</small>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="avatar-text avatar-lg bg-gray-200"><i class="feather-briefcase"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $applicationsCount }}</h4>
                            <small class="text-muted">Applications</small>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="avatar-text avatar-lg bg-gray-200"><i class="feather-send"></i></div>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $telegramChannelsCount }}</h4>
                            <small class="text-muted">Telegram Channels</small>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Visitors + Tasks -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Visitors Overview</h5></div>
                <div class="card-body"><div id="visitors-overview-chart"></div></div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Users (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['users'] ?? 0 }}</span>
                            </div>
                            <div id="task-completed-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Applications (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['applications'] ?? 0 }}</span>
                            </div>
                            <div id="new-tasks-area-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Resumes (7d)</span>
                                <span class="fw-bold">{{ $miniTotals['resumes'] ?? 0 }}</span>
                            </div>
                            <div id="project-done-area-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign -->
    <!-- <div class="row g-3 mt-1">
        <div class="col-xxl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Campaign Analytics</h5></div>
                <div class="card-body"><div id="campaign-alytics-bar-chart"></div></div>
            </div>
        </div>
    </div> -->

    <!-- Metrics + Leads + Team -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100"><div class="card-body"><h6>Bounce Rate</h6><div id="bounce-rate"></div></div></div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100"><div class="card-body"><h6>Page Views</h6><div id="page-views"></div></div></div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100"><div class="card-body"><h6>Site Impressions</h6><div id="site-impressions"></div></div></div>
        </div>
        <div class="col-xxl-3 col-md-6">
            <div class="card h-100"><div class="card-body"><h6>Conversions Rate</h6><div id="conversions-rate"></div></div></div>
        </div>
        <div class="col-xxl-8 col-12">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Leads Overview</h5></div>
                <div class="card-body"><div id="leads-overview-donut"></div></div>
            </div>
        </div>
        <div class="col-xxl-4 col-12">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Team Progress</h5></div>
                <div class="card-body">
                    <div class="hstack justify-content-between border rounded-3 p-2 mb-2">
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ module_vite('build-admin','resources/assets/js/app.js')->asset('resources/assets/images/avatar/5.svg') }}" class="img-fluid rounded-circle" width="35">
                            <div><b>James Cameron</b><br><small class="text-muted">Frontend</small></div>
                        </div>
                        <div class="team-progress-1"></div>
                    </div>
                    <div class="hstack justify-content-between border rounded-3 p-2 mb-2">
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ module_vite('build-admin','resources/assets/js/app.js')->asset('resources/assets/images/avatar/2.png') }}" class="img-fluid rounded-circle" width="35">
                            <div><b>Archie Cantones</b><br><small class="text-muted">UI/UX</small></div>
                        </div>
                        <div class="team-progress-2"></div>
                    </div>
                    <div class="hstack justify-content-between border rounded-3 p-2 mb-2">
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ module_vite('build-admin','resources/assets/js/app.js')->asset('resources/assets/images/avatar/3.png') }}" class="img-fluid rounded-circle" width="35">
                            <div><b>Malanie Hanvey</b><br><small class="text-muted">Backend</small></div>
                        </div>
                        <div class="team-progress-3"></div>
                    </div>
                    <div class="hstack justify-content-between border rounded-3 p-2">
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ module_vite('build-admin','resources/assets/js/app.js')->asset('resources/assets/images/avatar/4.png') }}" class="img-fluid rounded-circle" width="35">
                            <div><b>Kenneth Hune</b><br><small class="text-muted">Marketer</small></div>
                        </div>
                        <div class="team-progress-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Schedule + Social Radar -->
    <div class="row g-3 mt-1">
        <div class="col-xxl-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Upcoming Schedule</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="avatar-text bg-primary text-white"><i class="feather-calendar"></i></div>
                            <div><b>Sprint Planning</b><br><small class="text-muted">Tomorrow • 10:00</small></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="avatar-text bg-warning text-white"><i class="feather-briefcase"></i></div>
                            <div><b>Client Demo</b><br><small class="text-muted">Fri • 2:30</small></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="avatar-text bg-success text-white"><i class="feather-users"></i></div>
                            <div><b>Team Retro</b><br><small class="text-muted">Mon • 4:00</small></div>
                        </div>
                    </div>
                </div>
                <a href="#" class="card-footer text-center">View All</a>
            </div>
        </div>
        <div class="col-xxl-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Social Radar</h5></div>
                <div class="card-body"><div id="social-radar-chart"></div></div>
            </div>
        </div>
    </div>
<script>
    window.visitorsChart = {
        labels: @json($visitorsLabels ?? []),
        series: @json($visitorsSeries ?? [])
    };
    window.totalVisits = (window.visitorsChart.series || []).reduce((a,b)=>a+(+b||0),0);
    window.dashboardMini = {
        labels: @json($dayLabels ?? []),
        series: {
            users: @json($miniUsers ?? []),
            applications: @json($miniApplications ?? []),
            resumes: @json($miniResumes ?? []),
        }
    };
  </script>
</div><!-- /.main-content -->
@endsection
