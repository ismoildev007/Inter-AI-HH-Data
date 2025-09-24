<?php

namespace Modules\Interviews\Jobs;

use App\Models\Interview;
use App\Models\InterviewPreparation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Interviews\Services\AiQuestionGeneratorInterface;

class GenerateInterviewQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $interviewId)
    {
    }

    public function handle(AiQuestionGeneratorInterface $generator): void
    {
        $interview = Interview::with(['application.vacancy'])->find($this->interviewId);
        if (!$interview || !$interview->application || !$interview->application->vacancy) {
            return;
        }

        $vac = $interview->application->vacancy;
        $language = config('interviews.ai.language', 'auto');
        $count = (int) config('interviews.max_questions', 20);

        try {
            $questions = $generator->generate(
                $vac->title ?? 'Unknown role',
                $vac->company ?? null,
                $vac->description ?? null,
                $language,
                $count
            );

            $this->storeQuestions($interview, $questions);

            $interview->update(['status' => 'ready']);
        } catch (\Throwable $e) {
            Log::error('GenerateInterviewQuestionsJob failed', [
                'interview_id' => $this->interviewId,
                'error' => $e->getMessage(),
            ]);
            $interview?->update(['status' => 'failed']);
            $this->release($this->backoff);
        }
    }

    protected function storeQuestions(Interview $interview, array $questions): void
    {
        $questions = array_values(array_filter(array_map('trim', $questions)));
        $bulk = [];
        foreach ($questions as $q) {
            $bulk[] = [
                'interview_id' => $interview->id,
                'question' => $q,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($bulk)) {
            InterviewPreparation::insert($bulk);
        }
    }
}

