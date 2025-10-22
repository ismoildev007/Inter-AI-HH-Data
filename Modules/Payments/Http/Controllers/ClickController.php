<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClickController extends Controller
{
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

        $transaction = Transaction::create([
            'user_id' => Auth::id() ?? null,
            'plan_id' => $plan->id,
            'payment_method' => 'click',
            'payment_status' => 'prepared',
            'transaction_id' => $request->click_trans_id,
            'state' => 1,
            'amount' => $request->amount,
            'create_time' => now(),
        ]);

        return response()->json([
            'click_trans_id' => $request->click_trans_id,
            'merchant_trans_id' => $plan->id,
            'merchant_prepare_id' => $transaction->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    public function complete(Request $request)
    {
        Log::info('Click complete', $request->all());

        // imzo tekshirish
        if (!$this->checkSignature($request)) {
            return response()->json(['error' => -1, 'error_note' => 'Invalid signature']);
        }

        $transaction = Transaction::find($request->merchant_prepare_id);
        if (!$transaction) {
            return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
        }

        $plan = Plan::find($transaction->plan_id);
        if (!$plan) {
            return response()->json(['error' => -5, 'error_note' => 'Plan not found']);
        }

        $transaction->update([
            'payment_status' => 'completed',
            'state' => 2,
            'perform_time' => now(),
        ]);

        $subscription = Subscription::create([
            'user_id' => $transaction->user_id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'remaining_auto_responses' => $plan->auto_response_limit,
            'status' => 'active',
        ]);

        $transaction->update(['subscription_id' => $subscription->id]);

        return response()->json([
            'click_trans_id' => $request->click_trans_id,
            'merchant_trans_id' => $transaction->plan_id,
            'merchant_confirm_id' => $transaction->id,
            'error' => 0,
            'error_note' => 'Payment completed successfully',
        ]);
    }

    private function checkSignature(Request $request)
    {
        $signString = md5(
            $request->click_trans_id .
            $request->service_id .
            env('CLICK_SECRET_KEY') .
            $request->merchant_trans_id .
            $request->amount .
            $request->action .
            $request->sign_time
        );

        return $signString === $request->sign_string;
    }
}
