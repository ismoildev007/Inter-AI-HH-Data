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
        do {
            // Har siklda yangidan o'qib, yangi qo'shilgan source kanallarni darrov olamiz
            $rules = (array) config('telegramchannel_relay.rules', []);
            $usernames = array_keys($rules);

            $sources = TelegramChannel::query()
                ->where('is_source', true)
                ->get(['username', 'channel_id'])
                ->map(function ($row) {
                    if (!empty($row->username)) {
                        $u = ltrim((string) $row->username, '@');
                        return '@'.$u;
                    }
                    return (string) $row->channel_id;
                })
                ->all();

            $peers = array_values(array_unique(array_merge($usernames, $sources)));

            if (empty($peers)) {
                $this->warn('No source channels found (rules or DB).');
                if ($this->option('once')) break;
                sleep(30);
                continue;
            }

            foreach ($peers as $peer) {
                SyncSourceChannelJob::dispatch($peer);
                $this->info('Dispatched sync job for '.$peer);
            }

            if ($this->option('once')) {
                break;
            }

            sleep(30);
        } while (true);

        return self::SUCCESS;
    }
}
