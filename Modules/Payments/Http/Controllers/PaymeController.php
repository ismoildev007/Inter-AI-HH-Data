<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
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
    public $KEY = 'Paycom:EWGVue0E%gi1Y6v42pAF7FY2wfWoaTx8rHMs';

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
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Avval tizimga kiring!');
        }

        $user = Auth::user();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'remaining_auto_responses' => $request->remaining_auto_responses ?? 0,
            'status' => 'pending'
        ]);

        $amount = $subscription->plan->price;

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $subscription->plan_id,
            'subscription_id' => $subscription->id,
            'transaction_id' => null,
            'amount' => $amount,
            'state' => 0,
            'payment_status' => 'pending',
            'create_time' => null
        ]);

        $merchantId = env('PAYME_MERCHANT_ID');
        $tiyinAmount = intval($amount * 100);

        $payload = "m={$merchantId};ac.transaction_id={$transaction->id};a={$tiyinAmount}";
        $encoded = base64_encode($payload);
        $redirectUrl = "https://checkout.paycom.uz/{$encoded}";

        return redirect($redirectUrl);
    }

}
