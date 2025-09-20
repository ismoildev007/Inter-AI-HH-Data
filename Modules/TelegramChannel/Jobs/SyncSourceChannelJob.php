<?php

namespace Modules\TelegramChannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\TelegramChannel\Services\RelayService;

class SyncSourceChannelJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $username) {}

    public int $tries = 5;
    // Protect against duplicate dispatches for the same peer within a short window
    public int $uniqueFor = 120; // seconds

    public function uniqueId(): string
    {
        return $this->username;
    }

    public function backoff(): array
    {
        return [5, 20, 60];
    }

    public function handle(RelayService $relay): void
    {
        $delay = $relay->syncOneByUsername($this->username);
        if ($delay > 0) {
            // Delay the job to respect FLOOD_WAIT without blocking the worker
            $this->release($delay + 1);
        }
    }
}
