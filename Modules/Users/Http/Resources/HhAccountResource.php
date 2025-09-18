<?php

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HhAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'scope' => $this->scope,
            'expires_at' => optional($this->expires_at)?->diffForHumans(),
            'created_at' => optional($this->created_at)?->toDayDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDayDateTimeString(),

            'raw_json' => $this->raw_json,
            'user' => $this->whenLoaded('user'),

        ];
    }
}
