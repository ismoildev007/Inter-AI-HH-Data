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
        static $counter = 0; 
        $order = ++$counter;
        $vacancy = Vacancy::find($this->vacancy_id);
        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        $applied = Application::where('user_id', Auth::id())
            ->where('vacancy_id', $this->vacancy_id)
            ->exists();

        // Build detail API link depending on source
        $detailApi = null;
        if ($vacancy) {
            if (($vacancy->source ?? 'telegram') === 'telegram') {
                $detailApi = url('api/v1/telegram/vacancies/' . $vacancy->id);
            } elseif (($vacancy->source ?? '') === 'hh' && !empty($vacancy->external_id)) {
                $detailApi = url('api/v1/hh/vacancies/' . $vacancy->external_id);
            }
        }

        $vacancyData = [
            'id'          => $vacancy?->id,
            'source'      => $vacancy->source ?? 'telegram',
            'external_id' => $vacancy?->external_id ?? null,
            'company'     => $raw['employer']['name'] ?? $vacancy->company ?? null,
            'title'       => $order. ". ". $vacancy?->title ,
            'experience'  => $raw['experience']['name'] ?? null,
            'salary'      => $raw['salary'] ?? null,
            'published_at' => isset($raw['published_at'])
                ? Carbon::parse($raw['published_at'])->format('Y-m-d H:i:s')
                : null,
            'detail_api'  => $detailApi,
        ];

        if ($vacancy?->source === 'telegram') {
            $vacancyData['message_id'] = $vacancy->target_message_id;
            $vacancyData['source_id'] = $vacancy->source_id;
            $vacancyData['source_message_id'] = $vacancy->source_message_id;
            $vacancyData['target_message_id'] = $vacancy->target_message_id;

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
