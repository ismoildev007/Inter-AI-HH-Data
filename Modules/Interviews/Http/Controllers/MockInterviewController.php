<?php

namespace Modules\Interviews\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMockInterviewsJob;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MockInterviewController extends Controller
{
    public function getMockInterview()
    {
        $user = Auth::user();
        $mockInterview = $user->mockInterviews()->orderBy('created_at', 'desc')->get();

        if (!$mockInterview) {
            return response()->json([
                "message" => "No mock interview found."
            ], 404);
        }

        return response()->json([
            "mock_interview" => $mockInterview
        ]);
    }

    public function checkResumeEligibility(Request $request)
    {
        $user = Auth::user();

        $resume = $user->resumes()
            ->where('is_primary', true)
            ->whereNotNull('country')
            ->first();

        if (!$resume) {
            return response()->json([
                "eligible" => false,
                "message" => "You need to upload a complete resume first."
            ]);
        }

        return response()->json([
            "resume" => $resume,
            "eligible" => true,
            "message" => "You have a resume uploaded.",
        ]);
    }


    public function generateQuestions(Request $request)
    {
        $user = Auth::user();

        $resume = $user->resumes()->where('is_primary', true)->first()
            ?? $user->resumes()->latest()->first();

        if (!$resume) {
            return response()->json([
                "status" => "error",
                "message" => "You need to upload a resume first."
            ], 422);
        }

        $experiences = $resume->experiences->map(function ($exp) {
            return [
                "position" => $exp->position,
                "company" => $exp->company,
                "location" => $exp->location,
                "start_date" => $exp->start_date?->format("Y-m"),
                "end_date" => $exp->is_current ? "Present" : $exp->end_date?->format("Y-m"),
                "description" => $exp->description,
            ];
        });

        $skills = $resume->skills->map(fn($s) => [
            "name" => $s->name,
            "level" => $s->level,
        ]);

        $education = $resume->educations->map(function ($ed) {
            return [
                "degree" => $ed->degree,
                "institution" => $ed->institution,
                "location" => $ed->location,
                "start_date" => $ed->start_date?->format("Y"),
                "end_date" => $ed->is_current ? "Present" : $ed->end_date?->format("Y"),
                "extra_info" => $ed->extra_info,
            ];
        });

        $analysisRecord = $resume->analysis;

        $analysis = [
            "position" => $resume->desired_position ?? "Developer",
            "level" => $request->level ?? "Junior",
            "strengths" => $analysisRecord->strengths ?? [],
            "weaknesses" => $analysisRecord->weaknesses ?? [],
            "growth_areas" => $analysisRecord->keywords ?? [],
        ];

        $parsedText = $this->buildResumePromptText($resume, $experiences, $skills, $education, $analysisRecord);

        GenerateMockInterviewsJob::dispatch($user, $analysis, $parsedText);

        return response()->json([
            "status" => "queued",
            "message" => "Mock interview questions are being generated...",
        ]);
    }

    public function startInterview($id)
    {
        $user = Auth::user();

        $interview = $user->mockInterviews()->where('id', $id)->firstOrFail();

        if ($interview->status !== 'started') {
            $interview->update([
                'status' => 'started',
                'started_at' => now(),
            ]);
        }

        $firstQuestion = $interview->questions()->orderBy('order')->first();

        return response()->json([
            "status" => "started",
            "interview_id" => $interview->id,
            "question" => [
                "id" => $firstQuestion->id,
                "order" => $firstQuestion->order,
                "question" => $firstQuestion->question_text,
                "audio" => $firstQuestion->question_audio_url,
            ]
        ]);
    }

    public function saveAnswer(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'question_id' => 'required|exists:mock_interview_questions,id',
            'answer_text' => 'nullable|string',
            'answer_audio' => 'nullable|file|mimes:mp3,wav,m4a',
            'duration_seconds' => 'nullable|integer',
            'skipped' => 'nullable|boolean',
        ]);

        $interview = $user->mockInterviews()->where('id', $id)->firstOrFail();

        $audioPath = null;
        if ($request->hasFile('answer_audio')) {
            $audioPath = $request->file('answer_audio')->store('interview/audio', 'public');
        }

        $answer = $interview->answers()->create([
            'mock_interview_question_id' => $request->question_id,
            'user_id' => $user->id,

            'answer_text' => $request->answer_text,
            'answer_audio' => $audioPath,
            'duration_seconds' => $request->duration_seconds,

            'skipped' => $request->boolean('skipped'),
            'status' => 'processing',
        ]);

        dispatch(new \App\Jobs\ProcessInterviewAnswer(
            interviewAnswerId: $answer->id,
            lang: $interview->language
        ));

        $next = $interview->questions()
            ->where('order', '>', $answer->question->order)
            ->orderBy('order')
            ->first();

        return response()->json([
            "status" => "saved",
            "answer_id" => $answer->id,
            "next_question" => $next ? [
                "id" => $next->id,
                "order" => $next->order,
                "question" => $next->question_text,
                "audio" => $next->question_audio_url,
            ] : null,
            "message" => $next ? "Next question loaded" : "Interview completed"
        ]);
    }



    // helper function
    private function buildResumePromptText($resume, $experiences, $skills, $education, $analysis)
    {
        $text = "User Resume Summary:\n\n";

        $text .= "Desired Position: {$resume->desired_position}\n";
        $text .= "Professional Summary: {$resume->professional_summary}\n\n";

        $text .= "Experience:\n";
        foreach ($experiences as $exp) {
            $text .= "- {$exp['position']} at {$exp['company']} ({$exp['start_date']} - {$exp['end_date']})\n";
            if ($exp['description']) {
                $text .= "  {$exp['description']}\n";
            }
        }
        $text .= "\n";

        $text .= "Skills:\n";
        foreach ($skills as $s) {
            $text .= "- {$s['name']} ({$s['level']})\n";
        }
        $text .= "\n";

        $text .= "Education:\n";
        foreach ($education as $ed) {
            $text .= "- {$ed['degree']} at {$ed['institution']} ({$ed['start_date']} - {$ed['end_date']})\n";
        }
        $text .= "\n";

        $text .= "Strengths: " . implode(", ", $analysis->strengths ?? []) . "\n";
        $text .= "Weaknesses: " . implode(", ", $analysis->weaknesses ?? []) . "\n\n";

        return $text;
    }
}
