<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>Admin - {{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/favicon.ico') }}">

        {{ module_vite('build-admin', 'resources/assets/sass/app.scss') }}
    </head>
    <body>
        @include('admin::components.partials.sidebar')
        @include('admin::components.partials.header')

        <main class="nxl-container">
            <div class="nxl-content">
                @yield('content')
            </div>
        </main>

        {{ module_vite('build-admin', 'resources/assets/js/app.js') }}
    </body>
</html>
