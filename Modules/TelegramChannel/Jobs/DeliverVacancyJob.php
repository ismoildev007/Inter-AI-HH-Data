<?php

namespace Modules\TelegramChannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Models\Vacancy;
use App\Models\TelegramChannel;
use Modules\TelegramChannel\Services\Telegram\MadelineClient;

class DeliverVacancyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $vacancyId) {}

    // Increase attempts to survive throttling/locks without quick failing
    public int $tries = 15;

    public function uniqueId(): string
    {
        return 'deliver:vacancy:' . $this->vacancyId;
    }

    public function backoff(): array
    {
        return [5, 20, 60];
    }

    public function handle(MadelineClient $tg): void
    {
        $vac = null;
        try {
            $vac = Vacancy::find($this->vacancyId);
            if (!$vac) {
                return;
            }

            // Skip if already delivered or archived
            if (in_array((string) $vac->status, [Vacancy::STATUS_PUBLISH, Vacancy::STATUS_ARCHIVE], true)) {
                return;
            }

            $target = TelegramChannel::where('is_target', true)->first();
            if (!$target) {
                // No target: mark as failed after final attempt
                if ($this->attempts() >= $this->tries) {
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                }
                return;
            }

            // Render message
            $phones = (array) data_get($vac->contact, 'phones', []);
            $users  = (array) data_get($vac->contact, 'telegram_usernames', []);
            $targetUsername = $target?->username ? '@'.ltrim((string) $target->username, '@') : null;

            // Prepare plain source for Blade:
            // - Public: '@user' -> 'user'
            // - Private: 'cid:...' -> null (Blade will show fallback text while href remains the real link)
            $srcId = (string) ($vac->source_id ?? '');
            $plainSource = null;
            if ($srcId !== '') {
                if (str_starts_with($srcId, '@')) {
                    $plainSource = ltrim($srcId, '@');
                } elseif (str_starts_with($srcId, 'cid:')) {
                    $plainSource = null;
                }
            }

            $html = view('telegramchannel::templates.vacancy_post', [
                'title' => (string) ($vac->title ?? ''),
                'company' => (string) ($vac->company ?? ''),
                'phones' => $phones,
                'usernames' => $users,
                'description' => (string) ($vac->description ?? ''),
                'source_link' => (string) ($vac->source_message_id ?? ''),
                'apply_url' => (string) ($vac->apply_url ?? ''),
                'plain_username' => $plainSource,
                'target_username' => $targetUsername,
            ])->render();

            // Prepare peer
            $to = null;
            if (!empty($target->username)) {
                $to = '@' . ltrim((string) $target->username, '@');
            } elseif (!empty($target->channel_id)) {
                $to = (string) $target->channel_id;
            }
            if (!$to) {
                if ($this->attempts() >= $this->tries) {
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                }
                return;
            }

            // Throttle + locks to avoid double-send
            $sig = (string) ($vac->signature ?? '');
            $sigLock = null;
            try {
                if ($sig !== '') {
                    // Shorter TTL to avoid stale locks blocking for long
                    $sigLock = Cache::lock('tg:sig:' . $sig, 60);
                    if (!$sigLock->get()) {
                        // Another worker is processing same signature; retry shortly
                        $this->release(5);
                        return;
                    }
                }

                $thr = (array) config('telegramchannel_relay.throttle.publish', []);
                $tKey   = (string) ($thr['key'] ?? 'tg:publish');
                $tAllow = (int) ($thr['allow'] ?? 20);
                $tEvery = (int) ($thr['every'] ?? 60);
                $tBlock = (int) ($thr['block'] ?? 5);

                $acquired = false;
                $tMsgId = null;
                $targetLink = null;
                $floodDelay = 0;
                $sendError = null;
                $innerTries = max(1, (int) (config('telegramchannel_relay.throttle.publish.inner_retries', 6)));
                for ($i = 0; $i < $innerTries && !$acquired; $i++) {
                    Redis::throttle($tKey)
                        ->allow($tAllow)
                        ->every($tEvery)
                        ->block($tBlock)
                        ->then(function () use (&$acquired, $tg, $to, $html, $target, &$tMsgId, &$targetLink, &$floodDelay, &$sendError) {
                            $acquired = true;
                            try {
                                $resp = $tg->sendMessage($to, $html);
                                $tMsgId = $resp['id'] ?? null;
                                if (!$tMsgId && isset($resp['message']['id'])) {
                                    $tMsgId = $resp['message']['id'];
                                }
                                if (!$tMsgId && isset($resp['updates']) && is_array($resp['updates'])) {
                                    foreach ($resp['updates'] as $u) {
                                        if (isset($u['message']['id'])) { $tMsgId = $u['message']['id']; break; }
                                    }
                                }
                                if (is_numeric($tMsgId)) {
                                    $plain = $target?->username ? ltrim((string) $target->username, '@') : null;
                                    if ($plain) {
                                        $targetLink = 'https://t.me/' . $plain . '/' . $tMsgId;
                                    } elseif (!empty($target?->channel_id)) {
                                        $cid = (string) $target->channel_id;
                                        $cid = ltrim($cid, '-');
                                        if (str_starts_with($cid, '100')) { $cid = substr($cid, 3); }
                                        $targetLink = 'https://t.me/c/' . $cid . '/' . $tMsgId;
                                    }
                                }
                            } catch (\Throwable $e) {
                                $sendError = $e;
                                $msg = $e->getMessage();
                                if (preg_match('/FLOOD_WAIT_(\d+)/i', (string) $msg, $m)) {
                                    $floodDelay = max($floodDelay, (int) ($m[1] ?? 0));
                                }
                            }
                        }, function () use (&$acquired) {
                            $acquired = false;
                        });
                    if (!$acquired) {
                        // Avoid burning attempts too quickly; short pause before next inner try
                        sleep(max(1, (int) $tBlock));
                    }
                }

                if (!$acquired) {
                    // Could not acquire throttle; release to retry later
                    $this->release($tBlock > 0 ? $tBlock : 5);
                    return;
                }

                // Handle FLOOD_WAIT gracefully: re-schedule with Telegram-advised delay
                if ($floodDelay > 0) {
                    Log::warning('DeliverVacancyJob FLOOD_WAIT', [
                        'vacancy_id' => $vac->id,
                        'delay' => $floodDelay,
                    ]);
                    $this->release($floodDelay + 1);
                    return;
                }

                if (is_numeric($tMsgId)) {
                    // Success: publish vacancy
                    $vac->target_msg_id = $tMsgId;
                    if ($targetLink) {
                        $vac->target_message_id = $targetLink;
                    }
                    $vac->status = Vacancy::STATUS_PUBLISH;
                    $vac->save();
                } else {
                    // No message id parsed -> retry
                    if ($this->attempts() >= $this->tries) {
                        if ($sendError) {
                            Log::warning('DeliverVacancyJob send failed', [
                                'vacancy_id' => $vac->id,
                                'error' => $sendError->getMessage(),
                            ]);
                        }
                        $vac->status = Vacancy::STATUS_FAILED;
                        $vac->save();
                    } else {
                        $this->release(10);
                    }
                }
            } finally {
                optional($sigLock)->release();
            }
        } catch (\Throwable $e) {
            // Catch-all to prevent unhandled exceptions from causing MaxAttemptsExceededException loops
            Log::error('DeliverVacancyJob unhandled exception', [
                'vacancy_id' => $vac?->id,
                'error' => $e->getMessage(),
            ]);
            try {
                if ($vac) {
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                }
            } catch (\Throwable $ie) {}
            // Do not rethrow
        }
    }
}
