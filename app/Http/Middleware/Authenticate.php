<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        return null; // redirect emas
    }
    protected function unauthenticated($request, array $guards)
    {
        // ApiResponseTrait formatida qaytarish
        abort(response()->json([
            'status'  => 'error',
            'message' => 'Unauthenticated',
        ], 401));
    }
}
