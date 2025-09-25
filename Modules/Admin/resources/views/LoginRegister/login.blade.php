<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin Login - {{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/favicon.ico') }}">

        {{ module_vite('build-admin', 'resources/assets/sass/app.scss') }}
    </head>
    <body>
        <main class="auth-minimal-wrapper">
            <div class="auth-minimal-inner">
                <div class="minimal-card-wrapper">
                    <div class="card mb-4 mt-5 mx-4 mx-sm-0 position-relative" style="z-index:1;">
                        <div class="wd-50 bg-white p-2 rounded-circle shadow-lg position-absolute translate-middle top-0 start-50">
                            <img src="{{ module_vite('build-admin', 'resources/assets/js/app.js')->asset('resources/assets/images/logo-abbr.png') }}" alt="Logo" class="img-fluid">
                        </div>
                        <div class="card-body p-sm-5">
                            <h2 class="fs-20 fw-bolder mb-4">Login</h2>
                            <h4 class="fs-13 fw-bold mb-2">Login to your account</h4>
                            <p class="fs-12 fw-medium text-muted">Welcome back to {{ config('app.name') }} admin.</p>

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.login.attempt') }}" class="w-100 mt-4 pt-2">
                                @csrf
                                <div class="mb-4">
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" value="{{ old('email') }}" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label c-pointer" for="rememberMe">Remember Me</label>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <button type="submit" class="btn btn-lg btn-primary w-100">Login</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        {{ module_vite('build-admin', 'resources/assets/js/app.js') }}
    </body>
    </html>
