<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Modules\TelegramChannel\Jobs\SyncSourceChannelJob;
use App\Models\TelegramChannel;

class RelayRunCommand extends Command
{
    protected $signature = 'relay:run {--once}';
    protected $description = 'Dispatch sync jobs for configured channels';

    public function handle(): int
    {
        $rules = (array) config('telegramchannel_relay.rules', []);
        $usernames = array_keys($rules);

        // DB'dan is_source kanallarni ham qo'shamiz (istisnosiz relaysiz)
        $sources = TelegramChannel::query()
            ->where('is_source', true)
            ->get(['username', 'channel_id'])
            ->map(function ($row) {
                if (!empty($row->username)) {
                    $u = ltrim((string) $row->username, '@');
                    return '@'.$u; // peers sifatida '@username' ishlatamiz
                }
                return (string) $row->channel_id;
            })
            ->all();

        $peers = array_values(array_unique(array_merge($usernames, $sources)));

        if (empty($peers)) {
            $this->warn('No source channels found (rules or DB).');
            return self::SUCCESS;
        }

        do {
            foreach ($peers as $peer) {
                SyncSourceChannelJob::dispatch($peer);
                $this->info('Dispatched sync job for '.$peer);
            }

            if ($this->option('once')) {
                break;
            }

            // Daemon rejimi: 30 soniyada yana job tashlaymiz
            sleep(30);
        } while (true);

        return self::SUCCESS;
    }
}
