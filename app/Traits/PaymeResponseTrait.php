<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait PaymeResponseTrait
{
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
