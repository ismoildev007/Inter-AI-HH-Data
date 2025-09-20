<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Http\Resources\User\UserResource;
use Modules\Users\Repositories\AuthRepository;

class AuthController extends Controller
{
    use ApiResponseTrait;
    protected AuthRepository $repo;

    public function __construct(AuthRepository $repo)
    {
        $this->repo = $repo;
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
}
