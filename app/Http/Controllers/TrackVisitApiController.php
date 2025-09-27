<?php

namespace App\Http\Controllers;

use App\Jobs\TrackVisitJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TrackVisitApiController extends Controller
{
    /**
     * POST /api/v1/visits/track
     * Body (json, optional): { path?: string, ref?: string }
     */
    public function store(Request $request)
    {
        $sessionId = $request->cookie('visitor_id') ?? ($request->input('visitor_id') ?: Str::uuid()->toString());

        TrackVisitJob::dispatch([
            'user_id'    => Auth::id(),
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'visited_at' => now(),
        ])->onQueue('tracking');

        // 204 No Content with cookie so client can reuse visitor_id
        return response()->noContent()->withCookie(cookie('visitor_id', $sessionId, 60 * 24 * 365));
    }

    /**
     * GET /api/v1/visits/track
     * Query: visitor_id?, path?, ref?
     * Allows tracking via a simple GET (useful for <img> beacon or fetch GET).
     */
    public function track(Request $request)
    {
        $sessionId = $request->cookie('visitor_id') ?? ($request->query('visitor_id') ?: Str::uuid()->toString());

        TrackVisitJob::dispatch([
            'user_id'    => Auth::id(),
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'visited_at' => now(),
        ])->onQueue('tracking');

        // 204 No Content + set cookie so client can reuse visitor_id
        return response()->noContent()->withCookie(cookie('visitor_id', $sessionId, 60 * 24 * 365));
    }
}
