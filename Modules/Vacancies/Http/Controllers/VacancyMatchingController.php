<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
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

        $results = $this->service->matchResume($resume, $request->get('query'));

        return VacancyMatchResource::collection($results);
    }
}
