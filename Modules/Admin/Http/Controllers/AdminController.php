<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Admin login page
        return view('admin::LoginRegister.login');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Admin register page
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id = null)
    {
        // Admin logout page (placeholder blade)
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Not used for now
        return view('admin::index');
    }

    /**
     * Handle admin login (POST /admin/login).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool)($credentials['remember'] ?? false);

        // Attempt to login with default web guard
        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // Ensure the authenticated user is an admin
        $user = Auth::user();
        if (! $user || ! $user->role || strtolower($user->role->name) !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('You are not authorized to access admin panel.'),
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Logout current user and redirect to login.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


}
