<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Http\Resources\AreaResource;

class UserLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => (int) $this->id,
            'user_id'   => (int) $this->user_id,
            'text'      => $this->text,
            'area_id'   => (int) $this->area_id,
            'is_primary'=> (bool) $this->is_primary,

            // Area haqida minimal info
            'area'      => new AreaResource($this->whenLoaded('area')),
        ];
    }
}
