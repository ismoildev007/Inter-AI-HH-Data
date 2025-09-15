<?php

namespace Modules\TelegramChannel\Console\Commands;

use App\Models\TelegramChannel;
use Illuminate\Console\Command;
use Modules\TelegramChannel\Jobs\ScanChannel;

class TelegramScanDispatchCommand extends Command
{
    protected $signature = 'telegram:scan-dispatch {--limit=0 : Max channels to dispatch}';
    protected $description = 'Dispatch scan jobs for all source channels';

    public function handle(): int
    {
        $q = TelegramChannel::query()->where('is_source', true)->orderBy('id');
        $limit = (int) $this->option('limit');
        if ($limit > 0) $q->limit($limit);
        $channels = $q->get();

        foreach ($channels as $ch) {
            ScanChannel::dispatch($ch->id)->onQueue('telegram');
        }

        $this->info('Dispatched scans for '.$channels->count().' channels.');
        return self::SUCCESS;
    }
}

