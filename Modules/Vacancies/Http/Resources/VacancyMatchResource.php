<?php

namespace Modules\Vacancies\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VacancyMatchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'resume_id'     => $this->resume_id,
            'vacancy_id'    => $this->vacancy_id,
            'score_percent' => $this->score_percent,
            'explanations'  => json_decode($this->explanations, true),
            'vacancy' => [
                'id'          => optional($this->vacancy)->id,
                'title'       => optional($this->vacancy)->title,
                'description' => optional($this->vacancy)->description ?? null,
                'salary'      => [
                    'from'     => optional($this->vacancy)->salary_from,
                    'to'       => optional($this->vacancy)->salary_to,
                    'currency' => optional($this->vacancy)->salary_currency,
                ],
            ],

        ];
    }
}
