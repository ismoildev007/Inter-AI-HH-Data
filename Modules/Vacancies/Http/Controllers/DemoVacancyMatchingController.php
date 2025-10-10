<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Http\Requests\VacancyMatchRequest;
use Modules\Vacancies\Http\Resources\VacancyMatchResource;
use Modules\Vacancies\Jobs\MatchResumeJob;
use Modules\Vacancies\Services\DemoVacancyMatchingService;
use Modules\Vacancies\Services\VacancyMatchingService;

class DemoVacancyMatchingController extends Controller
{
    protected DemoVacancyMatchingService $service;

    public function __construct(DemoVacancyMatchingService $service)
    {
        $this->service = $service;
    }

    public function match(VacancyMatchRequest $request, DemoVacancyMatchingService $service)
    {
        $resume = auth()->user()
            ->resumes()
            ->where('is_primary', true)
            ->firstOrFail();
        $user = Auth::user();
        $resumeIds = $user->resumes()->pluck('id');

        $results = MatchResult::with('vacancy.employer')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('match_results.score_percent')
            ->select('match_results.*')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Matching finished successfully.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }


    public function myMatches()
    {
        $user = Auth::user();

        $resumeIds = $user->resumes()->pluck('id');

        $results = MatchResult::with('vacancy.employer')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('match_results.score_percent')
            ->select('match_results.*')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Matching finished successfully.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }
}
