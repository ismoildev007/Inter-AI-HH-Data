<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Users\Http\Resources\HhAccountResource;
use Modules\Users\Repositories\HhAccountRepositoryInterface;

class HhAccountsController extends Controller
{
    public function __construct(private readonly HhAccountRepositoryInterface $repo)
    {
    }

    public function authorizeUrl(Request $request)
    {
        $validated = $request->validate([
            'redirect_uri' => ['nullable', 'url'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string'],
        ]);

        $userId = Auth::id(); // can be null for now
        $result = $this->repo->createAuthorizeUrl($userId, $validated['redirect_uri'] ?? null, $validated['scopes'] ?? []);
        return response()->json($result);
    }

    public function callback(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        $account = $this->repo->handleCallback($validated['code'], $validated['state']);
        return new HhAccountResource($account);
    }

    public function attach(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            throw ValidationException::withMessages(['user' => 'Authentication required']);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
        ]);

        $account = $this->repo->attachToUser((int) $validated['account_id'], (int) $userId);
        return new HhAccountResource($account);
    }

    public function me()
    {
        $userId = Auth::id();
        if (!$userId) {
            throw ValidationException::withMessages(['user' => 'Authentication required']);
        }

        $account = $this->repo->findForUser((int) $userId);
        if (!$account) {
            return response()->json(['data' => null]);
        }
        return new HhAccountResource($account);
    }

    public function disconnect()
    {
        $userId = Auth::id();
        if (!$userId) {
            throw ValidationException::withMessages(['user' => 'Authentication required']);
        }

        $account = $this->repo->findForUser((int) $userId);
        if ($account) {
            $account->delete();
        }
        return response()->noContent();
    }
}

