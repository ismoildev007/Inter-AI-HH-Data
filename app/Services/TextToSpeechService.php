<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class TextToSpeechService
{
    private string $azureKey;
    private string $azureRegion;
    private string $elevenKey;

    public function __construct()
    {
        $this->azureKey   = env('AZURE_SPEECH_KEY');
        $this->azureRegion = env('AZURE_SPEECH_REGION', 'eastus');
        $this->elevenKey  = env('ELEVENLAB_API_KEY');
    }

    /**
     * Main entry point
     */
    public function generate(string $text, string $lang, string $filename): ?string
    {
        if ($lang === 'uz') {
            $ok = $this->textToAudioAzureUzbek($text, $filename);
        } else {
            $ok = $this->textToAudioElevenLabs($text, $filename, $lang);
        }

        return $ok ? asset("storage/{$filename}") : null;
    }

    /**
     * ElevenLabs for EN / RU
     */
    private function textToAudioElevenLabs(string $text, string $filename, string $lang): bool
    {
        try {
            if (!$this->elevenKey) {
                Log::error("ELEVENLABS key missing");
                return false;
            }

            $voiceMap = [
                'en' => env('ELEVENLABS_VOICE_EN', '21m00Tcm4TlvDq8ikWAM'),
                'ru' => env('ELEVENLABS_VOICE_RU', 'ErXwobaYiN018PzUjQy5'),
                'ar' => env('ELEVENLABS_VOICE_AR'), 
            ];

            $voiceId = $voiceMap[$lang] ?? $voiceMap['en'];

            $payload = [
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => 0.4,
                    'similarity_boost' => 0.9
                ]
            ];

            $url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}/stream?optimize_streaming_latency=4";

            $resp = Http::withHeaders([
                'xi-api-key' => $this->elevenKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg'
            ])
                ->timeout(60)
                ->post($url, $payload);

            if ($resp->failed()) {
                Log::error("ELEVENLABS ERROR", ['body' => $resp->body()]);
                return false;
            }

            Storage::disk('public')->put($filename, $resp->body());
            return true;
        } catch (\Throwable $e) {
            Log::error("ELEVEN TTS FAILED: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Azure for Uzbek TTS
     */
    private function textToAudioAzureUzbek(string $text, string $filename): bool
    {
        try {
            if (!$this->azureKey) {
                Log::error("AZURE key missing");
                return false;
            }

            // Cache token (valid for 10 min)
            $token = Cache::remember("azure_tts_token", now()->addMinutes(9), function () {
                $resp = Http::withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->azureKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])
                    ->post("https://{$this->azureRegion}.api.cognitive.microsoft.com/sts/v1.0/issuetoken");

                if ($resp->failed()) {
                    throw new \RuntimeException("Azure token error: ".$resp->body());
                }

                return $resp->body();
            });

            // SSML
            $ssml = <<<XML
                    <speak version="1.0" xml:lang="uz-UZ">
                        <voice name="uz-UZ-MadinaNeural">{$text}</voice>
                    </speak>
                    XML;

            $ttsResp = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/ssml+xml',
                'X-Microsoft-OutputFormat' => 'audio-16khz-64kbitrate-mono-mp3'
            ])
                ->timeout(30)
                ->withBody($ssml, 'application/ssml+xml')
                ->post("https://{$this->azureRegion}.tts.speech.microsoft.com/cognitiveservices/v1");

            if ($ttsResp->failed()) {
                Log::error("AZURE TTS ERROR", ['body' => $ttsResp->body()]);
                return false;
            }

            Storage::disk('public')->put($filename, $ttsResp->body());
            return true;
        } catch (\Throwable $e) {
            Log::error("AZURE TTS FAILED: ".$e->getMessage());
            return false;
        }
    }
}
