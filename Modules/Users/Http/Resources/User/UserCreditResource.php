<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCreditResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id' => (int) $this->user_id,
            'balance' => (int) $this->balance,
        ];
    }
}
