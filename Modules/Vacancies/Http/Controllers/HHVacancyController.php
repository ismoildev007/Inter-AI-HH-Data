<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MatchResult;
use App\Models\Resume;
use App\Models\User;
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

    public function apply($id)
    {
        $user = auth()->user();

        $resumeId = $user->settings->resume_id;
        if (!$resumeId) {
            return response()->json([
                'success' => false,
                'message' => 'No primary resume set. Please set a primary resume in your settings.',
            ], 400);
        }

        $vacancy = Vacancy::where('external_id', $id)->firstOrFail();

        $userResume = Resume::where('user_id', $user->id)
            ->where('is_primary', true)
            ->firstOrFail();

        $matchResult = MatchResult::where('vacancy_id', $vacancy->id)
            ->where('resume_id', $userResume->id)
            ->first();

        $coverLetter = $user->preference?->cover_letter ?? null;

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
}
