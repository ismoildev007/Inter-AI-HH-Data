<?php

namespace Modules\ResumeCreate\Interfaces;

use App\Models\Resume;

interface ResumeCreateInterface
{
    /**
     * Create a new resume record based on builder data.
     *
     * The concrete shape of $data will be finalised from the Figma design.
     */
    public function create(array $data): Resume;
}

