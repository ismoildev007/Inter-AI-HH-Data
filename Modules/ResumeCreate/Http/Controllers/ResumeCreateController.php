<?php

namespace Modules\ResumeCreate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\TelegramBot\Services\TelegramBotService;
use Modules\ResumeCreate\Http\Requests\ResumeWizardRequest;
use Modules\ResumeCreate\Http\Requests\ResumePhotoRequest;
use Modules\ResumeCreate\Services\ResumeCreateService;
use Modules\ResumeCreate\Services\ResumePhotoService;
use Modules\ResumeCreate\Services\ResumePdfBuilder;

class ResumeCreateController extends Controller
{
    public function __construct(
        protected ResumeCreateService $service,
        protected ResumePhotoService $photoService,
        protected ResumePdfBuilder $pdfBuilder,
    ) {
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'module' => 'ResumeCreate',
        ]);
    }

    public function show(): JsonResponse
    {
        $user = $this->resolveUserFromRequest(request());

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $resume = $this->service->getForCurrentUser();

        if (! $resume) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => $this->transformResumeToWizardPayload($resume),
        ]);
    }

    public function store(ResumeWizardRequest $request): JsonResponse
    {
        $user = $this->resolveUserFromRequest($request);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $resume = $this->service->saveForCurrentUser($request->validated());

        return response()->json([
            'data' => [
                'id' => $resume->id,
            ],
        ]);
    }

    public function uploadPhoto(ResumePhotoRequest $request): JsonResponse
    {
        $path = $this->photoService->store($request->file('photo'));

        return response()->json([
            'path' => $path,
        ]);
    }

    public function deletePhoto(Request $request): JsonResponse
    {
        $path = (string) $request->input('path');
        $this->photoService->delete($path);

        return response()->json([
            'status' => 'deleted',
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $lang = $request->query('lang', 'ru');

        $user = $this->resolveUserFromRequest($request);
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $resume = $this->service->getForCurrentUser();

        if (! $resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        return $this->pdfBuilder->download($resume, $lang);
    }

    public function sendPdfToTelegram(Request $request): JsonResponse
    {
        $lang = $request->query('lang', 'ru');

        $user = $this->resolveUserFromRequest($request);
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $user->chat_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telegram chat_id not found for user',
            ], 400);
        }

        $resume = $this->service->getForCurrentUser();
        if (! $resume) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resume not found',
            ], 404);
        }

        $path = $this->pdfBuilder->store($resume, $lang);

        /** @var TelegramBotService $bot */
        $bot = app(TelegramBotService::class);
        $bot->sendResumePdf($user->chat_id, $path, basename($path));

        return response()->json([
            'status' => 'ok',
        ]);
    }

    protected function resolveUserFromRequest(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        if ($user) {
            return $user;
        }

        $token = $request->bearerToken() ?: (string) $request->query('token');

        if ($token !== '') {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
                Auth::setUser($user);

                return $user;
            }
        }

        return null;
    }

    protected function transformResumeToWizardPayload($resume): array
    {
        return [
            'personal' => [
                'first_name' => $resume->first_name,
                'last_name' => $resume->last_name,
                'email' => $resume->contact_email,
                'phone' => $resume->phone,
                'city' => $resume->city,
                'country' => $resume->country,
                'photo_path' => $resume->profile_photo_path,
                'linkedin_url' => $resume->linkedin_url,
                'github_url' => $resume->github_url,
                'portfolio_url' => $resume->portfolio_url,
            ],
            'job' => [
                'desired_position' => $resume->desired_position,
                'desired_salary' => $resume->desired_salary,
                'citizenship' => $resume->citizenship,
                'employment_types' => $resume->employment_types ?? [],
                'work_schedules' => $resume->work_schedules ?? [],
                'ready_to_relocate' => (bool) $resume->ready_to_relocate,
                'ready_for_trips' => (bool) $resume->ready_for_trips,
            ],
            'summary' => [
                'text' => $resume->professional_summary,
            ],
            'experiences' => $resume->experiences
                ->map(function ($exp) {
                    return [
                        'position' => $exp->position,
                        'company' => $exp->company,
                        'location' => $exp->location,
                        'start_date' => optional($exp->start_date)->format('Y-m'),
                        'end_date' => optional($exp->end_date)->format('Y-m'),
                        'is_current' => (bool) $exp->is_current,
                        'description' => $exp->description,
                    ];
                })
                ->values()
                ->all(),
            'educations' => $resume->educations
                ->map(function ($edu) {
                    return [
                        'degree' => $edu->degree,
                        'institution' => $edu->institution,
                        'location' => $edu->location,
                        'start_date' => optional($edu->start_date)->format('Y-m'),
                        'end_date' => optional($edu->end_date)->format('Y-m'),
                        'is_current' => (bool) $edu->is_current,
                        'extra_info' => $edu->extra_info,
                    ];
                })
                ->values()
                ->all(),
            'skills' => $resume->skills
                ->map(function ($skill) {
                    return [
                        'name' => $skill->name,
                        'level' => $skill->level,
                    ];
                })
                ->values()
                ->all(),
            'languages' => $resume->languages ?? [],
            'certificates' => $resume->certificates ?? [],
        ];
    }
}
