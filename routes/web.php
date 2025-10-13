<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'message' => 'Resource not found.',
        ], 404);
    }

    return response()->view('errors.404', [], 404);
});
