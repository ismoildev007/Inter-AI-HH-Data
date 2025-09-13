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
            'vacancy'       => [
                'title'       => $this->vacancy->title,
                'description' => $this->vacancy->description_html,
                'salary'      => [
                    'from' => $this->vacancy->salary_from,
                    'to'   => $this->vacancy->salary_to,
                    'currency' => $this->vacancy->salary_currency,
                ],
            ],
        ];
    }
}
