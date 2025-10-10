<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>InterAI</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/avatar/5.svg">

    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <!--! END: Bootstrap CSS-->
    <!--! BEGIN: Vendors CSS-->
    <link rel="stylesheet" type="text/css" href="/assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/vendors/css/select2-theme.min.css">
    <!--! END: Vendors CSS-->
    <!--! BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="/assets/css/theme.min.css">
</head>
<body>
    @include('admin::components.partials.sidebar')
    @include('admin::components.partials.header')

    <main class="nxl-container">
        <div class="nxl-content">
            @yield('content')
        </div>
    </main>

    @php
        $assetWithVersion = function ($path) {
            $fullPath = public_path($path);
            $version = file_exists($fullPath) ? filemtime($fullPath) : null;
            return asset($path) . ($version ? '?v=' . $version : '');
        };
    @endphp

    <script src="{{ $assetWithVersion('assets/vendors/js/vendors.min.js') }}"></script>
    <!-- vendors.min.js {always must need to be top} -->
    <script src="{{ $assetWithVersion('assets/vendors/js/select2.min.js') }}"></script>
    <script src="{{ $assetWithVersion('assets/vendors/js/select2-active.min.js') }}"></script>
    <!--! END: Vendors JS !-->
    <!--! BEGIN: Apps Init  !-->
    <script src="{{ $assetWithVersion('assets/js/common-init.min.js') }}"></script>
    <script src="{{ $assetWithVersion('assets/js/apps-storage-init.min.js') }}"></script>
    <!--! END: Apps Init !-->
    <!--! BEGIN: Theme Customizer  !-->
   
    <script src="{{ $assetWithVersion('assets/js/visitors-custom.js') }}"></script>

    <script src="{{ $assetWithVersion('assets/js/analytics-custom.js') }}"></script>

    <script src="{{ $assetWithVersion('assets/js/app.js') }}"></script>

    <script src="{{ $assetWithVersion('assets/js/dashboard-custom.js') }}"></script>

    <script src="{{ $assetWithVersion('assets/js/mini-charts-custom.js') }}"></script>


</body>
</html>
