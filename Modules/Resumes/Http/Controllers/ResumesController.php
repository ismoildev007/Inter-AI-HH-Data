<?php

namespace Modules\Resumes\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Resumes\Http\Requests\ResumeUpdateRequest;
use Modules\Resumes\Http\Resources\ResumeResource;
use Modules\Resumes\Services\DemoResumeService;
use Modules\Resumes\Services\ResumeService;
use App\Models\Resume;
use Modules\Resumes\Http\Requests\ResumeStoreRequest;

class ResumesController extends Controller
{
    protected ResumeService $service;
    protected DemoResumeService $demoResumeService;

    public function __construct(
        ResumeService $service,
    DemoResumeService $demoResumeService)
    {
        $this->service = $service;
        $this->demoResumeService = $demoResumeService;
    }

    public function index()
    {
        $userId = auth()->id();

        return ResumeResource::collection(
            Resume::with('analysis')
                ->where('user_id', $userId)
                ->paginate(10)
        );
    }


    public function store(ResumeStoreRequest $request)
    {
        $resume = $this->service->create($request->validated() + ['user_id' => auth()->id()]);
        return new ResumeResource($resume->load('analysis'));
    }
    public function demoStore(ResumeStoreRequest $request)
    {
        $demoResume = $this->demoResumeService->create($request->validated());
        return new ResumeResource($demoResume->load('analysis'));
    }

    public function show(string $id)
    {
        $resume = Resume::with('analysis')->findOrFail($id);
        return new ResumeResource($resume);
    }

    public function update(ResumeUpdateRequest $request, string $id)
    {
        $resume = Resume::findOrFail($id);
        $resume = $this->service->update($resume, $request->validated());
        return new ResumeResource($resume->load('analysis'));
    }

    public function destroy(string $id)
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);

        $resume->delete();

        return response()->json([
            'message' => 'Resume deleted successfully.'
        ]);
    }


    /**
     * Set a resume as primary for the authenticated user.
     */
    public function setPrimary(string $id)
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);

        $this->service->setPrimary($resume);

        return new ResumeResource($resume->fresh()->load('analysis'));
    }
}
