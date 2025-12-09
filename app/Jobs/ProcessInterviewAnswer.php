<?php

namespace App\Jobs;

use App\Models\InterviewAnswer;
use App\Services\GeminiTranscriptionService;
use App\Services\WhisperTranscriptionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Interviews\Services\AiEvaluationService;
use App\Jobs\ProcessMockInterviewSummary;
use App\Models\MockInterview;
use App\Models\MockInterviewAnswer;

class ProcessInterviewAnswer implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $interviewAnswerId,
        public string $lang
    ) {
        $this->onQueue('interview-answer');
    }

    public function handle(): void
    {
        Log::info('test');
        $answer = MockInterviewAnswer::with('interviewQuestion', 'mockInterview')
            ->findOrFail($this->interviewAnswerId);

        $interview = $answer->mockInterview;
        $audioPath = $answer->answer_audio;
        $transcription = null;


        if (!$audioPath) {
            $answer->update([
                'answer_text'    => 'No answer',
                'recommendation' => "No answer provided.",
                'status'         => 'completed',
            ]);

            $this->checkIfAllCompleted($interview->id);
            return;
        }


        $fullPath = storage_path("app/public/{$audioPath}");

        if (!file_exists($fullPath)) {
            Log::error("Audio file not found", ['path' => $fullPath]);

            $answer->update([
                'answer_text'    => 'No answer',
                'recommendation' => "No answer provided.",
                'status'         => 'completed',
            ]);

            $this->checkIfAllCompleted($interview->id);
            return;
        }

        if ($this->lang === 'uz') {
            $transcription = app(GeminiTranscriptionService::class)
                ->transcribe($fullPath);
        } else {
            $transcription = app(WhisperTranscriptionService::class)
                ->transcribe($fullPath, $this->lang);
        }


        try {
            Storage::disk('public')->delete($audioPath);
        } catch (Exception $e) {
            Log::error("Audio delete failed", [
                'path'  => $audioPath,
                'error' => $e->getMessage()
            ]);
        }


        if (!$transcription) {
            $answer->update([
                'answer_text'    => null,
                'recommendation' => "No answer provided.",
                'status'         => 'completed',
                'answer_audio'   => null,
            ]);

            $this->checkIfAllCompleted($interview->id);
            return;
        }


        $evaluationService = app(AiEvaluationService::class);
        $recommendation = $evaluationService->evaluate(
            question: $answer->interviewQuestion->question,
            userAnswer: $transcription,
            lang: $this->lang
        );


        $answer->update([
            'answer_text'    => $transcription,
            'recommendation' => $recommendation,
            'status'         => 'completed',
            'answer_audio'   => null,
        ]);

        $this->checkIfAllCompleted($interview->id);
    }


    private function checkIfAllCompleted(int $interviewId)
    {
        $interview = MockInterview::with('user', 'interviewAnswers', 'interviewQuestions')
            ->find($interviewId);


        $total = $interview->interviewQuestions->count();
        $completed = $interview->interviewAnswers()->where('status', 'completed')->count();

        if ($completed >= $total) {
            dispatch(new ProcessMockInterviewSummary(
                interviewId: $interview->id,
                lang: $interview->user->language
            ));
        }
    }
}
