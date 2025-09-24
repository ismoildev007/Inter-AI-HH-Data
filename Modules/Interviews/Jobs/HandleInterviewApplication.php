<?php

namespace Modules\Interviews\Jobs;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Interviews\Services\InterviewService;

class HandleInterviewApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $applicationId)
    {
    }

    public function handle(InterviewService $service): void
    {
        $application = Application::with('vacancy')->find($this->applicationId);
        if (!$application) {
            return;
        }

        try {
            $service->createForApplication($application);
        } catch (\Throwable $e) {
            Log::warning('HandleInterviewApplication failed', [
                'application_id' => $this->applicationId,
                'error' => $e->getMessage(),
            ]);
            $this->release($this->backoff);
        }
    }
}

