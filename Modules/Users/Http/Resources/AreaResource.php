<?php

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => (int) $this->id,
            'source'     => $this->source,
            'external_id'=> $this->external_id,
            'name'       => $this->name,
            'parent_id'  => (int) $this->parent_id,
            'raw_json'   => $this->raw_json,

            // parent haqida minimal info
            'parent'     => new AreaResource($this->whenLoaded('parent')),

            // bolalar (child) areas
            'children'   => AreaResource::collection($this->whenLoaded('children')),
        ];
    }
}
