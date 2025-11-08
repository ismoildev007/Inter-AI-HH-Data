<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Applications list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        // Show a single row per user (who has at least one application) with their applications count
        $usersQuery = \App\Models\User::query()
            ->whereHas('applications')
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';

                $query->where(function ($q) use ($search, $like) {
                    $q->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

                    if (ctype_digit($search)) {
                        $q->orWhere('phone', 'like', '%' . $search . '%');
                    }
                })
                // Also allow searching by vacancy/resume/application properties
                ->orWhereHas('applications', function ($appQ) use ($search, $like) {
                    $appQ->whereRaw('LOWER(status) LIKE ?', [$like])
                        ->orWhere('applications.id', ctype_digit($search) ? (int) $search : null);
                })
                ->orWhereHas('applications.vacancy', function ($vacQ) use ($like) {
                    $vacQ->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(company) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(category) LIKE ?', [$like]);
                })
                ->orWhereHas('applications.resume', function ($resQ) use ($like) {
                    $resQ->whereRaw('LOWER(title) LIKE ?', [$like]);
                });
            })
            // Count applications per user
            ->withCount('applications')
            // Latest application for quick preview data
            ->with(['applications' => function ($q) {
                $q->select(['id', 'user_id', 'vacancy_id', 'status', 'match_score', 'submitted_at', 'created_at'])
                    ->orderByRaw('COALESCE(submitted_at, created_at) DESC');
            }, 'applications.vacancy'])
            // Latest submission timestamp to sort by
            ->addSelect(['latest_application_at' => Application::selectRaw('MAX(COALESCE(submitted_at, created_at))')
                ->whereColumn('applications.user_id', 'users.id')])
            ->orderByDesc('latest_application_at');

        $users = $usersQuery->paginate(150)->withQueryString();

        return view('admin::Applications.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show application.
     */
    public function show($id)
    {
        $application = Application::with(['user', 'vacancy', 'resume'])->findOrFail($id);
        return view('admin::Applications.show', compact('application'));
    }

    /**
     * Show a single user's applications list.
     */
    public function user($id)
    {
        $user = \App\Models\User::query()
            ->with(['applications' => function ($q) {
                $q->with(['vacancy', 'resume'])
                    ->orderByRaw('COALESCE(submitted_at, created_at) DESC');
            }])
            ->findOrFail($id);

        return view('admin::Applications.Application-show', [
            'user' => $user,
            'applications' => $user->applications,
        ]);
    }

    /**
     * Only applications with status = interview.
     */
    public function interview(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $applications = Application::with(['user', 'vacancy', 'resume'])
            ->whereRaw("LOWER(COALESCE(status,'')) = ?", ['interview'])
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->where(function ($inner) use ($search, $like, $normalized) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('applications.id', (int) $search);
                    }
                    $inner->orWhereHas('user', function ($user) use ($like, $search, $normalized) {
                            $user->whereRaw('LOWER(first_name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                            if (ctype_digit($search)) {
                                $user->orWhere('phone', 'like', '%' . $search . '%');
                            }
                        })
                        ->orWhereHas('vacancy', function ($vacancy) use ($like) {
                            $vacancy->whereRaw('LOWER(title) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(company) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(category) LIKE ?', [$like]);
                        })
                        ->orWhereHas('resume', function ($resume) use ($like) {
                            $resume->whereRaw('LOWER(title) LIKE ?', [$like]);
                        });
                });
            })
            ->latest()
            ->paginate(150)
            ->withQueryString();

        return view('admin::Applications.interview', [
            'applications' => $applications,
            'search' => $search,
        ]);
    }
}
