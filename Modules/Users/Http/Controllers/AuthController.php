<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Resumes\Http\Resources\ResumeResource;
use Modules\Users\Http\Requests\ChatIdLoginRequest;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Http\Resources\User\UserResource;
use Modules\Users\Http\Resources\User\UserSettingResource;
use Modules\Users\Repositories\AuthRepository;
use Modules\Users\Services\AutoApplySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait;
    protected AuthRepository $repo;
    protected AutoApplySettingsService $autoApplySettingsService;

    public function __construct(AuthRepository $repo, AutoApplySettingsService $autoApplySettingsService)
    {
        $this->repo = $repo;
        $this->autoApplySettingsService = $autoApplySettingsService;
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->repo->register($request->all());
        if (isset($result['error']) && $result['error']) {
            return $this->error($result['message'], $result['status']);
        }

        return response()->json($result, 201);
    }
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $result = $this->repo->update($user, $request->all());

        if (isset($result['status']) && $result['status'] === 'error') {
            return $this->error($result['message'], $result['code']);
        }

        return response()->json($result, 200);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->repo->login($request->validated());
        if (!$result) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result
        ], 200);
    }
    // public function chatIdLogin(ChatIdLoginRequest $request)
    // {
    //     $result = $this->repo->chatIdLogin($request->validated());

    //     if (!$result) {
    //         return response()->json([
    //             'message' => 'Chat ID noto‘g‘ri yoki foydalanuvchi topilmadi.'
    //         ], 401);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => $result
    //     ], 200);
    // }
    public function chatIdLogin(Request $request) {
        Log::info('Chat ID login request', ['request' => $request->all()]);
        $chatId = $request->input('chat_id');

        $user = User::where('chat_id', $chatId)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Chat ID noto‘g‘ri yoki foydalanuvchi topilmadi.'
            ], 401);
        }

        $token = $user->tokens()->latest()->first()?->plainTextToken ?? $user->createToken('api_token', ['*'], now()->addYears(22))->plainTextToken;
        Log::info('Chat ID login successful', ['user_id' => $user->id, 'token' => $token]);
        return response()->json([
            'status' => 'success',
            'data'   => [
                'token' => $token,
            ]
        ], 200);
    }

    public function checkToken(Request $request) 
    {
        Log::info('Check token request', ['token' => $request->bearerToken()]);
        $user = $request->user();
        return response()->json(['valid' => (bool)$user]);
    }

    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $user->load([
            'resumes',
//            'role',
            'settings',
            'credit',
            'preferences.industry',
            'locations.area',
            'jobTypes',
            'profileViews.employer',
        ]);

        return new UserResource($user);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function getAutoApply(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $setting = $user->settings()->first();
        if (!$setting) {
            return $this->error('Setting not found', 404);
        }

        return $this->success(new UserSettingResource($setting));
    }

    public function incrementAutoApply(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $setting = $this->autoApplySettingsService->incrementCount($user);

        return $this->success(new UserSettingResource($setting), 'Count incremented');
    }

    // Create auto-apply settings for the authenticated user
    public function createAutoApply(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $validated = $request->validate([
            'auto_apply_enabled' => 'required|boolean',
            'auto_apply_limit'   => 'required|integer|min:0',
        ]);

        $setting = $this->autoApplySettingsService->create($user, $validated);

        return $this->success(new UserSettingResource($setting), 'Created', 201);
    }

    // Update auto-apply settings for the authenticated user
    public function updateAutoApply(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $validated = $request->validate([
            'auto_apply_enabled' => 'sometimes|boolean',
            'auto_apply_limit'   => 'sometimes|integer|min:0',
        ]);

        if (empty($validated)) {
            return $this->error('No fields to update', 422);
        }

        $setting = $this->autoApplySettingsService->update($user, $validated);

        if (is_array($setting) && isset($setting['error'])) {
            return $this->error($setting['message'], 422);
        }

        return $this->success(new UserSettingResource($setting), 'Updated', 200);
    }



    public function balance(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $balance = UserCredit::where('user_id', $user->id)->first();
        $credit  = UserSetting::where('user_id', $user->id)->first();

        $autoApplyLimit = $credit->auto_apply_limit ?? 0;
        $autoApplyCount = $credit->auto_apply_count ?? 0;

        $remaining = min(
            $balance->balance ?? 0,
            max(0, $autoApplyLimit - $autoApplyCount)
        );


        return response()->json([
            'status'  => true,
            'balance' => $balance->balance ?? 0,
            'hh_status' => $user->hhAccount ? true : false,
            'credit'  => [
                'limit'     => $autoApplyLimit,
                'count'     => $autoApplyCount,
                'remaining' => $remaining
            ]
        ]);
    }

    public function requestVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        return response()->json(
            $this->repo->requestVerificationCode($request->email)
        );
    }



    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|digits:6',
        ]);

        $cachedCode = cache()->get("verify_code:{$request->email}");

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid or expired verification code'
            ], 422);
        }

        cache()->forget("verify_code:{$request->email}");

        return response()->json([
            'status'  => true,
            'message' => 'Email verified successfully, you can now complete registration'
        ]);
    }

    public function userVerify(Request $request)
    {
        $request->validate([
//            'email' => 'required|email',
            'phone' => 'required',
        ]);

        $exists = \App\Models\User::where('phone', $request->phone)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Bunday foydalanuvchi allaqachon mavjud.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mavjud emas, davom etishingiz mumkin.'
        ], 200);
    }
    public function resumeCheck(Request $request)
    {
        $user = \App\Models\User::where('chat_id', $request->chat_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User topilmadi.'
            ], 404);
        }

        $resume = Resume::where('user_id', $user->id)->first();

        if ($resume) {
            return response()->json([
                'success' => true,
                'message' => 'Resume mavjud.',
                'data'    => new ResumeResource($resume)
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Mavjud emas, davom etishingiz mumkin.'
        ], 200);
    }

    public function coverLetter()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }
        $coverLetter = $user->preference()->first();

        return response()->json([
            'cover_letter' => $coverLetter?->cover_letter ?? null,
        ]);
    }

    public function updateCoverLetter(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $request->validate([
            'cover_letter' => 'required|string',
        ]);

        $preference = $user->preference()->first();
        if (!$preference) {
            $preference = $user->preference()->create([
                'cover_letter' => $request->cover_letter,
            ]);
        } else {
            $preference->cover_letter = $request->cover_letter;
            $preference->save();
        }

        return response()->json([
            'message' => 'Cover letter updated successfully',
            'cover_letter' => $preference->cover_letter,
        ]);
    }

}
