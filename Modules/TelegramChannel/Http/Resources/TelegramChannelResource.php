<?php

namespace Modules\TelegramChannel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TelegramChannelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'channel_id' => $this->channel_id,
            'title' => $this->title,
            'is_source' => (bool) $this->is_source,
            'is_target' => (bool) $this->is_target,
            'last_message_id' => $this->last_message_id,
            'raw_json' => $this->raw_json,
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

