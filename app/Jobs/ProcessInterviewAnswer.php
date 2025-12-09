<?php

namespace App\Jobs;

use App\Models\MockInterview;
use App\Models\MockInterviewAnswer;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Interviews\Services\AiEvaluationService;
use App\Services\GeminiTranscriptionService;
use App\Services\WhisperTranscriptionService;
use App\Jobs\ProcessMockInterviewSummary;

class ProcessInterviewAnswer implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $interviewAnswerId,
        public string $lang
    ) {}

    public function handle(): void
    {
        $answer = MockInterviewAnswer::with('question', 'interview')
            ->findOrFail($this->interviewAnswerId);

        $interview = $answer->interview;
        $audioPath = $answer->answer_audio;
        $transcription = null;


        // ➤ No audio
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
            $answer->update([
                'answer_text'    => 'No answer',
                'recommendation' => "Audio file missing.",
                'status'         => 'completed',
            ]);

            $this->checkIfAllCompleted($interview->id);
            return;
        }


        // ➤ Transcribe
        if ($this->lang === 'uz') {
            $transcription = app(GeminiTranscriptionService::class)
                ->transcribe($fullPath);
        } else {
            $transcription = app(WhisperTranscriptionService::class)
                ->transcribe($fullPath, $this->lang);
        }


        // ➤ Remove audio
        try {
            Storage::disk('public')->delete($audioPath);
        } catch (Exception $e) {
            Log::error("Audio delete failed: ".$e->getMessage());
        }


        // ➤ If transcription failed
        if (!$transcription) {
            $answer->update([
                'answer_text'    => null,
                'recommendation' => "Transcription failed.",
                'status'         => 'completed',
                'answer_audio'   => null,
            ]);

            $this->checkIfAllCompleted($interview->id);
            return;
        }


        // ➤ Evaluate answer
        $evaluationService = app(AiEvaluationService::class);
        $recommendation = $evaluationService->evaluate(
            question: $answer->question->question_text,
            userAnswer: $transcription,
            lang: $this->lang
        );


        // ➤ Save final result
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
        $interview = MockInterview::with('user', 'questions.answers')
            ->find($interviewId);

        $total = $interview->questions->count();
        $completed = $interview->answers()->where('status', 'completed')->count();

        if ($completed >= $total) {
            dispatch(new ProcessMockInterviewSummary(
                interviewId: $interview->id,
                lang: $interview->user->language
            ));
        }
    }
}
