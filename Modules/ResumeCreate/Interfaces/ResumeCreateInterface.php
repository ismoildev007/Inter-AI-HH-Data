<?php

namespace Modules\ResumeCreate\Interfaces;

use App\Models\Resume;

interface ResumeCreateInterface
{
    public function saveForUser(int $userId, array $data): Resume;

    public function getForUser(int $userId): ?Resume;
}
