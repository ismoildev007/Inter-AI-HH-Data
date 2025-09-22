<?php

namespace Modules\Vacancies\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VacancyMatchResource extends JsonResource
{
    public function toArray($request)
    {
        $vacancy = $this->vacancy;

        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        return [
            'resume_id'     => $this->resume_id,
            'vacancy_id'    => $this->vacancy_id,
            'score_percent' => $this->score_percent,
            // 'explanations'  => json_decode($this->explanations, true),
            'vacancy' => [
                'id'          => optional($this->vacancy)->id,
                'external_id' => optional($this->vacancy)->external_id,
                'title'       => optional($this->vacancy)->title,
                // add location (area)
                'location'    => $vacancy?->area?->name
                    ?? $raw['area']['name']
                    ?? null,

                // add experience (from relation if mapped, otherwise from raw_data)
                'experience'  => $raw['experience']['name']
                    ?? null,

                // optionally salary info
                'salary'      => $raw['salary']
                    ?? null,

            ],

        ];
    }
}
