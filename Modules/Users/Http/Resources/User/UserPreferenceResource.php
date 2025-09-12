<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Http\Resources\IndustryResource;

class UserPreferenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => (int) $this->id,
            'user_id'             => (int) $this->user_id,
            'industry_id'         => (int) $this->industry_id,
            'experience_level'    => $this->experience_level,
            'desired_salary_from' => $this->desired_salary_from,
            'desired_salary_to'   => $this->desired_salary_to,
            'currency'            => $this->currency,
            'work_mode'           => $this->work_mode,
            'notes'               => $this->notes,
            'cover_letter'        => $this->cover_letter,

            // industry haqida minimal info
            'industry'            => new IndustryResource($this->whenLoaded('industry')),
        ];
    }
}
