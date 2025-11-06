<?php

namespace Modules\Interviews\Services;

use App\Models\Application;
use App\Models\Interview;
use Illuminate\Support\Facades\DB;

class InterviewService
{
    public function createForApplication(Application $application): ?Interview
    {
        // Source-based guard
        $sourceFilter = (string) config('interviews.source_filter', 'hh');
        if (!$application->relationLoaded('vacancy')) {
            $application->load('vacancy');
        }
        if (!$application->vacancy || $application->vacancy->source !== $sourceFilter) {
            return null;
        }

        // Idempotency (soft): do not create if a recent pending/ready interview exists
        $existing = Interview::query()
            ->where('application_id', $application->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'ready'])
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($application) {
            $interview = Interview::create([
                'application_id' => $application->id,
                'status' => 'pending',
            ]);

            // Dispatch generation job
            \Modules\Interviews\Jobs\GenerateInterviewQuestionsJob::dispatch($interview->id);

            return $interview;
        });
    }

    /**
     * Ensure a discard-type interview exists for the given application.
     * Does not dispatch AI question generation.
     */
    public function ensureDiscardForApplication(Application $application): ?Interview
    {
        // Guard by source
        $sourceFilter = (string) config('interviews.source_filter', 'hh');
        if (!$application->relationLoaded('vacancy')) {
            $application->load('vacancy');
        }
        if (!$application->vacancy || $application->vacancy->source !== $sourceFilter) {
            return null;
        }

        // Idempotency: do not create duplicate discard interviews
        $existing = Interview::query()
            ->where('application_id', $application->id)
            ->whereNull('deleted_at')
            ->where('status', 'discard')
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($application) {
            return Interview::create([
                'application_id' => $application->id,
                'status' => 'discard',
            ]);
        });
    }
}
