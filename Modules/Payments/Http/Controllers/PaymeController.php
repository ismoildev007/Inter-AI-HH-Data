<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payments\Http\Resources\TransactionResource;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymeController extends Controller
{
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
            "CheckPerformTransaction" => $this->checkPerformTransaction($request),
            "CreateTransaction" => $this->createTransaction($request),
            "CheckTransaction" => $this->checkTransaction($request),
            "PerformTransaction" => $this->performTransaction($request),
            "CancelTransaction" => $this->cancelTransaction($request),
            "GetStatement" => $this->getStatement($request),
            "ChangePassword" => $this->changePassword($request),
            default => $this->error($request->id, -32601, "Method not found."),
        };
    }

    protected function checkPerformTransaction(Request $request)
    {

        $params = $request->params;
        if (!isset($params['account']['transaction_id'])) {
            return self::notParam();
        }
        $pay_id = $params['id'] ?? null;
        $prepare_id = $params['account']['transaction_id'] ?? null;

        if ($prepare_id) {
            $prepare = PaymeTransaction::where('id', $prepare_id)->first();
            if (!$prepare) {
                return self::OrderNotFound();
            }
        } elseif ($pay_id) {
            $prepare = PaymeTransaction::where('transaction_id', $pay_id)->first();
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

        $prepare->create_time = 0;
        $prepare->transaction_id = null;
        $prepare->perform_time = 0;
        $prepare->cancel_time = 0;
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
            $prepare = PaymeTransaction::where('id', $prepare_id)->first();
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

        $order = PaymeTransaction::where('transaction_id', $transaction_id)->first();
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
        $prepare = PaymeTransaction::where(['transaction_id' => $transactionId])->first();

        if (!$prepare) {
            return self::OrderNotFound();
        }
        if ($prepare->state == -2) {
            return $this->canceled($prepare);
        }

        $order = Booking::where('id', $prepare->booking_id)->first();
        if (!$order) {
            Log::error('Booking not found for booking_id: ' . $prepare->booking_id);
            return response()->json(['error' => 'Booking not found'], 500);
        }

        if (!$prepare->perform_time) {
            $time = floor(microtime(true) * 1000);
            $prepare->perform_time = $time;
            $prepare->state = 2;
            $prepare->payment_status = '1';
            $prepare->save();
        }

        if ($order->service_id) {
            $service = Service::find($order->service_id);
            if (!$service || !$service->is_active) {
                Log::error('Service not found or inactive for service_id: ' . $order->service_id);
                return response()->json(['error' => 'Service not found or inactive'], 500);
            }
        }

        if ($prepare->state == 2) {
            $order = Booking::find($prepare->booking_id);
            if (!$order) {
                Log::error('Booking not found for booking_id: ' . $prepare->booking_id);
                return response()->json(['error' => 'Buyurtma topilmadi'], 500);
            }

            $order->update([
                'status' => 'payed',
                'payment_type' => 'Payme'
            ]);

            Transaction::create([
                'booking_id' => $prepare->booking_id,
                'transaction_id' => $prepare->transaction_id,
                'state' => $prepare->state,
                'payment_status' => $prepare->payment_status,
                'amount' => $prepare->amount,
                'create_time' => $prepare->create_time,
                'perform_time' => $prepare->perform_time,
                'cancel_time' => $prepare->cancel_time,
                'reason' => $prepare->reason,
            ]);

            // Subscription ni faollashtirish (pending dan active ga o'tkazish)
            $subscriptionId = session('subscription_id');
            $subscription = $subscriptionId ? Subscription::find($subscriptionId) : Subscription::where('provider_id', $order->user_id)
                ->where('sub_id', $order->sub_id)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($subscription) {
                $durationDays = ($subscription->sub->id == 3) ? 30 : $subscription->sub->duration_days; // Faqat 30 kun
                $subscription->update([
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addDays($durationDays),
                ]);
                Log::info('Subscription faollashtirildi', [
                    'user_id' => $order->user_id,
                    'subscription_id' => $subscription->id,
                    'sub_id' => $subscription->sub_id,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                ]);
            } else {
                Log::warning('No pending subscription found, creating new active subscription', [
                    'user_id' => $order->user_id,
                    'sub_id' => $order->sub_id,
                ]);
                $sub = Sub::find($order->sub_id);
                if ($sub) {
                    $durationDays = ($sub->id == 3) ? 30 : $sub->duration_days; // Faqat 30 kun
                    $newSubscription = Subscription::create([
                        'provider_id' => $order->user_id,
                        'sub_id' => $order->sub_id,
                        'start_date' => now(),
                        'end_date' => now()->addDays($durationDays),
                        'used_services_count' => 0,
                        'description_uz' => $sub->description_uz,
                        'description_ru' => $sub->description_ru,
                        'description_en' => $sub->description_en,
                        'status' => 'active',
                    ]);
                    Log::info('Yangi subscription yaratildi', [
                        'user_id' => $order->user_id,
                        'subscription_id' => $newSubscription->id,
                        'sub_id' => $sub->id,
                        'start_date' => $newSubscription->start_date,
                        'end_date' => $newSubscription->end_date,
                    ]);
                } else {
                    Log::error('Sub not found for sub_id: ' . $order->sub_id);
                }
            }

            // Eski obunani faqat kerakli holatda bekor qilish
            $currentSubscription = Subscription::where('provider_id', $order->user_id)
                ->where('status', 'active')
                ->where('id', '!=', $subscription->id ?? $newSubscription->id ?? null)
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->first();
            if ($currentSubscription) {
                $currentSubscription->update([
                    'status' => 'canceled',
                    'end_date' => now(),
                ]);
                Log::info('Eski obuna bekor qilindi', [
                    'user_id' => $order->user_id,
                    'subscription_id' => $currentSubscription->id,
                    'sub_id' => $currentSubscription->sub_id,
                ]);
            }

            // Addon bilan bog'liq logika (faqat bir marta ishlashi uchun)
            if ($order->addon_id && !$order->processed) {
                $order->update(['processed' => true]); // Bu bookingni ishlangan deb belgilaydi
                $addon = Addon::find($order->addon_id);
                if (!$addon) {
                    Log::error('Addon not found for addon_id: ' . $order->addon_id);
                    return response()->json(['error' => 'Addon not found'], 500);
                }

                Log::info('Addon tekshiruvi', [
                    'order_id' => $order->id,
                    'addon_id' => $order->addon_id,
                    'addon_name' => $addon->name,
                ]);

                // Faqat Tezkor yoki TOP addon uchun service_id ni tekshirish
                $serviceId = null;
                if ($addon->id == 2) { // TOP addon uchun service_id majburiy
                    $serviceId = $order->service_id;
                    if (!$serviceId) {
                        Log::error('Service ID is required for TOP addon', ['order_id' => $order->id, 'addon_id' => $addon->id]);
                        return response()->json(['error' => 'Service ID is required for TOP addon'], 400);
                    }
                } elseif ($addon->id == 1) { // Tezkor uchun service_id null
                    $serviceId = $order->service_id; // Tezkor uchun null qoldirish uchun o'zgartirish kerak bo'lmasa
                }

                $addonUser = AddonUser::where('addon_id', $addon->id)
                    ->where('user_id', $order->user_id)
                    ->where(function ($query) use ($serviceId) {
                        $query->where('service_id', $serviceId)
                            ->orWhereNull('service_id');
                    })
                    ->where('end_date', '>=', now())
                    ->first();

                if (!$addonUser || ($addon->id == 2 && $order->service_id && $addonUser->end_date->diffInDays(now()) < $addon->duration_days)) {
                    $initialEndDate = now()->addDays($addon->duration_days);
                    $addonUser = AddonUser::create([
                        'addon_id' => $addon->id,
                        'user_id' => $order->user_id,
                        'service_id' => $serviceId,
                        'start_date' => now(),
                        'end_date' => $initialEndDate,
                        'meta' => json_encode(['booking_id' => $order->id]),
                        'status' => 'active',
                    ]);

                    Log::info('Addon faollashtirildi', [
                        'user_id' => $order->user_id,
                        'addon_id' => $addon->id,
                        'addon_user_id' => $addonUser->id,
                        'service_id' => $serviceId,
                        'start_date' => $addonUser->start_date,
                        'end_date' => $addonUser->end_date,
                        'status' => $addonUser->status,
                    ]);

                    // Yangi xizmatni TOP qilish (faqat Tezkor yoki TOP uchun)
                    if ($serviceId) {
                        $service = Service::find($serviceId);
                        if ($service) {
                            $service->update([
                                'is_top' => true,
                                'topped_at' => $addonUser->end_date,
                            ]);
                            Log::info('Service TOP qilindi', [
                                'user_id' => $order->user_id,
                                'service_id' => $service->id,
                                'addon_id' => $addon->id,
                                'topped_at' => $service->topped_at,
                            ]);
                        } else {
                            Log::warning('Service not found for service_id: ' . $serviceId, [
                                'user_id' => $order->user_id,
                                'addon_id' => $addon->id,
                            ]);
                        }
                    }
                } else {
                    $newEndDate = \Carbon\Carbon::parse($addonUser->end_date)->addDays($addon->duration_days);
                    if ($addon->id == 2 && $order->service_id) {
                        if ($addonUser->end_date->diffInDays(now()) >= $addon->duration_days) {
                            $newEndDate = $addonUser->end_date; // Qo'shimcha qo'shilmaydi
                            Log::info('TOP uchun qo‘shimcha muddat cheklangan', [
                                'addon_user_id' => $addonUser->id,
                                'current_end_date' => $addonUser->end_date,
                            ]);
                        } else {
                            $newEndDate = now()->addDays($addon->duration_days); // Faqat 10 kun
                            Log::info('TOP uchun ' . $addon->duration_days . ' kun qo‘shildi', [
                                'addon_user_id' => $addonUser->id,
                                'new_end_date' => $newEndDate,
                            ]);
                        }
                    }
                    $addonUser->update([
                        'start_date' => now(),
                        'end_date' => $newEndDate,
                        'meta' => json_encode(['booking_id' => $order->id, 'updated_at' => now()]),
                        'status' => 'active',
                        'service_id' => $serviceId,
                    ]);

                    Log::info('AddonUser end_date uzaytirildi', [
                        'user_id' => $order->user_id,
                        'addon_id' => $addon->id,
                        'addon_user_id' => $addonUser->id,
                        'service_id' => $serviceId,
                        'new_end_date' => $newEndDate,
                        'status' => $addonUser->status,
                    ]);

                    // Xizmatni TOP holatini yangilash (faqat Tezkor yoki TOP uchun)
                    if ($serviceId) {
                        $service = Service::find($serviceId);
                        if ($service) {
                            $service->update([
                                'is_top' => true,
                                'topped_at' => $newEndDate,
                            ]);
                            Log::info('Service TOP holati yangilandi', [
                                'user_id' => $order->user_id,
                                'service_id' => $service->id,
                                'addon_id' => $addon->id,
                                'topped_at' => $service->topped_at,
                            ]);
                        }
                    }
                }
            }

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
        $order = PaymeTransaction::where('transaction_id', $transaction_id)->first();
        if ($order) {
            if (!$order->cancel_time) {
                if ($order->payment_status == 0) {
                    $order->state = -1;
                } else {
                    $order->state = -2;
                }
                $order->cancel_time = $time;
                $order->reason = $param['params']['reason'];
                $order->save();
            }
            return [
                'result' => [
                    'transaction' => (string)$order->id,
                    'state' => $order->state,
                    'cancel_time' => $order->cancel_time,
                ]
            ];
        }

        return self::OrderNotFound();
    }

    protected function getStatement(Request $request)
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

        $transactions = PaymeTransaction::getTransactionsByTimeRange($from, $to);

        return $this->success([
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    protected function changePassword(Request $request)
    {
        return $this->error($request->id, -32504, [
            "uz" => "Metodni bajarish uchun yetarli huquqlar yo'q",
            "ru" => "Недостаточно привилегий для выполнения метода",
            "en" => "Not enough privileges to perform this method"
        ]);
    }

    protected function success(array $result)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => request()->id ?? null,
            'result' => $result,
        ]);
    }

    protected function error($id, $code, $message)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => is_array($message) ? $message : [
                    'uz' => $message,
                    'ru' => $message,
                    'en' => $message,
                ],
            ]
        ], 200);
    }

    protected function OrderNotFound()
    {
        return response()->json([
            'error' => [
                'code' => -31050,
                'message' => [
                    'uz' => 'Buyurtma topilmadi',
                    'ru' => 'Заказ не найден',
                    'en' => 'Order not found'
                ]
            ]
        ]);
    }

    protected function notParam()
    {
        return response()->json([
            'error' => [
                'code' => -31050,
                'message' => [
                    'ru' => 'Ошибки неверного ввода данных покупателем',
                    'uz' => 'Xaridor tomonidan noto`g`ri ma`lumotlarni kiritish xatolari',
                    'en' => 'Errors of incorrect data entry by the buyer',
                ]
            ]
        ]);
    }

    protected function notCorrectAmount()
    {
        return response()->json([
            'error' => [
                'code' => -31001,
                'message' => [
                    'ru' => 'Неверная сумма',
                    'uz' => 'Yaroqsiz miqdor',
                    'en' => 'Incorrect amount',
                ]
            ]
        ]);
    }

    protected function canceled($order)
    {
        return response()->json([
            'result' => [
                'transaction' => (string)$order->id,
                'state' => $order->state,
                'cancel_time' => $order->cancel_time,
                'create_time' => $order->create_time,
                'perform_time' => $order->perform_time,
                'reason' => $order->reason,
            ]
        ]);
    }

    protected function pending()
    {
        return response()->json([
            'error' => [
                'code' => -31050,
                'message' => [
                    'ru' => 'В ожидании оплаты',
                    'uz' => 'To`lov kutilmoqda',
                    'en' => 'Waiting for payment',
                ]
            ]
        ]);
    }
}
