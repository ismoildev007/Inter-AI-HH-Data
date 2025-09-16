<?php

namespace Modules\Resumes\Interfaces;

use App\Models\HhAccount;

interface HhResumeInterface
{
    public function fetchMyResumes(HhAccount $account): array;
}