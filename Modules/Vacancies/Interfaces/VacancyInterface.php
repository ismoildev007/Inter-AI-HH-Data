<?php 

namespace Modules\Vacancies\Interfaces;

use app\Models\Vacancy;

interface VacancyInterface
{
    public function firstOrCreateFromHH(array $data): Vacancy;
    
}