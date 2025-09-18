<?php

namespace Modules\TelegramChannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\TelegramChannel\Services\RelayService;

class SyncSourceChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $username) {}

    public int $tries = 5;

    public function backoff(): array
    {
        return [5, 20, 60];
    }

    public function handle(RelayService $relay): void
    {
        $relay->syncOneByUsername($this->username);
    }
}