<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResumeController extends Controller
{
    protected function resolveResumeDisk()
    {
        try {
            if (class_exists(\League\Flysystem\AwsS3V3\PortableVisibilityConverter::class)) {
                return Storage::disk('spaces');
            }
        } catch (\Throwable $e) {
            // fallback below
        }
        $fallback = config('filesystems.default', 'public');
        return Storage::disk($fallback);
    }
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
            ->paginate(100)
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

        // Provide category options (exclude "Other") for editing
        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $categoryOptions = $categorizer->getLabelsExceptOther();

        return view('admin::Resumes.categories.show', [
            'resumes' => $resumes,
            'category' => $selectedCategory,
            'totalInCategory' => $totalInCategory,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    /**
     * Show resume.
     */
    public function show($id)
    {
        $resume = Resume::with(['user', 'analysis'])->findOrFail($id);

        // Provide category options excluding "Other" for inline editing
        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $categoryOptions = $categorizer->getLabelsExceptOther();

        return view('admin::Resumes.show', compact('resume', 'categoryOptions'));
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

        $disk = $this->resolveResumeDisk();
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

    /**
     * Download all resumes as a single ZIP archive.
     */
    public function downloadAll()
    {
        @set_time_limit(0);

        $disk = $this->resolveResumeDisk();

        $resumes = Resume::query()
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->get(['id', 'file_path']);

        if ($resumes->isEmpty()) {
            return redirect()->back()->with('status', 'No resumes available to download.');
        }

        $downloadName = 'resumes-' . now()->format('Ymd-His') . '.zip';

        // If ZipStream library is available, stream the ZIP to the browser
        if (class_exists('ZipStream\\ZipStream')) {
            return response()->stream(function () use ($resumes, $disk, $downloadName) {
                $options = new \ZipStream\Option\Archive();
                // Laravel will send headers; keep ZipStream from sending its own
                $options->setSendHttpHeaders(false);
                $options->setFlushOutput(true);
                $options->setEnableZip64(true);

                $zip = new \ZipStream\ZipStream(null, $options);

                foreach ($resumes as $resume) {
                    $path = ltrim((string) $resume->file_path, '/');

                    if (preg_match('#^https?://#i', $path)) {
                        continue;
                    }
                    if (!$disk->exists($path)) {
                        continue;
                    }

                    $base = basename($path) ?: ('resume-' . $resume->id);
                    $zipName = sprintf('%06d_%s', (int) $resume->id, $base);

                    try {
                        $stream = method_exists($disk, 'readStream') ? $disk->readStream($path) : null;
                        if (is_resource($stream)) {
                            $zip->addFileFromStream($zipName, $stream);
                            @fclose($stream);
                        } else {
                            $content = $disk->get($path);
                            if ($content !== false) {
                                $zip->addFile($zipName, $content);
                            }
                        }
                    } catch (Throwable $e) {
                        continue;
                    }
                }

                $zip->finish();
            }, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Fallback: build ZIP on disk then stream it
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'resumes_');
        if ($tmpZipPath === false) {
            abort(500, 'Failed to create temporary file for ZIP.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpZipPath, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZipPath);
            abort(500, 'Failed to open ZIP archive.');
        }

        foreach ($resumes as $resume) {
            $path = ltrim((string) $resume->file_path, '/');

            if (preg_match('#^https?://#i', $path)) {
                continue;
            }
            if (!$disk->exists($path)) {
                continue;
            }

            $base = basename($path) ?: ('resume-' . $resume->id);
            $zipName = sprintf('%06d_%s', (int) $resume->id, $base);

            try {
                $stream = method_exists($disk, 'readStream') ? $disk->readStream($path) : null;
                if (is_resource($stream)) {
                    // Efficiently copy stream into the zip entry
                    $zip->addEmptyDir(dirname($zipName) === '.' ? '' : dirname($zipName));
                    // ZipArchive has no direct addFromStream, so read chunks
                    $zip->addFromString($zipName, stream_get_contents($stream));
                    @fclose($stream);
                } else {
                    $content = $disk->get($path);
                    if ($content !== false) {
                        $zip->addFromString($zipName, $content);
                    }
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        $zip->close();

        return response()->download($tmpZipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Update resume category (exclude "Other" from allowed choices).
     */
    public function updateCategory(Request $request, Resume $resume)
    {
        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        // Only allow human labels except "Other"
        $allowed = $categorizer->getLabelsExceptOther();

        $validated = $request->validate([
            'category' => ['required', Rule::in($allowed)],
        ]);

        $resume->update([
            'category' => $validated['category'],
        ]);

        return redirect()->back()->with('status', 'Resume category updated.');
    }

    /**
     * Delete a resume and its related records.
     */
    public function destroy(Resume $resume)
    {
        DB::transaction(function () use ($resume) {
            // Delete related analysis and match results if exist
            if (method_exists($resume, 'analysis') && $resume->relationLoaded('analysis') ? $resume->analysis : $resume->analysis()->exists()) {
                optional($resume->analysis)->delete();
            }
            if (method_exists($resume, 'matchResults')) {
                $resume->matchResults()->delete();
            }

            // Try to remove stored file if it is on our storage disk (not external URL)
            try {
                $path = $resume->file_path ? ltrim($resume->file_path, '/') : null;
                if ($path && !preg_match('#^https?://#i', $resume->file_path)) {
                    $disk = Storage::disk('spaces');
                    if ($disk->exists($path)) {
                        $disk->delete($path);
                    }
                }
            } catch (Throwable $e) {
                // ignore storage errors
            }

            $resume->delete();
        });

        return redirect()->route('admin.resumes.index')->with('status', 'Resume deleted.');
    }
}
