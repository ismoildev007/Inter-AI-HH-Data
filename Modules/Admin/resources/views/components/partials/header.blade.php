<header class="nxl-header">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display:none"><i class="feather-arrow-right"></i></a>
            </div>
        </div>

        <div class="header-right ms-auto d-flex align-items-center gap-3">
            <div class="nxl-h-item d-flex align-items-center gap-2">



                <a href="javascript:void(0);" 
                   onclick="window.location.reload();" 
                   class="nxl-head-link refresh-link text-white ms-5 me-5" 
                   title="Refresh">
                   Refresh・
                    <i class="feather-refresh-cw"></i>
                </a>


                <!-- Profil -->
                <a href="{{ route('admin.profile') }}"
                    class="nxl-head-link profile-link me-0 text-white"
                    title="Profile">
                    Profil・
                    <div class="avatar-text avatar-md">
                        <img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/avatar/5.svg') }}"
                            alt=""
                            class="img-fluid">
                    </div>
                </a>

                <!-- Refresh tugma -->


                <!-- Logout -->
                <a href="{{ route('admin.logout') }}"
                    class="nxl-head-link logout-link text-white"
                    title="Logout">
                    <i class="feather-log-out"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<style>
    /* Profil */
    .nxl-h-item .nxl-head-link.profile-link {
        background-color: #3b82f6 !important; /* blue-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.profile-link:hover {
        background-color: #1d4ed8 !important; /* blue-700 */
    }

    /* Refresh */
    .nxl-h-item .nxl-head-link.refresh-link {
        background-color: #10b981 !important; /* green-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.refresh-link:hover {
        background-color: #047857 !important; /* green-700 */
    }

    /* Logout */
    .nxl-h-item .nxl-head-link.logout-link {
        background-color: #ef4444 !important; /* red-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.logout-link:hover {
        background-color: #b91c1c !important; /* red-700 */
    }
</style>