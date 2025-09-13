<?php

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => (int) $this->id,
            'name'      => $this->name ?? null,
            'email'     => $this->email ?? null,
            'phone'     => $this->phone ?? null,
            'logo'      => $this->logo_path ?? null,
            'created_at'=> $this->created_at?->toDateTimeString(),
        ];
    }
}
