<?php

namespace Modules\Payments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->transaction_id ?? (int) $this->id,
            'time' => isset($this->create_time) ? (int) $this->create_time : 0,
            'amount' => (float) $this->amount,
            'account' => [
                'transaction_id' => (int) ($this->id),
            ],
            'create_time' => isset($this->create_time) ? (int) $this->create_time : 0,
            'perform_time' => is_numeric($this->perform_time)
                ? (int) $this->perform_time
                : (strtotime($this->perform_time) * 1000),
            'cancel_time' => is_numeric($this->cancel_time)
                ? (int) $this->cancel_time
                : (strtotime($this->cancel_time) * 1000),
            'transaction' => (int) ($this->id),
            'state' => isset($this->state) ? (int) $this->state : 0,
            'reason' => !empty($this->reason) ? (int) $this->reason : null,
        ];
    }
}
