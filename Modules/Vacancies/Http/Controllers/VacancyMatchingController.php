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
use Modules\Vacancies\Services\VacancyMatchingService;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Http;
class VacancyMatchingController extends Controller
{
    protected VacancyMatchingService $service;
    protected HHVacancyInterface $hhRepository;

    public function __construct(VacancyMatchingService $service, HHVacancyInterface $hhRepository)
    {
        $this->service = $service;
        $this->hhRepository = $hhRepository;
    }

    public function myMatches(Request $request, VacancyMatchingService $service)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search'); // Frontend dan kelgan search parametri

        // 🔹 Agar search mavjud bo'lsa, HeadHunter + local DB dan qidirish
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
        $hhResults = $this->hhRepository->search($query, 0, 100, ['area' => 97]);

        // 2️⃣ Local vacancies table dan qidirish
        $localResults = \App\Models\Vacancy::with('employer')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->get();

        $hhVacancyIds = [];
        if (!empty($hhResults['items'])) {
            foreach ($hhResults['items'] as $item) {
                $vacancy = \App\Models\Vacancy::updateOrCreate(
                    [
                        'external_id' => $item['id'], // asosiy identifikator
                        'source'      => 'hh',
                    ],
                    [
                        'source'            => 'hh',
                        'external_id'       => $item['id'],
                        'title'             => $item['name'] ?? 'N/A',
                        'description'       => $item['snippet']['responsibility']
                            . "\n" . ($item['snippet']['requirement'] ?? ''),
                        'category'          => $item['professional_roles'][0]['name']
                            ?? null,
                        'area_id'           => $item['area']['id'] ?? null,
//                        'schedule_id'       => $item['schedule']['id'] ?? null,
//                        'employment_id'     => $item['employment']['id'] ?? null,
                        'salary_from'       => $item['salary']['from'] ?? null,
                        'salary_to'         => $item['salary']['to'] ?? null,
                        'salary_currency'   => $item['salary']['currency'] ?? null,
                        'salary_gross'      => $item['salary']['gross'] ?? null,
                        'published_at'      => $item['published_at'] ?? now(),
                        'expires_at'        => $item['expires_at'] ?? null,
                        'status'            => $item['archived'] ? 'archived' : 'active',
                        'apply_url'         => $item['apply_alternate_url'] ?? null,
                        'views_count'       => $item['counters']['views'] ?? 0,
                        'responses_count'   => $item['counters']['responses'] ?? 0,
                        'raw_data'          => json_encode($item, JSON_UNESCAPED_UNICODE),
                        'company'           => $item['employer']['name'] ?? null,
                        'contact'           => $item['contacts']['email'] ?? null,
                        'language'          => $item['language'] ?? null,
                        'signature'         => null,
                        'source_id'         => $item['id'] ?? null,
                        'raw_hash'          => md5(json_encode($item)),
                        'normalized_hash'   => null,

                        // employer_id — alohida method orqali
                        'employer_id'       => $this->getOrCreateEmployer($item['employer'] ?? []),
                    ]
                );

                $hhVacancyIds[] = $vacancy->id;
            }
        }

        // 4️⃣ HeadHunter va local natijalarini birlashtirish
        $allVacancies = \App\Models\Vacancy::with('employer')
            ->whereIn('id', array_merge($hhVacancyIds, $localResults->pluck('id')->toArray()))
            ->leftJoin('applications', function ($join) use ($user) {
                $join->on('applications.vacancy_id', '=', 'vacancies.id')
                    ->where('applications.user_id', $user->id);
            })
            ->orderByRaw('CASE WHEN applications.id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('vacancies.created_at')
            ->select('vacancies.*')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Search completed.',
            'data'    => VacancyMatchResource::collection($allVacancies),
        ]);
    }

    protected function getOrCreateEmployer(array $employerData)
    {
        if (empty($employerData['id'])) {
            return null;
        }

        $employer = \App\Models\Employer::firstOrCreate(
            ['external_id' => $employerData['id']],
            [
                'name' => $employerData['name'] ?? 'Unknown',
                'logo_url' => $employerData['logo_urls']['240'] ?? null,
                'url' => $employerData['alternate_url'] ?? null,
            ]
        );

        return $employer->id;
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
