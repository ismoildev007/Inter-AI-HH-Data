<?php

namespace Modules\Users\Console\Commands;

use App\Models\HhAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Users\Repositories\HhAccountRepositoryInterface;

class RefreshHhTokensCommand extends Command
{
    protected $signature = 'hh:refresh-tokens
        {--window=6 : Hours ahead to refresh before expiry}
        {--batch=100 : Chunk size per iteration}
        {--user-id= : Only refresh for this user id}
        {--dry-run : Do not refresh, only report}';

    protected $description = 'Refresh HH access tokens that are expired or expiring soon.';

    public function handle(): int
    {
        $windowHours = (int) $this->option('window');
        $batch = (int) $this->option('batch');
        $filterUserId = $this->option('user-id') ? (int) $this->option('user-id') : null;
        $dryRun = (bool) $this->option('dry-run');

        /** @var HhAccountRepositoryInterface $repo */
        $repo = app(HhAccountRepositoryInterface::class);

        $threshold = now()->addHours(max(0, $windowHours));

        $query = HhAccount::query()
            ->whereNotNull('refresh_token')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '<', $threshold);
            })
            ->orderBy('id');

        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }

        $this->info(sprintf(
            'Refreshing HH tokens expiring before %s (window=%dh), batch=%d%s',
            $threshold->toDateTimeString(),
            $windowHours,
            $batch,
            $dryRun ? ', dry-run' : ''
        ));

        $count = 0;
        $refreshed = 0;
        $failed = 0;

        $query->chunkById(max(10, $batch), function ($accounts) use (&$count, &$refreshed, &$failed, $repo, $dryRun) {
            foreach ($accounts as $account) {
                $count++;
                $this->line(sprintf(
                    ' - user_id=%s account_id=%s expires_at=%s',
                    $account->user_id,
                    $account->id,
                    optional($account->expires_at)->toDateTimeString() ?? 'null'
                ));

                if ($dryRun) {
                    continue;
                }

                try {
                    $repo->refreshToken($account);
                    $refreshed++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('HH token refresh failed', [
                        'account_id' => $account->id,
                        'user_id' => $account->user_id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn(sprintf('   refresh failed: %s', $e->getMessage()));
                }
            }
        });

        $this->info(sprintf('Accounts scanned: %d, refreshed: %d, failed: %d', $count, $refreshed, $failed));
        return self::SUCCESS;
    }
}

