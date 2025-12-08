<?php

namespace Modules\ResumeCreate\Services;

use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Modules\ResumeCreate\Jobs\GenerateResumeTranslationsJob;
use Modules\ResumeCreate\Interfaces\ResumeCreateInterface;

class ResumeCreateService
{
    public function __construct(
        protected ResumeCreateInterface $repository,
    ) {
    }

    public function saveForCurrentUser(array $data): Resume
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new \RuntimeException('Unauthenticated user cannot create a resume.');
        }

        $resume = $this->repository->saveForUser($userId, $data);

        GenerateResumeTranslationsJob::dispatch($resume->id);

        return $resume;
    }

    public function getForCurrentUser(): ?Resume
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return $this->repository->getForUser($userId);
    }
}
