<?php

namespace Modules\Vacancies\Http\Resources;

use App\Models\Vacancy;
use Illuminate\Http\Resources\Json\JsonResource;

class VacancyMatchResource extends JsonResource
{
    public function toArray($request)
    {
        $vacancy = Vacancy::find($this->vacancy_id);
        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        return [
            'resume_id'     => $this->resume_id,
            'vacancy_id'    => $this->vacancy_id,
            'score_percent' => $this->score_percent,
            'vacancy' => [
                'id'          => $vacancy?->id,
                'external_id' => $vacancy?->external_id,
                'title'       => $vacancy?->title,
                'location'    => $vacancy?->area?->name
                    ?? ($raw['area']['name'] ?? null),
                'experience'  => $raw['experience']['name'] ?? null,
                'salary'      => $raw['salary'] ?? null,
            ],
        ];
    }
}
