<?php

namespace Modules\Vacancies\Http\Resources;

namespace Modules\Vacancies\Http\Resources;

use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class VacancyMatchResource extends JsonResource
{
    public function toArray($request)
    {
        $vacancy = Vacancy::find($this->vacancy_id);
        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        // 🔎 Check if applied
        $applied = Application::where('user_id', Auth::id())
            ->where('vacancy_id', $this->vacancy_id)
            ->exists();

        return [
            'resume_id'     => $this->resume_id,
            'vacancy_id'    => $this->vacancy_id,
            'score_percent' => (int) round($this->score_percent),
            'status'       => $applied,
            'vacancy' => [
                'id'          => $vacancy?->id,
                'external_id' => $vacancy?->external_id,
                'company'     => $raw['employer']['name'] ?? null,
                'title'       => $vacancy?->title,
                'location'    => $vacancy?->area?->name
                    ?? ($raw['area']['name'] ?? null),
                'experience'  => $raw['experience']['name'] ?? null,
                'salary'      => $raw['salary'] ?? null,
            ],
        ];
    }
}
