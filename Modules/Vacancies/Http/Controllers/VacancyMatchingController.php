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
        // Log::info('Starting match for user', ['user_id' => auth()->id(), 'resume_id' => $resume->id]);
        // MatchResumeJob::dispatch($resume, $resume->title ?? $resume->description);
        // Log::info('Dispatched MatchResumeJob', ['user_id' => auth()->id(), 'resume_id' => $resume->id]);
        $savedData = $service->matchResume($resume, $resume->title ?? $resume->description);
        $results = MatchResult::with('vacancy.area', 'vacancy.employer')
            ->where('resume_id', $resume->id)
            ->orderByDesc('score_percent')
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
            ->whereIn('resume_id', $resumeIds)
            ->orderByDesc('score_percent')
            ->get();

        return VacancyMatchResource::collection($results);
    }
}
