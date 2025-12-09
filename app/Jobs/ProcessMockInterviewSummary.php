<?php

namespace App\Jobs;

use App\Models\MockInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Interviews\Services\AiSummaryService;

class ProcessMockInterviewSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $interviewId,
        public string $lang
    ) {}

    public function handle()
    {
        $interview = MockInterview::with('questions.answers')
            ->findOrFail($this->interviewId);

        $summary = app(AiSummaryService::class)
            ->generate($interview, $this->lang);

        $interview->update([
            'overall_percentage' => $summary['overall_percentage'],
            'strengths'          => $summary['strengths'],
            'weaknesses'         => $summary['weaknesses'],
            'work_on'            => $summary['work_on'],
        ]);
    }
}
