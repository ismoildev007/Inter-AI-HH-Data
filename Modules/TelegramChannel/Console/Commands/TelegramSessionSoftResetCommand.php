<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Modules\TelegramChannel\Services\Telegram\MadelineClient;

class TelegramSessionSoftResetCommand extends Command
{
    protected $signature = 'telegram:session:soft-reset';
    protected $description = 'Soft reset the MadelineProto client without deleting session files';

    public function handle(MadelineClient $tg): int
    {
        try {
            $tg->softReset();
            $this->info('MadelineProto client soft reset completed.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Soft reset failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}

