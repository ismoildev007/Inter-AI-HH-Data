<?php

namespace Modules\Resumes\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Resumes\Http\Requests\ResumeUpdateRequest;
use Modules\Resumes\Http\Resources\ResumeResource;
use Modules\Resumes\Services\ResumeService;
use App\Models\Resume;
use Modules\Resumes\Http\Requests\ResumeStoreRequest;

class ResumesController extends Controller
{
    protected ResumeService $service;

    public function __construct(ResumeService $service)
    {
        $this->service = $service;
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

    public function show(int $id)
    {
        $resume = Resume::with('analysis')->findOrFail($id);
        return new ResumeResource($resume);
    }

    public function update(ResumeUpdateRequest $request, int $id)
    {
        $resume = Resume::findOrFail($id);
        $resume = $this->service->update($resume, $request->validated());
        return new ResumeResource($resume->load('analysis'));
    }

    public function destroy(int $id)
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
    public function setPrimary(int $id)
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);

        $this->service->setPrimary($resume);

        return new ResumeResource($resume->fresh()->load('analysis'));
    }
}
