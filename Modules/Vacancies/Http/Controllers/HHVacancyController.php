<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
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
        $query   = $request->get('query', 'laravel developer');
        $page    = (int) $request->get('page', 0);
        $perPage = (int) $request->get('per_page', 20);

        $vacancies = $this->hh->search($query, $page, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $vacancies,
        ]);
    }

    public function show(string $id)
    {
        $vacancy = $this->hh->getById($id);

        return response()->json([
            'success' => true,
            'data'    => $vacancy,
        ]);
    }

    public function apply(Vacancy $vacancy)
    {
        $user = auth()->user();

        $resumeId = $user->settings->resume_id;
        if (!$resumeId) {
            return response()->json([
                'success' => false,
                'message' => 'No primary resume set. Please set a primary resume in your settings.',
            ], 400);
        }
        $coverLetter = $user->preference->cover_letter ?? null;
        return DB::transaction(function () use ($user, $vacancy, $resumeId, $coverLetter) {
            $application = Application::updateOrCreate([
                [
                    'user_id'    => $user->id,
                    'vacancy_id' => $vacancy->id,
                    'resume_id'  => $resumeId,
                ],
                [
                    'status' => 'applied',
                    'submitted_at' => now(),
                    'match_score' => $vacancy->pivot->score_percent ?? null,

                ]
            ]);
            if ($vacancy->external_id && $resumeId) {
                try {
                    app(\Modules\Vacancies\Interfaces\HHVacancyInterface::class)
                        ->applyToVacancy($vacancy->external_id, $resumeId, $coverLetter);

                    $application->update(['hh_status' => 'applied']);
                } catch (\Throwable $e) {
                    Log::error("HH apply failed", ['error' => $e->getMessage()]);
                    $application->update(['hh_status' => 'failed']);
                }
            }

            return $application;
        });
    }
}
