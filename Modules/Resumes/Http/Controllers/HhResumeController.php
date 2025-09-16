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

    public function saveAsPrimary($resumeId)
    {
        $user = Auth::user();
        $user->settings()->updateOrCreate([], ['resume_id' => $resumeId]);

        return response()->json([
            'message' => 'Resume set as primary successfully.'
        ]);
    }
}
