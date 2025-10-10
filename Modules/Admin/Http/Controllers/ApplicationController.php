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

        $applications = Application::with(['user', 'vacancy', 'resume'])
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->where(function ($inner) use ($search, $like, $normalized) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('applications.id', (int) $search);
                    }

                    $inner->orWhereRaw('LOWER(applications.status) LIKE ?', [$like])
                        ->orWhereHas('user', function ($user) use ($like, $search, $normalized) {
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
            ->paginate(15)
            ->withQueryString();

        return view('admin::Applications.index', [
            'applications' => $applications,
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
}
