<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>InterAI</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('build-admin/assets/images/avatar/5.svg') }}">

    @vite([
        'Modules/Admin/Resources/assets/sass/app.scss',
        'Modules/Admin/Resources/assets/js/app.js'
    ], 'build-admin')
</head>
<body>
    @include('admin::components.partials.sidebar')
    @include('admin::components.partials.header')

    <main class="nxl-container">
        <div class="nxl-content">
            @yield('content')
        </div>
    </main>
</body>
</html>
