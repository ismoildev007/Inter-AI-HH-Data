<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
// No file logging
use Symfony\Component\Process\Process;

class TelegramOrchestratorCommand extends Command
{
    protected $signature = 'telegram:orchestrate
        {--queue= : Queue name for queue:work (defaults config telegramchannel.send_queue)}
        {--cycle= : Seconds to restart children (defaults config telegramchannel.orchestrator_cycle_seconds)}
        {--delay= : Seconds between starts (defaults config telegramchannel.orchestrator_start_delay)}
        {--no-redis : Do not attempt to start Redis}
    ';

    protected $description = 'Start Redis (optional), telegram:scan-loop and queue:work, monitor and auto-restart with delays';

    private ?Process $scan = null;
    private ?Process $worker = null;

    public function handle(): int
    {
        $queue = (string) ($this->option('queue') ?? config('telegramchannel.send_queue', 'telegram'));
        $cycle = (int) ($this->option('cycle') ?? (int) config('telegramchannel.orchestrator_cycle_seconds', 86400));
        $delay = (int) ($this->option('delay') ?? (int) config('telegramchannel.orchestrator_start_delay', 2));

        if ($delay < 0) { $delay = 0; }
        if ($cycle < 0) { $cycle = 0; }

        $this->info('Orchestrator starting (queue='.$queue.', cycle='.($cycle ?: 'never').', delay='.$delay.'s)');

        // Try to ensure Redis is running (optional)
        if (!(bool) $this->option('no-redis')) {
            $this->ensureRedis();
            $this->sleepDelay($delay);
        } else {
            $this->line('Skip Redis start (--no-redis)');
        }

        // Start children
        $this->startScanLoop();
        $this->sleepDelay($delay);
        $this->startWorker($queue);

        $startedAt = time();

        // Graceful termination on shutdown
        register_shutdown_function(function () {
            $this->line('Orchestrator shutting down, stopping children...');
            $this->stopChild($this->scan, 'scan-loop');
            $this->stopChild($this->worker, 'queue:work');
        });

        // Main watch loop
        while (true) {
            // Restart if any child died
            if (!$this->isRunning($this->scan)) {
                $this->warn('scan-loop stopped, restarting in '.$delay.'s...');
                $this->sleepDelay($delay);
                $this->startScanLoop();
            }
            if (!$this->isRunning($this->worker)) {
                $this->warn('queue:work stopped, restarting in '.$delay.'s...');
                $this->sleepDelay($delay);
                $this->startWorker($queue);
            }

            // Scheduled cycle restart
            if ($cycle > 0 && (time() - $startedAt) >= $cycle) {
                $this->line('Cycle reached ('.$cycle.'s). Restarting children...');
                $this->stopChild($this->scan, 'scan-loop');
                $this->sleepDelay($delay);
                $this->stopChild($this->worker, 'queue:work');
                $this->sleepDelay($delay);
                $this->startScanLoop();
                $this->sleepDelay($delay);
                $this->startWorker($queue);
                $startedAt = time();
            }

            // Tick delay
            usleep(500000); // 0.5s
        }

        // Unreachable
        // return self::SUCCESS;
    }

    private function startScanLoop(): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $cmd = sprintf('%s %s telegram:scan-loop %s', escapeshellcmd($php), escapeshellarg($artisan), $this->silenceRedirect());
        $this->scan = Process::fromShellCommandline($cmd, base_path());
        $this->scan->setTimeout(null);
        $this->scan->start();
        $this->line('Started scan-loop (PID '.$this->scan->getPid().')');
        // no file logging
    }

    private function startWorker(string $queue): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        // Keep conservative flags; sleep=3, timeout=120; retries come from job's $tries
        $cmd = sprintf('%s %s queue:work --queue=%s --sleep=3 --timeout=120 %s',
            escapeshellcmd($php), escapeshellarg($artisan), escapeshellarg($queue), $this->silenceRedirect()
        );
        $this->worker = Process::fromShellCommandline($cmd, base_path());
        $this->worker->setTimeout(null);
        $this->worker->start();
        $this->line('Started queue:work (PID '.$this->worker->getPid().', queue='.$queue.')');
        // no file logging
    }

    private function stopChild(?Process &$proc, string $name): void
    {
        if (!$proc) { return; }
        try {
            if ($proc->isRunning()) {
                $this->line('Stopping '.$name.' (PID '.$proc->getPid().')');
                // Try graceful stop first
                $term = \defined('SIGTERM') ? \SIGTERM : 15;
                $kill = \defined('SIGKILL') ? \SIGKILL : 9;
                $proc->stop(3, $term);
                // If still running, force kill
                if ($proc->isRunning()) {
                    $proc->stop(0, $kill);
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        } finally {
            $proc = null;
        }
    }

    private function isRunning(?Process $proc): bool
    {
        return $proc !== null && $proc->isRunning();
    }

    private function sleepDelay(int $seconds): void
    {
        if ($seconds > 0) { sleep($seconds); }
    }

    private function ensureRedis(): void
    {
        $pong = $this->runCheck('redis-cli ping');
        if ($pong && str_contains(trim($pong), 'PONG')) {
            $this->line('Redis is running (PONG)');
            return;
        }

        $cmd = (string) config('telegramchannel.redis_start', '');
        if ($cmd === '') {
            // Best-effort autodetect local server command
            $which = $this->runCheck('which redis-server');
            if ($which) { $cmd = 'redis-server --daemonize yes'; }
        }

        if ($cmd !== '') {
            $this->line('Starting Redis: '.$cmd);
            $this->runBackground($cmd, null);
            // Re-check
            sleep(1);
            $pong2 = $this->runCheck('redis-cli ping');
            if ($pong2 && str_contains(trim($pong2), 'PONG')) {
                $this->line('Redis started (PONG)');
                return;
            }
            $this->warn('Redis did not respond to PING after start attempt');
        } else {
            $this->line('Skip Redis start (no command configured)');
        }
    }

    private function runCheck(string $cmd): ?string
    {
        try {
            $p = Process::fromShellCommandline($cmd, base_path());
            $p->setTimeout(10);
            $p->run();
            if ($p->isSuccessful()) return $p->getOutput();
        } catch (\Throwable) {}
        return null;
    }

    private function runBackground(string $cmd, ?string $logFile): void
    {
        // Run and detach: append output to log or silence
        $full = $cmd.' '.($logFile ? ('>> '.escapeshellarg($logFile).' 2>&1') : $this->silenceRedirect());
        $p = Process::fromShellCommandline($full, base_path());
        $p->setTimeout(null);
        $p->start();
    }

    private function silenceRedirect(): string
    {
        $isWindows = stripos(PHP_OS_FAMILY ?? php_uname('s'), 'Windows') === 0;
        $devnull = $isWindows ? 'NUL' : '/dev/null';
        return '>> '.escapeshellarg($devnull).' 2>&1';
    }
}
