<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Users list.
     */
    public function index()
    {
        $users = User::with('role')->latest()->paginate(15);
        return view('admin::Users.index', compact('users'));
    }

    /**
     * Show user.
     */
    public function show($id)
    {
        $user = User::with(['role', 'resumes'])->findOrFail($id);
        return view('admin::Users.show', compact('user'));
    }
}
