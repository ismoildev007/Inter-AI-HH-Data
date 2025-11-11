<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Modules\TelegramChannel\Jobs\SyncSourceChannelJob;
use App\Models\TelegramChannel;

// class RelayRunCommand extends Command
// {
//     protected $signature = 'relay:run {--once}';
//     protected $description = 'Dispatch sync jobs for configured channels';

//     public function handle(): int
//     {
//         do {
//             // Har siklda yangidan o'qib, yangi qo'shilgan source kanallarni darrov olamiz
//             $rules = (array) config('telegramchannel_relay.rules', []);
//             $usernames = array_keys($rules);

//             $sources = TelegramChannel::query()
//                 ->where('is_source', true)
//                 ->get(['username', 'channel_id'])
//                 ->map(function ($row) {
//                     if (!empty($row->username)) {
//                         $u = ltrim((string) $row->username, '@');
//                         return '@'.$u;
//                     }
//                     return (string) $row->channel_id;
//                 })
//                 ->all();

//             $peers = array_values(array_unique(array_merge($usernames, $sources)));

//             if (empty($peers)) {
//                 $this->warn('No source channels found (rules or DB).');
//                 if ($this->option('once')) break;
//                 sleep(30);
//                 continue;
//             }

//             foreach ($peers as $peer) {
//                 SyncSourceChannelJob::dispatch($peer);
//                 $this->info('Dispatched sync job for '.$peer);
//             }

//             if ($this->option('once')) {
//                 break;
//             }

//             sleep(30);
//         } while (true);

//         return self::SUCCESS;
//     }
// }
class RelayRunCommand extends Command
{
    protected $signature = 'relay:run {--once}';
    protected $description = 'Dispatch sync jobs for configured channels';

    public function handle(): int
    {
        $rules = (array) config('telegramchannel_relay.rules', []);
        $rulePeers = array_keys($rules);

        // Collect DB sources as peers (prefer @username, fallback to channel_id string)
        $dbPeers = TelegramChannel::query()
            ->where('is_source', true)
            ->get(['username', 'channel_id'])
            ->map(function ($row) {
                if (!empty($row->username)) {
                    return '@'.ltrim((string) $row->username, '@');
                }
                return (string) $row->channel_id;
            })
            ->all();

        $peers = array_values(array_unique(array_merge($rulePeers, $dbPeers)));
        if (empty($peers)) {
            $this->warn('No source channels found (rules or DB).');
            return self::SUCCESS;
        }

        // Round-robin chunked dispatch to avoid huge backlogs
        $cfg = (array) config('telegramchannel_relay.dispatch', []);
        $chunkSize = max(1, (int) ($cfg['chunk_size'] ?? 25));
        $offsetKey = (string) ($cfg['offset_cache_key'] ?? 'tg:relay:offset');

        $count = count($peers);
        $offset = (int) (\Cache::get($offsetKey, 0));
        if ($offset >= $count || $offset < 0) { $offset = 0; }

        $slice = [];
        if ($offset + $chunkSize <= $count) {
            $slice = array_slice($peers, $offset, $chunkSize);
        } else {
            $slice = array_slice($peers, $offset);
            $remain = $chunkSize - count($slice);
            if ($remain > 0) {
                $slice = array_merge($slice, array_slice($peers, 0, $remain));
            }
        }

        foreach ($slice as $peer) {
            SyncSourceChannelJob::dispatch($peer)->onQueue('telegram-sync');
        }
        $this->info(sprintf(
            'Dispatched sync chunk: count=%d offset=%d total=%d',
            count($slice), $offset, $count
        ));

        $next = ($offset + $chunkSize) % $count;
        \Cache::forever($offsetKey, $next);

        return self::SUCCESS;
    }
}
