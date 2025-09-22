<?php

namespace Modules\Vacancies\Interfaces;

use App\Models\Vacancy;

interface HHVacancyInterface
{
    public function search(string $query, int $page = 0, int $perPage = 100): array;

    public function getById(string $id): array;

    public function applyToVacancy(string $vacancyId, string $resumeId, ?string $coverLetter = null): array;

}