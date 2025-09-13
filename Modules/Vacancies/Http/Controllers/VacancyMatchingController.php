<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
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
        Log::info('Primary resume found', ['resume_id' => $resume->id]);

        $results = $this->service->matchResume($resume, $resume->title);

        return VacancyMatchResource::collection($results);
    }
}
