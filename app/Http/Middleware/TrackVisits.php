<?php

namespace App\Http\Middleware;

use App\Jobs\TrackVisitJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class TrackVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->cookie('visitor_id') ?? Str::uuid()->toString();

        TrackVisitJob::dispatch([
            'user_id'    => Auth::id(),
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'visited_at' => now(),
        ])->onQueue('tracking');

        return $next($request)->withCookie(cookie('visitor_id', $sessionId, 60 * 24 * 30));
    }
}
