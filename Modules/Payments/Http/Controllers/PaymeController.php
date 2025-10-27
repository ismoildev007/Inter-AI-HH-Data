<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Payments\Services\PaymeService;

class PaymeController extends Controller
{

    protected PaymeService $service;

    public function __construct(PaymeService $service)
    {
        $this->service = $service;
    }
    public $KEY = 'Paycom:fepzYE6e9BSjir3Qaov6CcrrvMh#8E7aNUWZ';

    public function checkAuth($request)
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/^Basic\s+(.*)$/i', $authHeader, $matches)) {
            $decoded = base64_decode($matches[1]);
            if ($decoded == $this->KEY) {
                return true;
            }

        }
        return false;
    }

    public function handleCallback(Request $request)
    {

        $check = $this->checkAuth($request);
        if (!$check) {
            return response()->json([
                'error' => [
                    'code' => -32504,
                    'message' => 'Недостаточно привилегий для выполнения метода'
                ]
            ], 200);
        }


        $method = $request->method;

        return match ($method) {
            "CheckPerformTransaction" => $this->service->checkPerformTransaction($request),
            "CreateTransaction" => $this->service->createTransaction($request),
            "CheckTransaction" => $this->service->checkTransaction($request),
            "PerformTransaction" => $this->service->performTransaction($request),
            "CancelTransaction" => $this->service->cancelTransaction($request),
            "GetStatement" => $this->service->getStatement($request),
            "ChangePassword" => $this->service->changePassword($request),
            default => $this->service->error($request->id, -32601, "Method not found."),
        };
    }

    public function booking(Request $request)
    {
        $user = Auth::user();
        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return response()->json(['error' => 'Plan topilmadi'], 404);
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => null,
            'ends_at' => null,
            'remaining_auto_responses' => $plan->auto_response_limit ?? 0,
            'status' => 'pending'
        ]);

        $amount = $plan->price;

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'transaction_id' => null,
            'amount' => $plan->price,
            'state' => 0,
            'payment_status' => 'pending',
            'create_time' => null
        ]);

        $merchantId = env('PAYME_MERCHANT_ID');
        $tiyinAmount = intval($amount * 100);
        $backUrl = 'https://t.me/inter_ai_vacancies_bot';
        $payload = "m={$merchantId};ac.transaction_id={$transaction->id};a={$tiyinAmount};c={$backUrl}";
        $encoded = base64_encode($payload);
        $redirectUrl = "https://checkout.paycom.uz/{$encoded}";

        return response()->json([
            'success' => true,
            'payment_url' => $redirectUrl,
        ]);
    }

}
