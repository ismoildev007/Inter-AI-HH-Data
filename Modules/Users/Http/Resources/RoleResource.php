<?php

namespace Modules\Users\Http\Resources;

class RoleResource
{
    public function toArray($request): array
    {
        return [
            'name'   => (bool) $this->name,
        ];
    }
}
