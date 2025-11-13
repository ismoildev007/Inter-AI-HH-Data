<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MatchResult;
use App\Models\Resume;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class HHVacancyController extends Controller
{
    protected HHVacancyInterface $hh;

    public function __construct(HHVacancyInterface $hh)
    {
        $this->hh = $hh;
    }

    public function index(Request $request)
    {
        $query   = $request->get('query');
        $page    = (int) $request->get('page', 0);
        $perPage = (int) $request->get('per_page', 100);

        $vacancies = $this->hh->search($query, $page, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $vacancies,
        ]);
    }

    public function show(string $id)
    {
        $vacancy = $this->hh->getById($id);
        if (!$vacancy) {
            return response()->json([
                'success' => false,
                'message' => 'Vacancy not found',
            ], 404);
        }

        $user = auth()->user();
        $vacancyData = Vacancy::where('external_id', $vacancy['id'])->first();
        $applied = false;
        if ($user && $vacancyData) {
            $applied = Application::where('user_id', $user->id)
                ->where('vacancy_id', $vacancyData->id)
                ->exists();
        }
        return response()->json([
            'success' => true,
            'data'    => [
                'vacancy' => $vacancy['id'],
                'raw'     => $vacancy,
                'status' => $applied,
            ],
        ]);
    }

    public function telegramShow($id)
    {
        $user = auth()->user();
        $vacancy = Vacancy::where('id', $id)->first();

        if (!$vacancy) {
            return response()->json([
                'success' => false,
                'message' => 'Vacancy not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'category' => $vacancy->category,
                'description' => $vacancy->description,
                'company' => $vacancy->company,
                'contact' => $vacancy->contact,
                'language' => $vacancy->language,
                'signature' => $vacancy->signature,
                'source_id' => $vacancy->source_id,
                'apply_url' => $vacancy->apply_url,
                'source_message_id' => $vacancy->source_message_id,
                'target_message_id' => $vacancy->target_message_id,
                'target_msg_id' => $vacancy->target_msg_id,
            ],
        ]);
    }

    public function apply(Request $request, $id)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'resume_id' => ['required', 'string'], // HH resume ID (external)
        ]);
        // HH resume to use for this application (from request)
        $resumeId = $validated['resume_id'];

        $vacancy = Vacancy::where('external_id', $id)->firstOrFail();

        $userResume = Resume::where('user_id', $user->id)
            ->where('is_primary', true)
            ->firstOrFail();

        $matchResult = MatchResult::where('vacancy_id', $vacancy->id)
            ->where('resume_id', $userResume->id)
            ->first();

        $coverLetter = $user->preference?->cover_letter ?? null;

        // Qo'lda apply qilinayotgan bo'lsa, user tanlovini settingsga majburan yozmaymiz.
        // Auto-apply uchun user alohida tanlov qiladi (settings orqali).

        return DB::transaction(function () use ($user, $vacancy, $resumeId, $coverLetter, $matchResult, $userResume) {
            $existing = Application::where('user_id', $user->id)
                ->where('vacancy_id', $vacancy->id)
                ->first();

            if ($existing && $existing->status === 'response') {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already responded to this vacancy.',
                ], 409);
            }

            $application = Application::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'vacancy_id' => $vacancy->id,
                    'resume_id'  => $userResume->id,
                ],
                [
                    'hh_resume_id' => $resumeId,
                    'status'       => 'response',
                    'submitted_at' => now(),
                    'match_score'  => $matchResult?->score_percent,
                    'external_id'  => $vacancy->external_id,
                ]
            );

            // Only call HH API if it's a new application or hh_status is null/failed
            if ($vacancy->external_id && $resumeId && (!$existing || $existing->hh_status !== 'response')) {
                try {
                    app(\Modules\Vacancies\Interfaces\HHVacancyInterface::class)
                        ->applyToVacancy($vacancy->external_id, $resumeId, $coverLetter);

                    $application->update(['hh_status' => 'response']);
                } catch (\Throwable $e) {
                    Log::error("HH apply failed", ['error' => $e->getMessage()]);
                    $application->update(['hh_status' => 'failed']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully.',
                'data'    => $application,
            ]);
        });
    }

    public function hhSearch(Request $request)
    {

        $query = $request->get('query', 'developer');
        $page = (int) $request->get('page', 0);
        $perPage = (int) $request->get('per_page', 100);

        try {
            $vacancies = $this->hh->search($query, $page, $perPage);
            return response()->json([
                'success' => true,
                'count' => count($vacancies['items']),
                'data' => $vacancies,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
