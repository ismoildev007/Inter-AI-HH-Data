<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Stream the resume file inline for admins.
     */
    public function download($id): StreamedResponse
    {
        $resume = Resume::findOrFail($id);

        if (!$resume->file_path) {
            abort(404);
        }

        $disk = Storage::disk('public');
        $path = ltrim($resume->file_path, '/');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $filename = basename($path);
        $headers = [];

        if (!empty($resume->file_mime)) {
            $headers['Content-Type'] = $resume->file_mime;
        }

        // Force inline display so PDFs open in the browser.
        $headers['Content-Disposition'] = 'inline; filename="' . $filename . '"';

        return $disk->response($path, $filename, $headers);
    }
}
