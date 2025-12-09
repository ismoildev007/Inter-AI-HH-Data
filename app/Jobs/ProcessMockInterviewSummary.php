<?php

namespace App\Jobs;

use App\Models\MockInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MockInterviews\Services\AiSummaryService;

class ProcessMockInterviewSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $interviewId,
        public string $lang
    ) {
        $this->onQueue('interview-summary');
    }

    public function handle()
    {
        $interview = MockInterview::with('interviewQuestions.interviewAnswers')
            ->findOrFail($this->interviewId);

        $summaryService = app(AiSummaryService::class);

        $summary = $summaryService->generate($interview, $this->lang);

        $interview->update([
            'overall_percentage' => $summary['overall_percentage'] ?? 0,
            'strengths'          => json_encode($summary['strengths'] ?? []),
            'weaknesses'         => json_encode($summary['weaknesses'] ?? []),
            'work_on'            => json_encode($summary['work_on'] ?? []),
        ]);
    }
}
