<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Http\Resources\EmployerResource;

class UserProfileViewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => (int) $this->id,
            'user_id'     => (int) $this->user_id,
            'employer_id' => (int) $this->employer_id,
            'source'      => $this->source,
            'viewed_at'   => $this->viewed_at?->toDateTimeString(),

            // employer bilan bog‘liq minimal info
            'employer'    => new EmployerResource($this->whenLoaded('employer')),
        ];
    }
}
