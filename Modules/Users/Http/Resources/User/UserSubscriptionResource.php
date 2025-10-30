<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'remaining_auto_responses' => (int) $this->remaining_auto_responses,
            'status' => $this->status,
        ];
    }
}
