<?php

namespace Modules\ResumeCreate\Repositories;

use App\Models\Resume;
use Modules\ResumeCreate\Interfaces\ResumeCreateInterface;

class ResumeCreateRepository implements ResumeCreateInterface
{
    public function create(array $data): Resume
    {
        // Implementation will be added once the Figma-based requirements are finalised.
        // For now, this is a placeholder to keep the module structure consistent.
        throw new \LogicException('ResumeCreateRepository::create() is not implemented yet.');
    }
}

