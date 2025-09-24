<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Http\Resources\User\UserResource;
use Modules\Users\Http\Resources\User\UserSettingResource;
use Modules\Users\Repositories\AuthRepository;
use Modules\Users\Services\AutoApplySettingsService;

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

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $user->load([
            'role',
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

        if (isset($validated['auto_apply_limit'])) {
            $balance = optional($user->credit)->balance ?? 0;
            if ($validated['auto_apply_limit'] > $balance) {
                return $this->error(
                    "You only have {$balance} credits, so you cannot set auto apply limit higher than that.",
                    422
                );
            }
        }

        $setting = $this->autoApplySettingsService->update($user, $validated);

        return $this->success(new UserSettingResource($setting), 'Updated', 200);
    }


    public function balance(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $balance = UserCredit::where('user_id', $user->id)->first();
        $credit = UserSetting::where('user_id', $user->id)->first();

        return response()->json([
            'status' => true,
            'balance' => $balance->balance,
            'credit' => [
                'limit' => $credit->auto_apply_limit,
                'count' => $credit->auto_apply_count ?? 0
            ]
        ]);
    }
}
