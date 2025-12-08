<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AttachQueryToken
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('token') && ! $request->bearerToken()) {
            $token = (string) $request->query('token');

            if ($token !== '') {
                $request->headers->set('Authorization', 'Bearer '.$token);
            }
        }

        return $next($request);
    }
}

