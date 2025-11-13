<?php
namespace Modules\Resumes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HhAccount;
use Illuminate\Support\Facades\Auth;
use Modules\Resumes\Repositories\HhResumeRepository;

class HhResumeController extends Controller
{
    public function __construct(private readonly HhResumeRepository $repo) {}

    public function myHhResumes()
    {
        $userId = Auth::id();

        $account = HhAccount::where('user_id', $userId)->firstOrFail();

        $resumes = $this->repo->fetchMyResumes($account);

        return response()->json([
            'resumes' => $resumes,
            'success' => true
        ]);
    }

    /**
     * Return only published HH resumes for the authenticated user.
     */
    public function myPublishedHhResumes()
    {
        $userId = Auth::id();
        $account = HhAccount::where('user_id', $userId)->firstOrFail();

        $res = $this->repo->fetchMyResumes($account);

        if (!($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to fetch resumes',
            ], 400);
        }

        $items = $res['data']['items'] ?? [];
        $published = collect($items)
            ->filter(fn ($r) => data_get($r, 'status.id') === 'published')
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'items' => $published,
        ]);
    }

    public function saveAsPrimary($resumeId)
    {
        $user = Auth::user();
        $user->settings()->updateOrCreate([], ['resume_id' => $resumeId]);

        return response()->json([
            'message' => 'Resume set as primary successfully.'
        ]);
    }
}
