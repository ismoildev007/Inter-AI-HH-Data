<?php

namespace Modules\Payments\Services;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Traits\PaymeResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Payments\Http\Resources\TransactionResource;

class PaymeService
{
    use PaymeResponseTrait;
    public function checkPerformTransaction(Request $request)
    {

        $params = $request->params;
        if (!isset($params['account']['transaction_id'])) {
            return self::notParam();
        }
        $pay_id = $params['id'] ?? null;
        $prepare_id = $params['account']['transaction_id'] ?? null;

        if ($prepare_id) {
            $prepare = Transaction::where('id', $prepare_id)->first();
            if (!$prepare) {
                return self::OrderNotFound();
            }
        } elseif ($pay_id) {
            $prepare = Transaction::where('transaction_id', $pay_id)->first();
            if (!$prepare) {
                return self::pending();
            }
        }

        if (!isset($prepare)) {
            return self::OrderNotFound();
        }

        if (($params['amount']) != ($prepare->amount * 100)) {
            return $this->notCorrectAmount();
        }

        if ($prepare->payment_status == 1) {
            return self::OrderNotFound();
        }

        if ($prepare->state == -2) {
            return $this->canceled($prepare);
        }

        $prepare->create_time = null;
        $prepare->transaction_id = null;
        $prepare->perform_time = null;
        $prepare->cancel_time = null;
        $prepare->state = 0;
        $prepare->reason = null;
        $prepare->save();

        return response()->json([
            'result' => [
                'allow' => true
            ]
        ]);
    }

    public function createTransaction(Request $request)
    {
        $param = $request->all();
        $time = floor(microtime(true) * 1000);
        $transaction_id = $param['params']['id'] ?? null;
        $prepare_id = $param['params']['account']['transaction_id'] ?? null;
        if ($prepare_id) {
            $prepare = Transaction::where('id', $prepare_id)->first();
            if (($param['params']['amount']) != ($prepare->amount * 100)) return self::notCorrectAmount();
            if ($prepare->state == 1 && $prepare->transaction_id != $transaction_id) {
                return self::pending();
            }
            if ($prepare->state == 1) {
                return [
                    'result' => [
                        'create_time' => $prepare->create_time,
                        'transaction' => (string)$prepare->id,
                        'state' => 1,
                    ]
                ];
            }
            if ($prepare->payment_status == 1) {
                return self::OrderNotFound();
            }
            if ($prepare->state == -2) {
                return $this->canceled($prepare);
            }
            if (!$prepare) {
                return self::OrderNotFound();
            }
            $prepare->create_time = $time;
            $prepare->transaction_id = $param['params']['id'];
            $prepare->state = 1;
            $prepare->save();
            return response()->json([
                'result' => [
                    'create_time' => $prepare->create_time,
                    'transaction' => (string)$prepare->id,
                    'state' => 1,
                ]
            ]);
        }
        return self::notParam();
    }

    public function checkTransaction($param)
    {
        $transaction_id = $param['params']['id'] ?? null;

        $order = Transaction::where('transaction_id', $transaction_id)->first();
        if ($order) {
            if ($order->state == -2) return $this->canceled($order);
            return [
                'result' => [
                    'create_time' => $order->create_time,
                    'perform_time' => $order->perform_time,
                    'cancel_time' => $order->cancel_time,
                    'transaction' => (string)$order->id,
                    'state' => $order->state,
                    'reason' => $order->reason,
                ]
            ];
        }

        return self::OrderNotFound();
    }
    public function performTransaction($param)
    {
        $transactionId = null;
        if (isset($param['params']['account']['transaction_id'])) {
            $transactionId = $param['params']['account']['transaction_id'];
        } elseif (isset($param['params']['id'])) {
            $transactionId = $param['params']['id'];
        } elseif (isset($param['params']['transaction'])) {
            $transactionId = $param['params']['transaction'];
        }
        $prepare = Transaction::where(['transaction_id' => $transactionId])->first();

        if (!$prepare) {
            return self::OrderNotFound();
        }
        if ($prepare->state == -2) {
            return $this->canceled($prepare);
        }

        $subscription = Subscription::where('id', $prepare->subscription_id)->first();
        if (!$subscription) {
            Log::error('Booking not found for booking_id: ' . $prepare->transaction_id);
            return response()->json(['error' => 'Bookin0g not found'], 500);
        }

        if (!$prepare->perform_time) {
            $time = floor(microtime(true) * 1000);
            $prepare->perform_time = $time;
            $prepare->state = 2;
            $prepare->payment_status = '1';
            $prepare->payment_method = 'payme';
            $prepare->save();
        }

        if ($prepare->state == 2) {
            $subscription->update([
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'status' => 'active'
            ]);
            return response()->json([
                'result' => [
                    'perform_time' => $prepare->perform_time,
                    'transaction' => (string)$prepare->id,
                    'state' => $prepare->state,
                ]
            ]);
        }
    }

    public function cancelTransaction($param)
    {
        $transaction_id = $param['params']['id'] ?? null;
        $time = floor(microtime(true) * 1000);
        $subscription = Transaction::where('transaction_id', $transaction_id)->first();
        if ($subscription) {
            if (!$subscription->cancel_time) {
                if ($subscription->payment_status == 0) {
                    $subscription->state = -1;
                } else {
                    $subscription->state = -2;
                }
                $subscription->cancel_time = $time;
                $subscription->reason = $param['params']['reason'];
                $subscription->save();
            }
            return [
                'result' => [
                    'transaction' => (string)$subscription->id,
                    'state' => $subscription->state,
                    'cancel_time' => $subscription->cancel_time,
                ]
            ];
        }

        return self::OrderNotFound();
    }

    public function getStatement(Request $request)
    {
        $from = $request->params['from'] ?? null;
        $to = $request->params['to'] ?? null;

        if (!$from || !$to || !is_numeric($from) || !is_numeric($to)) {
            return $this->error($request->id, -31050, [
                'uz' => "Vaqtlar noto‘g‘ri formatda",
                'ru' => "Неверный формат времени",
                'en' => "Invalid time format"
            ]);
        }

        $transactions = Transaction::getTransactionsByTimeRange($from, $to);

        return $this->success([
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function changePassword(Request $request)
    {
        return $this->error($request->id, -32504, [
            "uz" => "Metodni bajarish uchun yetarli huquqlar yo'q",
            "ru" => "Недостаточно привилегий для выполнения метода",
            "en" => "Not enough privileges to perform this method"
        ]);
    }
}
