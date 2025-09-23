<?php

namespace Modules\Vacancies\Interfaces;

use App\Models\Vacancy;

interface HHVacancyInterface
{
    public function search(string $query, int $page = 0, int $perPage = 100): array;

    public function getById(string $id): array;

    public function applyToVacancy(string $vacancyId, string $resumeId, ?string $coverLetter = null): array;

    /**
     * List HH negotiations for the current auth user or a specific account.
     * Returns raw HH API JSON on success.
     */
    public function listNegotiations(int $page = 0, int $perPage = 100, ?\App\Models\HhAccount $account = null): array;
}
