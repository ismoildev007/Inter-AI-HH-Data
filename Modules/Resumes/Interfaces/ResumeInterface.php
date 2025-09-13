<?php 

namespace Modules\Vacancies\Interfaces;

use App\Models\Resume;

interface ResumeInterface
{
    public function store(array $data): Resume;

    public function update(Resume $resume, array $data): Resume;

    public function findById(int $id): ?Resume;

    public function delete(Resume $resume): bool;
}