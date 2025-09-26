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
            <!-- <div class="dropdown nxl-h-item nxl-header-language d-none d-sm-flex">
                <a href="javascript:void(0);" class="nxl-head-link me-0 nxl-language-link" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/4x3/us.svg') }}" alt="lang" class="img-fluid wd-20">
                </a>
                <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-language-dropdown">
                    <div class="dropdown-divider mt-0"></div>
                    <div class="row px-4 pt-3">
                        <div class="col-6 language_select">
                            <a href="javascript:void(0);" class="d-flex align-items-center gap-2">
                                <div class="avatar-image avatar-sm"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/1x1/us.svg') }}" alt="" class="img-fluid"></div>
                                <span>English</span>
                            </a>
                        </div>
                        <div class="col-6 language_select">
                            <a href="javascript:void(0);" class="d-flex align-items-center gap-2">
                                <div class="avatar-image avatar-sm"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/1x1/sa.svg') }}" alt="" class="img-fluid"></div>
                                <span>Arabic</span>
                            </a>
                        </div>
                        <div class="col-6 language_select">
                            <a href="javascript:void(0);" class="d-flex align-items-center gap-2">
                                <div class="avatar-image avatar-sm"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/1x1/bd.svg') }}" alt="" class="img-fluid"></div>
                                <span>Bengali</span>
                            </a>
                        </div>
                        <div class="col-6 language_select">
                            <a href="javascript:void(0);" class="d-flex align-items-center gap-2">
                                <div class="avatar-image avatar-sm"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/1x1/ch.svg') }}" alt="" class="img-fluid"></div>
                                <span>Chinese</span>
                            </a>
                        </div>
                        <div class="col-6 language_select">
                            <a href="javascript:void(0);" class="d-flex align-items-center gap-2">
                                <div class="avatar-image avatar-sm"><img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/vendors/img/flags/1x1/nl.svg') }}" alt="" class="img-fluid"></div>
                                <span>Dutch</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div> -->

            <<div class="nxl-h-item d-flex align-items-center gap-2">
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
    /* Faqat profil uchun qoida */
    .nxl-h-item .nxl-head-link.profile-link {
        background-color: #3b82f6 !important;
        /* blue-500 */
        transition: background-color 0.3s ease;
    }

    .nxl-h-item .nxl-head-link.profile-link:hover {
        background-color: #1d4ed8 !important;
        /* blue-700 */
    }

    /* Logout tugmasi uchun qoida */
    .nxl-h-item .nxl-head-link.logout-link {
        background-color: #ef4444 !important;
        /* red-500 */
        transition: background-color 0.3s ease;
    }

    .nxl-h-item .nxl-head-link.logout-link:hover {
        background-color: #b91c1c !important;
        /* red-700 */
    }
</style>