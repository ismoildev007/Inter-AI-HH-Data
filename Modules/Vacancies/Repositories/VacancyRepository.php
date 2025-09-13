<?php

namespace Modules\Vacancies\Repositories;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\VacancyInterface;

class VacancyRepository implements VacancyInterface
{
    public function firstOrCreateFromHH(array $data): Vacancy
    {
        Log::info(['data' => $data]);

        $salary = is_array($data['salary'] ?? null) ? $data['salary'] : [];

        return Vacancy::firstOrCreate(
            [
                'source'      => 'hh',
                'external_id' => $data['id'],
            ],
            [
                'title'          => $data['name'] ?? '',
                'description'    => $data['description'] ?? '',
                'salary_from'    => $salary['from'] ?? null,
                'salary_to'      => $salary['to'] ?? null,
                'salary_currency' => $salary['currency'] ?? null,
                'salary_gross'   => $salary['gross'] ?? null,
                'published_at'   => $data['published_at'] ?? null,
                'apply_url'      => $data['alternate_url'] ?? null,
                'raw_data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]
        );
    }
}
