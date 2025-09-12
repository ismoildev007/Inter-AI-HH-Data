<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Http\Resources\User\UserResource;
use Modules\Users\Repositories\AuthRepository;

class AuthController extends Controller
{
    protected AuthRepository $repo;

    public function __construct(AuthRepository $repo)
    {
        $this->repo = $repo;
    }

    public function register(Request $request)
    {
        $result = $this->repo->register($request->all());

        return response()->json($result, 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->repo->login($request->validated());

        if (!$result) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        return response()->json($result);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load([
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
