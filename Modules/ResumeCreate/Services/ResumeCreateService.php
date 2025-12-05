<?php

namespace Modules\ResumeCreate\Services;

use App\Models\Resume;
use Modules\ResumeCreate\Interfaces\ResumeCreateInterface;

class ResumeCreateService
{
    public function __construct(
        protected ResumeCreateInterface $repository,
    ) {
    }

    /**
     * High-level entry point for creating a resume via the builder.
     *
     * The DTO/array structure will follow the Figma design.
     */
    public function create(array $data): Resume
    {
        return $this->repository->create($data);
    }
}

