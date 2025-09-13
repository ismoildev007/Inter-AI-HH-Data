<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserJobTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'job_type'=> $this->job_type,
        ];
    }
}
