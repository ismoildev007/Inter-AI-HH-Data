<?php

namespace Modules\ResumeCreate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ResumeCreateController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'module' => 'ResumeCreate',
        ]);
    }
}

