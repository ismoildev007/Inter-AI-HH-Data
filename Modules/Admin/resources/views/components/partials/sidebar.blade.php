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

                <li class="nxl-item">
                    <a href="{{ route('admin.dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
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

                <li class="nxl-item">
                    <a href="{{ route('admin.telegram_channels.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-send"></i></span>
                        <span class="nxl-mtext">Telegram Channels</span>
                    </a>
                </li>

                
            </ul>
        </div>
    </div>
</nav>
