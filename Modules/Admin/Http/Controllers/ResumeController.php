<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;

class ResumeController extends Controller
{
    /**
     * Resumes list.
     */
    public function index()
    {
        $resumes = Resume::with('user')->latest()->paginate(15);
        return view('admin::Resumes.index', compact('resumes'));
    }

    /**
     * Show resume.
     */
    public function show($id)
    {
        $resume = Resume::with(['user', 'analysis'])->findOrFail($id);
        return view('admin::Resumes.show', compact('resume'));
    }
}
