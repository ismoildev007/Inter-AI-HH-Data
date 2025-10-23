<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $availableLocales = ['uz', 'ru', 'en'];
        $current = session('locale');

        if ($request->has('lang')) {
            $requested = $request->string('lang')->lower()->toString();
            if (in_array($requested, $availableLocales, true)) {
                $current = $requested;
                session(['locale' => $current]);
            }
        }

        if (! in_array($current, $availableLocales, true)) {
            $current = config('app.locale');
        }

        app()->setLocale($current);

        return $next($request);
    }
}
