<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Http\Requests\VacancyMatchRequest;
use Modules\Vacancies\Http\Resources\VacancyMatchResource;
use Modules\Vacancies\Services\VacancyMatchingService;

class VacancyMatchingController extends Controller
{
    protected VacancyMatchingService $service;

    public function __construct(VacancyMatchingService $service)
    {
        $this->service = $service;
    }

    public function match(VacancyMatchRequest $request)
    {
        $resume = auth()->user()
            ->resumes()
            ->where('is_primary', true)
            ->firstOrFail();

        \Modules\Vacancies\Jobs\MatchResumeJob::dispatch($resume, $resume->title ?? $resume->description);

        return response()->json([
            'status' => 'queued',
            'message' => 'Resume matching started. Check back in a few moments.'
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
