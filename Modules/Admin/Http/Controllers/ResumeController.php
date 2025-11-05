<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResumeController extends Controller
{
    /**
     * Resumes list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $resumes = Resume::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->whereRaw('LOWER(title) LIKE ?', [$like]);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin::Resumes.index', [
            'resumes' => $resumes,
            'search' => $search,
        ]);
    }

    /**
     * Resume categories list.
     */
    public function categories(Request $request)
    {
        $categories = Resume::query()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('category')
            ->whereRaw("TRIM(COALESCE(category, '')) <> ''")
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $totalResumes = Resume::count();

        return view('admin::Resumes.categories.index', [
            'categories' => $categories,
            'totalResumes' => $totalResumes,
        ]);
    }

    /**
     * Show resumes by category.
     */
    public function categoryShow(string $category, Request $request)
    {
        // Route value should already be decoded; ensure we treat it as plain string
        $selectedCategory = $category;

        $resumes = Resume::with('user')
            ->where('category', $selectedCategory)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalInCategory = Resume::where('category', $selectedCategory)->count();

        return view('admin::Resumes.categories.show', [
            'resumes' => $resumes,
            'category' => $selectedCategory,
            'totalInCategory' => $totalInCategory,
        ]);
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

        $disk = Storage::disk('spaces');
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
