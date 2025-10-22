<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClickController extends Controller
{
    public function handleCallback()
    {

    }

    public function prepare(Request $request)
    {
        Log::info('Click prepare', $request->all());

        // sign tekshirish
        if (!$this->checkSignature($request)) {
            return response()->json(['error' => -1, 'error_note' => 'Invalid signature']);
        }

        $plan = Plan::find($request->merchant_trans_id);

        if (!$plan) {
            return response()->json(['error' => -5, 'error_note' => 'Plan not found']);
        }

        

    }
}
