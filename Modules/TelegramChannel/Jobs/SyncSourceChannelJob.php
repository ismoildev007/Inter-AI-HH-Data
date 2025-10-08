<?php

namespace Modules\TelegramChannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\TelegramChannel\Services\RelayService;
use Modules\TelegramChannel\Exceptions\SessionLockBusyException;
use Illuminate\Support\Facades\Log;

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

    public function handle(): void
    {
        /** @var RelayService $relay */
        $relay = app(RelayService::class);
        try {
            $delay = $relay->syncOneByUsername($this->username);
        } catch (SessionLockBusyException $e) {
            $retry = (int) config('telegramchannel_relay.locks.session_retry', 20);
            Log::warning('Telegram relay: session busy, job released', [
                'username' => $this->username,
                'retry' => $retry,
                'error' => $e->getMessage(),
            ]);
            $this->release(max(1, $retry));
            return;
        }
        if ($delay > 0) {
            // Delay the job to respect FLOOD_WAIT without blocking the worker
            $this->release($delay + 1);
        }
    }
}
