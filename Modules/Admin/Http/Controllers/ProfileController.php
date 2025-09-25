<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user) {
            $user->load([
                'role',
                'credit',
                'settings',
                'resumes',
                'profileViews',
            ]);

            $applicationsCount = Application::where('user_id', $user->id)->count();
            $resumesCount = $user->resumes->count();
            $profileViewsCount = $user->profileViews->count();
        } else {
            $applicationsCount = $resumesCount = $profileViewsCount = 0;
        }

        return view('admin::Admin.Profile.index', compact(
            'user',
            'applicationsCount',
            'resumesCount',
            'profileViewsCount'
        ));
    }
}
