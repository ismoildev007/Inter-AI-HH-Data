<?php

namespace Modules\Resumes\Interfaces;

use App\Models\DemoResume;
use App\Models\Resume;

interface ResumeInterface
{
    public function store(array $data): Resume;
    public function demoStore(array $data): DemoResume;

    public function update(Resume $resume, array $data): Resume;

    public function findById(int $id): ?Resume;

    public function delete(Resume $resume): bool;
}
