<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Users list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->where(function ($inner) use ($like, $search, $normalized) {
                    $inner->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhere('phone', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin::Users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show user.
     */
    public function show($id)
    {
        $user = User::with(['role', 'resumes'])->findOrFail($id);

        $matchResultsQuery = MatchResult::query()
            ->whereNotNull('vacancy_id')
            ->whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        $matchedVacancyCount = (clone $matchResultsQuery)
            ->select('vacancy_id')
            ->distinct()
            ->count();

        $recentVacancyMatches = (clone $matchResultsQuery)
            ->with('vacancy')
            ->latest()
            ->get()
            ->filter(fn (MatchResult $result) => $result->vacancy)
            ->unique('vacancy_id')
            ->take(5)
            ->values();

        return view('admin::Users.show', [
            'user' => $user,
            'matchedVacancyCount' => $matchedVacancyCount,
            'recentVacancyMatches' => $recentVacancyMatches,
        ]);
    }

    /**
     * Show matched vacancies for a user.
     */
    public function vacancies(User $user)
    {
        $vacancyQuery = Vacancy::query()
            ->whereIn('id', function ($subQuery) use ($user) {
                $subQuery->select('match_results.vacancy_id')
                    ->from('match_results')
                    ->join('resumes', 'match_results.resume_id', '=', 'resumes.id')
                    ->whereNull('match_results.deleted_at')
                    ->where('match_results.vacancy_id', '>', 0)
                    ->where('resumes.user_id', $user->id);
            })
            ->orderByDesc('id');

        $vacancies = $vacancyQuery->paginate(20);

        $matchSummaries = MatchResult::query()
            ->with('resume')
            ->whereIn('vacancy_id', $vacancies->pluck('id'))
            ->whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->groupBy('vacancy_id')
            ->map(function ($group) {
                $bestMatch = $group->sortByDesc(function (MatchResult $match) {
                    $score = $match->score_percent ?? 0;
                    $timestamp = optional($match->created_at ?? $match->updated_at)->timestamp ?? 0;

                    return [$score, $timestamp];
                })->first();

                $latestMatch = $group->sortByDesc(function (MatchResult $match) {
                    return optional($match->created_at ?? $match->updated_at)->timestamp ?? 0;
                })->first();

                return [
                    'best_match' => $bestMatch,
                    'latest_match' => $latestMatch,
                    'resume_titles' => $group->pluck('resume.title')->filter()->unique()->values(),
                ];
            });

        return view('admin::Users.vacancies.index', [
            'user' => $user,
            'vacancies' => $vacancies,
            'matchSummaries' => $matchSummaries,
        ]);
    }
}
