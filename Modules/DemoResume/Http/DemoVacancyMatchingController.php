<?php

namespace Modules\DemoResume\Http;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Modules\Vacancies\Http\Requests\VacancyMatchRequest;
use Modules\Vacancies\Http\Resources\VacancyMatchResource;
use Modules\Vacancies\Services\DemoVacancyMatchingService;

class DemoVacancyMatchingController extends Controller
{
    protected DemoVacancyMatchingService $service;

    public function __construct(DemoVacancyMatchingService $service)
    {
        $this->service = $service;
    }

    public function match(VacancyMatchRequest $request, DemoVacancyMatchingService $service)
    {
        // Bodydan chat_id olish
        $chatId = $request->input('chat_id');

        // chat_id orqali userni topish
        $user = User::where('chat_id', $chatId)->firstOrFail();

        $resume = $user
            ->resumes()
            ->where('is_primary', true)
            ->firstOrFail();

        // userga tegishli resume ID larni olish
        $resumeIds = $user->resumes()->pluck('id');

        $service->matchResume($resume, $resume->title ?? $resume->description);


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


    public function myMatches(Request $request)
    {
        $chatId = $request->query('chat_id');

        $user = User::where('chat_id', $chatId)->firstOrFail();

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
