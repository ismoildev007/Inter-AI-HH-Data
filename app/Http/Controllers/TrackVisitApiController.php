<?php

namespace App\Http\Controllers;

use App\Jobs\TrackVisitJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $uid = Auth::id();
        if (!$uid) {
            $raw = $request->input('user_id', $request->input('uid'));
            if (is_numeric($raw) && (int)$raw > 0) { $uid = (int)$raw; }
        }

        // Synchronous insert so dashboard updates immediately (no worker dependency)
        DB::table('visits')->insert([
            'user_id'    => $uid ?: null,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source'     => 'api',
            'visited_at' => now(),
            'created_at' => now(),
        ]);

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

        $uid = Auth::id();
        
        if (!$uid) {
            $raw = $request->query('user_id', $request->query('uid'));
            if (is_numeric($raw) && (int)$raw > 0) { $uid = (int)$raw; }
        }

        DB::table('visits')->insert([
            'user_id'    => $uid ?: null,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source'     => 'api',
            'visited_at' => now(),
            'created_at' => now(),
        ]);

        // 204 No Content + set cookie so client can reuse visitor_id
        return response()->noContent()->withCookie(cookie('visitor_id', $sessionId, 60 * 24 * 365));
    }
}
