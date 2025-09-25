<?php

namespace Modules\Vacancies\Http\Resources;

namespace Modules\Vacancies\Http\Resources;

use App\Models\Application;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class VacancyMatchResource extends JsonResource
{
    public function toArray($request)
    {
        $vacancy = Vacancy::find($this->vacancy_id);
        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        $applied = Application::where('user_id', Auth::id())
            ->where('vacancy_id', $this->vacancy_id)
            ->exists();

        $vacancyData = [
            'id'          => $vacancy?->id,
            'source'      => $vacancy->source ?? 'telegram',
            'external_id' => $vacancy?->external_id ?? null,
            'company'     => $raw['employer']['name'] ?? null,
            'title'       => $vacancy?->title,
            'location'    => $vacancy?->area?->name
                ?? ($raw['area']['name'] ?? null),
            'experience'  => $raw['experience']['name'] ?? null,
            'salary'      => $raw['salary'] ?? null,
            'published_at' => isset($raw['published_at'])
                ? Carbon::parse($raw['published_at'])->format('Y-m-d H:i:s')
                : null,
        ];

        if ($vacancy?->source === 'telegram') {
            $vacancyData['message_id'] = $vacancy->target_message_id;
        }

        return [
            // 'id' => $this->id,
            'resume_id'     => $this->resume_id,
            'vacancy_id'    => $this->vacancy_id,
            'score_percent' => (int) round($this->score_percent),
            'status'        => $applied,
            'vacancy'       => $vacancyData,
        ];
    }
}
