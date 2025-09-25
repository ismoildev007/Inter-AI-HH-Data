<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;

class ApplicationController extends Controller
{
    /**
     * Applications list.
     */
    public function index()
    {
        $applications = Application::with(['user', 'vacancy', 'resume'])->latest()->paginate(15);
        return view('admin::Applications.index', compact('applications'));
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
