<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $adminEmail = config('admin.seeder.email', 'admin@inter.ai');

        if (! $user || $user->email !== $adminEmail) {
            abort(403);
        }

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        if (! Hash::check($validated['old_password'], $user->password)) {
            return back()->withErrors(['old_password' => __('The current password is incorrect.')]);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('status', __('Password updated successfully.'));
    }
}
