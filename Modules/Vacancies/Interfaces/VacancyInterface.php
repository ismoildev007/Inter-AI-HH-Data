<?php 

namespace Modules\Vacancies\Interfaces;

use App\Models\Vacancy;

interface VacancyInterface
{
    public function firstOrCreateFromHH(array $data): Vacancy;

    public function bulkUpsertFromHH(array $vacanciesData): array;
}
