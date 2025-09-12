<?php

namespace Modules\Users\Http\Resources;

class IndustryResource
{
    public function toArray($request): array
    {
        return [
            'name'   => (bool) $this->name,
        ];
    }
}
