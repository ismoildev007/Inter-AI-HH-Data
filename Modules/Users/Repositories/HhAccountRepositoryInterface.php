<?php

namespace Modules\Users\Repositories;

use App\Models\HhAccount;

interface HhAccountRepositoryInterface
{
    /**
     * Create an authorization URL and persist PKCE state in cache.
     *
     * @param int|null $userId Optional current user id (can be null for now)
     * @param string|null $redirectUri Override redirect URI
     * @param array<int,string> $scopes Scopes to request
     * @return array{url:string,state:string,redirect_uri:string}
     */
    public function createAuthorizeUrl(?int $userId, ?string $redirectUri = null, array $scopes = []): array;

    /**
     * Handle OAuth callback: exchange code, store tokens, return account.
     */
    public function handleCallback(string $code, string $state): HhAccount;

    /**
     * Attach an existing HH account to a user.
     */
    public function attachToUser(int $accountId, int $userId): HhAccount;

    /**
     * Find current user's HH account, if any.
     */
    public function findForUser(int $userId): ?HhAccount;
}

