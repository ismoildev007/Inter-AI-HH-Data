<?php

namespace Modules\Vacancies\Repositories;

use App\Models\Vacancy;
use Modules\Vacancies\Interfaces\VacancyInterface;

class VacancyRepository implements VacancyInterface
{
    public function firstOrCreateFromHH(array $data): Vacancy
    {
        return Vacancy::firstOrCreate(
            [
                'source'      => 'hh',
                'external_id' => $data['id'],
            ],
            [
                'title'            => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
                'salary_from'      => $data['salary']['from'] ?? null,
                'salary_to'        => $data['salary']['to'] ?? null,
                'salary_currency'  => $data['salary']['currency'] ?? null,
                'salary_gross'     => $data['salary']['gross'] ?? null,
                'published_at'     => $data['published_at'] ?? null,
                'apply_url'        => $data['alternate_url'] ?? null,
                'raw_data'         => $data,
            ]
        );
    }
}
