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
        //Log::info('🟡 CLICK PREPARE: started', $request->all());

        if (!$this->checkSignature($request)) {
            Log::warning('Invalid signature in prepare', $request->all());
            return response()->json(['error' => -1, 'error_note' => 'Invalid signature']);
        }

        $transaction = Transaction::find($request->merchant_trans_id);
        if (!$transaction) {
            Log::error('Transaction not found', ['merchant_trans_id' => $request->merchant_trans_id]);
            return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
        }

        $plan = Plan::find($transaction->plan_id);
        if (!$plan) {
            Log::error('Plan not found', ['plan_id' => $transaction->plan_id]);
            return response()->json(['error' => -5, 'error_note' => 'Plan not found']);
        }

        if ((float)$plan->price != (float)$request->amount) {
            Log::error('Amount mismatch', ['plan_price' => $plan->price, 'req_amount' => $request->amount]);
            return response()->json(['error' => -2, 'error_note' => 'Incorrect amount']);
        }

        $transaction->update([
            'payment_status' => 'prepared',
            'transaction_id' => $request->click_trans_id,
            'state' => 1,
            'sign_time' => $request->sign_time,
        ]);

      //  Log::info('Transaction updated to prepared', ['transaction_id' => $transaction->id]);

        $response = [
            'click_trans_id' => $request->click_trans_id,
            'merchant_trans_id' => (string)$transaction->id,
            'merchant_prepare_id' => (int)$transaction->id,
            'error' => 0,
            'error_note' => 'Success',
        ];

       // Log::info('Transaction prepared response', $response);

        return response()->json($response);
    }

    public function complete(Request $request)
    {
        // Log::info('CLICK COMPLETE: started', [
        //     'click_trans_id' => $request->click_trans_id ?? null,
        //     'merchant_trans_id' => $request->merchant_trans_id ?? null,
        //     'merchant_prepare_id' => $request->merchant_prepare_id ?? null,
        // ]);

        try {
            // 1) Asosiy validatsiya — zarur maydonlar
            $required = ['click_trans_id', 'service_id', 'merchant_trans_id', 'amount', 'action', 'sign_time', 'sign_string'];
            foreach ($required as $f) {
                if (!$request->has($f)) {
                    Log::warning("CLICK COMPLETE: missing field {$f}", $request->all());
                    return response()->json(['error' => -8, 'error_note' => "Missing field {$f}"]);
                }
            }

            if (!$this->checkSignature($request)) {
                Log::warning('Invalid signature in complete', [
                    'merchant_trans_id' => $request->merchant_trans_id,
                    'merchant_prepare_id' => $request->merchant_prepare_id,
                ]);
                return response()->json(['error' => -1, 'error_note' => 'Invalid signature']);
            }

            if (isset($request->error) && (int)$request->error !== 0) {
                $errorNote = $request->error_note ?? 'Unknown error';
            
                // 💡 Click'dan kelgan matnni UTF-8 ga o‘tkazamiz
                if (!mb_check_encoding($errorNote, 'UTF-8')) {
                    $errorNote = mb_convert_encoding($errorNote, 'UTF-8', 'Windows-1251');
                }
            
                Log::warning('CLICK COMPLETE: payment failed on Click side', [
                    'error' => $request->error,
                    'error_note' => $errorNote,
                    'transaction_id' => $request->merchant_trans_id,
                ]);
            
                $transaction = Transaction::find($request->merchant_trans_id);
                if ($transaction) {
                    $transaction->update([
                        'payment_status' => 'failed',
                        'state' => -1,
                        'sign_time' => now(),
                    ]);
                }
            
                return response()->json([
                    'click_trans_id' => $request->click_trans_id ?? null,
                    'merchant_trans_id' => (string)($transaction->id ?? $request->merchant_trans_id),
                    'merchant_confirm_id' => (int)($transaction->id ?? 0),
                    'error' => (int)$request->error,
                    'error_note' => $errorNote,
                ]);
            }
            

            // 2) Transactionni lock bilan oling (merchant_prepare_id birinchi navbatda, keyin merchant_trans_id)
            $txId = $request->merchant_prepare_id ?? $request->merchant_trans_id;
            $transaction = Transaction::where('id', $txId)->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('Transaction not found', [
                    'merchant_prepare_id' => $request->merchant_prepare_id,
                    'merchant_trans_id' => $request->merchant_trans_id
                ]);
                return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
            }

            // 3) Agar allaqachon completed bo'lsa idempotent javob
            if ((int)$transaction->state >= 2) {
               // Log::info('Transaction already completed (idempotent)', ['transaction_id' => $transaction->id]);
                return response()->json([
                    'click_trans_id' => $request->click_trans_id,
                    'merchant_trans_id' => (string)$transaction->id,
                    'merchant_confirm_id' => (int)$transaction->id,
                    'error' => 0,
                    'error_note' => 'Already completed',
                ]);
            }

            // 4) Tekshirishlar: service_id / amount mosligi va tranzaksiya avval prepared bo'lgan yoki yo'qligi
            $serviceId = (string)$request->service_id;
            $expectedServiceId = env('CLICK_SERVICE_ID');
            if ($expectedServiceId && $serviceId !== (string)$expectedServiceId) {
                Log::warning('Service ID mismatch', ['expected' => $expectedServiceId, 'received' => $serviceId]);
                return response()->json(['error' => -6, 'error_note' => 'Service mismatch']);
            }

            // amountni aniqlik bilan taqqoslang (float unsafe)
            if ((int)round($transaction->amount * 100) !== (int)round($request->amount * 100)) {
                Log::warning('Amount mismatch on complete', [
                    'tx_amount' => (string)$transaction->amount,
                    'req_amount' => (string)$request->amount
                ]);
                return response()->json(['error' => -2, 'error_note' => 'Incorrect amount']);
            }

            // for production
            // if (bccomp((string)$transaction->amount, (string)$request->amount, 2) !== 0) {
            //     Log::warning('Amount mismatch on complete', [
            //         'tx_amount' => (string)$transaction->amount,
            //         'req_amount' => (string)$request->amount
            //     ]);
            //     return response()->json(['error' => -2, 'error_note' => 'Incorrect amount']);
            // }
            

            // 5) Qo'shimcha: tranzaksiya prepared stateida bo'lishi kerak (state === 1)
            if ((int)$transaction->state !== 1) {
                Log::warning('Transaction not in prepared state', [
                    'transaction_id' => $transaction->id,
                    'state' => $transaction->state
                ]);
                return response()->json(['error' => -7, 'error_note' => 'Transaction not prepared for completion']);
            }

            // 6) Hamma tekshiruv muvaffaqiyatli bo'lsa — DB transaction ichida update qiling
            \DB::transaction(function () use ($transaction, $request, &$subscription) {
                $plan = Plan::find($transaction->plan_id);
                if (!$plan) {
                    throw new \Exception('Plan not found for transaction ' . $transaction->id);
                }

                $transaction->update([
                    'payment_status' => 'completed',
                    'state' => 2,
                    'transaction_id' => $request->click_trans_id, // saqlashni yangilash
                    'sign_time' => now(),
                ]);
               // Log::info('Transaction marked completed', ['transaction_id' => $transaction->id]);

                // subscription yaratish yoki yangilash
                $subscription = Subscription::find($transaction->subscription_id);

                if ($subscription) {
                    $subscription->update([
                        'starts_at' => now(),
                        'ends_at' => now()->addDays(30),
                        'remaining_auto_responses' => $plan->auto_response_limit ?? 0,
                        'status' => 'active',
                    ]);
                   // Log::info('Subscription updated to active', ['subscription_id' => $subscription->id]);
                } else {
                    $subscription = Subscription::create([
                        'user_id' => $transaction->user_id,
                        'plan_id' => $plan->id,
                        'starts_at' => now(),
                        'ends_at' => now()->addDays(30),
                        'remaining_auto_responses' => $plan->auto_response_limit ?? 0,
                        'status' => 'active',
                    ]);
                    $transaction->update(['subscription_id' => $subscription->id]);
                    Log::warning('New subscription created', ['subscription_id' => $subscription->id]);
                }
            });

            // Log::info('CLICK COMPLETE: finished successfully', [
            //     'transaction_id' => $transaction->id,
            //     'subscription_id' => $subscription->id ?? null
            // ]);

            return response()->json([
                'click_trans_id' => $request->click_trans_id,
                'merchant_trans_id' => (string)$transaction->id,
                'merchant_confirm_id' => (int)$transaction->id,
                'error' => 0,
                'error_note' => 'Success',
            ]);
        } catch (\Throwable $e) {
            Log::error('💥 CLICK COMPLETE ERROR', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'click_trans_id' => $request->click_trans_id ?? null,
                'merchant_trans_id' => (string)$request->merchant_trans_id ?? null,
                'merchant_confirm_id' => (int)$request->merchant_prepare_id ?? 0,
                'error' => -9,
                'error_note' => 'Internal server error',
            ]);
        }
    }


    // public function complete(Request $request)
    // {
    //     Log::info('CLICK COMPLETE: started', $request->all());

    //     try {
    //         if (!$this->checkSignature($request)) {
    //             Log::warning('Invalid signature in complete', $request->all());
    //             return response()->json(['error' => -1, 'error_note' => 'Invalid signature']);
    //         }

    //         $transaction = Transaction::find($request->merchant_prepare_id ?? $request->merchant_trans_id);
    //         if (!$transaction) {
    //             Log::error('Transaction not found', [
    //                 'merchant_prepare_id' => $request->merchant_prepare_id,
    //                 'merchant_trans_id' => $request->merchant_trans_id
    //             ]);
    //             return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
    //         }

    //         $plan = Plan::find($transaction->plan_id);
    //         if (!$plan) {
    //             Log::error('Plan not found', ['plan_id' => $transaction->plan_id]);
    //             return response()->json(['error' => -5, 'error_note' => 'Plan not found']);
    //         }

    //         $transaction->update([
    //             'payment_status' => 'completed',
    //             'state' => 2,
    //             'sign_time' => now(),
    //         ]);
    //         Log::info('Transaction completed', ['transaction_id' => $transaction->id]);

    //         $subscription = Subscription::find($transaction->subscription_id);

    //         if ($subscription) {
    //             $subscription->update([
    //                 'starts_at' => now(),
    //                 'ends_at' => now()->addDays(30),
    //                 'remaining_auto_responses' => $plan->auto_response_limit,
    //                 'status' => 'active',
    //             ]);
    //             Log::info('Subscription updated to active', ['subscription_id' => $subscription->id]);
    //         } else {
    //             $subscription = Subscription::create([
    //                 'user_id' => $transaction->user_id,
    //                 'plan_id' => $plan->id,
    //                 'starts_at' => now(),
    //                 'ends_at' => now()->addDays(30),
    //                 'remaining_auto_responses' => $plan->auto_response_limit ?? 0,
    //                 'status' => 'active',
    //             ]);
    //             $transaction->update(['subscription_id' => $subscription->id]);
    //             Log::warning('New subscription created because none found', ['subscription_id' => $subscription->id]);
    //         }

    //         Log::info('CLICK COMPLETE: finished successfully', [
    //             'transaction_id' => $transaction->id,
    //             'subscription_id' => $subscription->id
    //         ]);

    //         return response()->json([
    //             'click_trans_id' => $request->click_trans_id,
    //             'merchant_trans_id' => (string)$transaction->id,
    //             'merchant_confirm_id' => (int)$transaction->id,
    //             'error' => 0,
    //             'error_note' => 'Success',
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('💥 CLICK COMPLETE ERROR', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'click_trans_id' => $request->click_trans_id,
    //             'merchant_trans_id' => (string)$request->merchant_trans_id,
    //             'merchant_confirm_id' => (int)$request->merchant_prepare_id,
    //             'error' => -9,
    //             'error_note' => 'Internal server error',
    //         ]);
    //     }
    // }


    private function checkSignature(Request $request)
    {
        $secretKey = env('CLICK_SECRET_KEY');

        $string = (string)$request->click_trans_id .
            (string)$request->service_id .
            (string)$secretKey .
            (string)$request->merchant_trans_id .
            (($request->action == 1) ? (string)$request->merchant_prepare_id : '') .
            (string)$request->amount .
            (string)$request->action .
            (string)$request->sign_time;

        $expectedSign = md5($string);
        $isValid = $expectedSign === $request->sign_string;

        if (!$isValid) {
            Log::error('Signature mismatch', [
                'expected' => $expectedSign,
                'received' => $request->sign_string,
                'data' => $request->all(),
                'string' => $string,
            ]);
        } else {
            // Log::info('✅ Signature verified successfully', [
            //     'sign_string' => $expectedSign
            // ]);
        }

        return $isValid;
    }

    public function booking(Request $request)
    {
        $user = Auth::user();
        //Log::info('CLICK BOOKING STARTED', ['user_id' => $user->id, 'plan_id' => $request->plan_id]);

        $plan = Plan::find($request->plan_id);
        if (!$plan) {
            Log::error('Plan not found', ['plan_id' => $request->plan_id]);
            return response()->json(['error' => 'Plan not found'], 404);
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'remaining_auto_responses' => $plan->remaining_auto_responses ?? 0,
            'status' => 'pending'
        ]);
        //Log::info('Subscription created', ['subscription_id' => $subscription->id]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'payment_method' => 'click',
            'payment_status' => 'pending',
            'state' => 0,
            'amount' => $plan->price,
            'sign_time' => null,
        ]);
        // Log::info('Transaction created', [
        //     'transaction_id' => $transaction->id,
        //     'subscription_id' => $subscription->id
        // ]);

        $merchantId = env('CLICK_MERCHANT_ID');
        $serviceId = env('CLICK_SERVICE_ID');
        $amount = $plan->price;
        $transactionId = $transaction->id;

        $clickUrl = "https://my.click.uz/services/pay?service_id={$serviceId}&merchant_id={$merchantId}&amount={$amount}&transaction_param={$transactionId}";
      //  Log::info('CLICK payment URL generated', ['url' => $clickUrl]);

        return response()->json([
            'success' => true,
            'payment_url' => $clickUrl,
            'transaction_id' => $transactionId,
            'subscription_id' => $subscription->id,
        ]);
    }
}
