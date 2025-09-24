<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('admin.dashboard') }}" class="b-brand">
                <img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/logo-full.png') }}" alt="" class="logo logo-lg">
                <img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/logo-abbr.png') }}" alt="" class="logo logo-sm">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption"><label>Navigation</label></li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboards</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.dashboard') }}#crm">CRM</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.dashboard') }}#analytics">Analytics</a></li>
                    </ul>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.users.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-users"></i></span>
                        <span class="nxl-mtext">Users</span>
                    </a>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.resumes.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-file-text"></i></span>
                        <span class="nxl-mtext">Resumes</span>
                    </a>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.applications.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                        <span class="nxl-mtext">Applications</span>
                    </a>
                </li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-send"></i></span>
                        <span class="nxl-mtext">Telegram Channels</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.telegram_channels.index') }}">List</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="{{ route('admin.telegram_channels.create') }}">Create</a></li>
                    </ul>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.profile') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-user"></i></span>
                        <span class="nxl-mtext">Profile</span>
                    </a>
                </li>

                <li class="nxl-item">
                    <a href="{{ route('admin.logout') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-log-out"></i></span>
                        <span class="nxl-mtext">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

