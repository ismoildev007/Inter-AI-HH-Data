<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NoteTranscriptionService
{
    public function __construct(
        public GeminiTranscriptionService $gemini,
        public WhisperTranscriptionService $whisper,
    ) {}

    public function transcribe(string $audioPath, string $language): ?string
    {
        $fullPath = storage_path('app/public/'.$audioPath);

        if (! is_file($fullPath)) {
            Log::warning('NoteTranscriptionService: audio file not found', ['path' => $fullPath]);

            return null;
        }

        $language = strtolower($language);

        if ($language === 'uz') {
            // For Uzbek audio we currently rely only on Gemini.
            // Whisper does not support explicit 'uz' language code, so fallback is disabled for now.
            return $this->gemini->transcribe($fullPath);
        }

        return $this->whisper->transcribe($fullPath, $language);
    }
}
