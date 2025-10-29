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
use Modules\Vacancies\Services\VacancyMatchingService;

class VacancyMatchingController extends Controller
{
    protected VacancyMatchingService $service;

    public function __construct(VacancyMatchingService $service)
    {
        $this->service = $service;
    }

    public function match(VacancyMatchRequest $request, VacancyMatchingService $service)
    {
        $resume = auth()->user()
            ->resumes()
            ->where('is_primary', true)
            ->firstOrFail();
        $user = Auth::user();
        $resumeIds = $user->resumes()->pluck('id');

        // Log::info('Starting match for user', ['user_id' => auth()->id(), 'resume_id' => $resume->id]);
        // MatchResumeJob::dispatch($resume, $resume->title ?? $resume->description);
        // Log::info('Dispatched MatchResumeJob', ['user_id' => auth()->id(), 'resume_id' => $resume->id]);
        $savedData = $service->matchResume($resume, $resume->title ?? $resume->description);

        $results = MatchResult::query()
            ->leftJoin('vacancies', 'vacancies.id', '=', 'match_results.vacancy_id')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw("CASE WHEN vacancies.source = 'hh' THEN 1 WHEN vacancies.source = 'telegram' THEN 0 ELSE -1 END DESC")
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('match_results.score_percent')
            ->select('match_results.*')
            ->with('vacancy.employer')
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

//        $results = MatchResult::with('vacancy.employer')
//            ->leftJoin('applications', function ($join) use ($user) {
//                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
//                    ->where('applications.user_id', $user->id);
//            })
//            ->whereIn('match_results.resume_id', $resumeIds)
//            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
//            ->orderByDesc('match_results.score_percent')
//            ->select('match_results.*')
//            ->get();
        $results = MatchResult::query()
            ->leftJoin('vacancies', 'vacancies.id', '=', 'match_results.vacancy_id')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw("CASE WHEN vacancies.source = 'hh' THEN 1 WHEN vacancies.source = 'telegram' THEN 0 ELSE -1 END DESC")
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('match_results.score_percent')
            ->select('match_results.*')
            ->with('vacancy.employer')
            ->get();



        return response()->json([
            'status'  => 'success',
            'message' => 'Matching finished successfully.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }
}
