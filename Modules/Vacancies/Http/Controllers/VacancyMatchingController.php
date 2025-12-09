<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Controllers\UsersController;
use Modules\Vacancies\Http\Requests\VacancyMatchRequest;
use Modules\Vacancies\Http\Resources\VacancyMatchResource;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Modules\Vacancies\Jobs\MatchResumeJob;
use Modules\Vacancies\Repositories\VacancyRepository;
use Modules\Vacancies\Services\VacancyMatchingService;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Http;
class VacancyMatchingController extends Controller
{
    protected VacancyMatchingService $service;
    protected HHVacancyInterface $hhRepository;
    protected VacancyRepository $vacancyRepository;

    public function __construct(VacancyMatchingService $service, HHVacancyInterface $hhRepository, VacancyRepository $vacancyRepository)
    {
        $this->service = $service;
        $this->hhRepository = $hhRepository;
        $this->vacancyRepository = $vacancyRepository;
    }

    public function myMatches(Request $request, VacancyMatchingService $service)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search'); // Frontend dan kelgan search parametri

        if (!empty($searchQuery)) {
            return $this->searchVacancies($searchQuery, $user);
        }

        // 🔹 Agar search bo'lmasa, Match Results dan chiqarish (default xolat)
        $resumeIds = $user->resumes()->pluck('id');

        $results = MatchResult::with('vacancy.employer')
            ->join('vacancies', 'vacancies.id', '=', 'match_results.vacancy_id')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('vacancies.created_at')
            ->select('match_results.*')
            ->get();

        if ($results->isEmpty()) {
            $resume = $user->resumes()
                ->where('is_primary', true)
                ->first();

            if ($resume) {
                $service->matchResume($resume, $resume->title ?? $resume->description);

                $results = MatchResult::with('vacancy.employer')
                    ->join('vacancies', 'vacancies.id', '=', 'match_results.vacancy_id')
                    ->leftJoin('applications', function ($join) use ($user) {
                        $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                            ->where('applications.user_id', $user->id);
                    })
                    ->whereIn('match_results.resume_id', $resumeIds)
                    ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
                    ->orderByDesc('vacancies.created_at')
                    ->select('match_results.*')
                    ->get();
            }
        }

        if ($results->isEmpty()) {
            if (!$user->resumes()->exists()) {
                app(UsersController::class)->destroyIfNoResumes(request());
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Hech qanday rezyume yoki moslik topilmadi. Foydalanuvchi o\'chirildi.',
                'data'    => [],
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Matching finished successfully.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }


    protected function searchVacancies(string $query, $user)
    {
        // 🔥 3 xil query varianti (asosiy, upper, lower)
        $queries = [
            $query,
            mb_strtolower($query),
            mb_strtoupper($query),
        ];

        $hhVacanciesCollection = collect();
        $localVacanciesCollection = collect();

        foreach ($queries as $q) {

            // 1️⃣ HH Search
            $hhResults = $this->hhRepository->search($q, 0, 100, ['area' => 97]);
            Log::info('HH search completed', [
                'original_query' => $query,
                'used_query' => $q,
                'count' => count($hhResults['items'] ?? [])
            ]);

            // HH dan kelgan natijalarni DB ga saqlash
            if (!empty($hhResults['items'])) {
                $saved = $this->vacancyRepository->bulkUpsertFromHH($hhResults['items']);
                $hhVacanciesCollection = $hhVacanciesCollection->merge($saved);
            }

            // 2️⃣ LOCAL DB SEARCH
            $local = \App\Models\Vacancy::with('employer')
                ->where(function ($w) use ($q) {
                    $w->where('title', 'LIKE', "%{$q}%")
                        ->orWhere('description', 'LIKE', "%{$q}%");
                })
                ->orderByDesc('created_at')
                ->get();

            $localVacanciesCollection = $localVacanciesCollection->merge($local);
        }

        // 3️⃣ Ikkala natijani birlashtirish
        $allVacancies = $hhVacanciesCollection
            ->merge($localVacanciesCollection)
            ->unique('id')
            ->values();

        // 4️⃣ Vacancy → MatchResult formatiga o‘tkazish
        $results = $allVacancies->map(function ($vacancy) use ($user) {

            if ($vacancy instanceof \App\Models\MatchResult) {
                return $vacancy;
            }

            $matchResult = new \App\Models\MatchResult();
            $matchResult->id = null;
            $matchResult->vacancy_id = $vacancy->id;
            $matchResult->resume_id = optional(
                $user->resumes()->where('is_primary', true)->first()
            )->id;

            $matchResult->score_percent = 0;
            $matchResult->explanations = json_encode(['text' => 'Search result']);
            $matchResult->setRelation('vacancy', $vacancy);
            $matchResult->exists = false;

            return $matchResult;
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Search completed.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }

    public function match(VacancyMatchRequest $request, VacancyMatchingService $service)
    {
        $resume = auth()->user()
            ->resumes()
            ->where('is_primary', true)
            ->firstOrFail();
        $user = Auth::user();
        $resumeIds = $user->resumes()->pluck('id');

        $service->matchResume($resume, $resume->title ?? $resume->description);

        $results = MatchResult::with('vacancy.employer')
            ->join('vacancies', 'vacancies.id', '=', 'match_results.vacancy_id')
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'match_results.vacancy_id')
                    ->where('applications.user_id', $user->id);
            })
            ->whereIn('match_results.resume_id', $resumeIds)
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('vacancies.created_at')
            ->select('match_results.*')
            ->get();

        if ($results->isEmpty()) {
            $user = Auth::user();

            if (! $user->resumes()->exists()) {
                app(UsersController::class)->destroyIfNoResumes(request());
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Hech qanday rezyume yoki moslik topilmadi. Foydalanuvchi o‘chirildi.',
                'data'    => [],
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Matching finished successfully.',
            'data'    => VacancyMatchResource::collection($results),
        ]);
    }
}
