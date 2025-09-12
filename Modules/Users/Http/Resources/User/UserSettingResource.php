<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id'              => (int) $this->user_id,
            'auto_apply_enabled'   => (bool) $this->auto_apply_enabled,
            'auto_apply_limit'     => $this->auto_apply_limit,
            'notifications_enabled'=> (bool) $this->notifications_enabled,
            'language'             => $this->language,
        ];
    }
}
