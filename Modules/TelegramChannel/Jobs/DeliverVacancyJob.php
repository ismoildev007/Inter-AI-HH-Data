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
use Modules\TelegramChannel\Exceptions\SessionLockBusyException;

class DeliverVacancyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $vacancyId) {}

    // Increase attempts to survive throttling/locks without quick failing
    public int $tries = 15;

    public function uniqueId(): string
    {
        // Bump namespace to bypass stale unique locks from old queue name
        return 'deliver:v2:vacancy:' . $this->vacancyId;
    }

    public function backoff(): array
    {
        return [5, 20, 60];
    }

    public function handle(): void
    {
        $vac = null;
        try {
            $vac = Vacancy::find($this->vacancyId);
            if (!$vac) {
                return;
            }

            // Log::info('DeliverVacancyJob start', [
            //     'vacancy_id' => $vac->id,
            //     'attempt' => $this->attempts(),
            //     'status' => (string) $vac->status,
            // ]);

            // Skip if already delivered or archived
            if (in_array((string) $vac->status, [Vacancy::STATUS_PUBLISH, Vacancy::STATUS_ARCHIVE], true)) {
                return;
            }

            $target = TelegramChannel::where('is_target', true)->first();
            if (!$target) {
                // No target configured yet — retry later instead of silently returning
                Log::warning('DeliverVacancyJob no target channel', ['vacancy_id' => $vac->id, 'attempt' => $this->attempts()]);
                if ($this->attempts() >= $this->tries) {
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                } else {
                    $this->release(30);
                }
                return;
            }

            // Lazily resolve Madeline client to avoid constructor DI failures during session lock
            try {
                /** @var \Modules\TelegramChannel\Services\Telegram\MadelineClient $tg */
                $tg = app(\Modules\TelegramChannel\Services\Telegram\MadelineClient::class);
            } catch (SessionLockBusyException $e) {
                $retry = (int) config('telegramchannel_relay.locks.session_retry', 20);
                Log::warning('DeliverVacancyJob session busy, releasing', [
                    'vacancy_id' => $vac->id,
                    'retry' => $retry,
                    'attempt' => $this->attempts(),
                ]);
                $this->release(max(1, $retry));
                return;
            }

            // Global FLOOD_WAIT cool-down: if set, skip early and re-schedule
            try {
                $coolKey = (string) (config('telegramchannel_relay.throttle.publish.cooldown_key', 'tg:publish:cooldown_until'));
                $now = time();
                $coolUntil = (int) (Cache::get($coolKey, 0));
                if ($coolUntil > $now) {
                    $delay = max(1, $coolUntil - $now);
                    // Log::info('DeliverVacancyJob cool-down active, releasing', [
                    //     'vacancy_id' => $vac->id,
                    //     'delay' => $delay,
                    // ]);
                    $this->release($delay + 1);
                    return;
                }
            } catch (\Throwable $e) {
                // ignore cool-down errors
            }

            // Pre-send dedupe guard: if identical content already queued/published, do not send
            try {
                $sig  = trim((string) ($vac->signature ?? ''));
                $nh   = trim((string) ($vac->normalized_hash ?? ''));
                $rh   = trim((string) ($vac->raw_hash ?? ''));
                $dupWhere = function ($q) use ($sig, $nh, $rh) {
                    $q->where(function ($w) use ($sig, $nh, $rh) {
                        $orAdded = false;
                        if ($sig !== '') { $w->orWhere('signature', $sig); $orAdded = true; }
                        if ($nh  !== '') { $w->orWhere('normalized_hash', $nh); $orAdded = true; }
                        if ($rh  !== '') { $w->orWhere('raw_hash', $rh); $orAdded = true; }
                        if (!$orAdded) { $w->whereRaw('1=0'); }
                    });
                };

                // 1) Already published duplicate? -> archive and stop
                $hasPublishedDup = Vacancy::query()
                    ->where('id', '!=', $vac->id)
                    ->whereIn('status', [Vacancy::STATUS_PUBLISH])
                    ->where($dupWhere)
                    ->exists();
                if ($hasPublishedDup) {
                    $vac->status = Vacancy::STATUS_ARCHIVE;
                    $vac->save();
                   // Log::info('DeliverVacancyJob dedupe: published exists, archived current', ['vacancy_id' => $vac->id]);
                    return;
                }

                // 2) Queued duplicate(s): only the smallest id wins
                $minQueuedDupId = Vacancy::query()
                    ->where('id', '!=', $vac->id)
                    ->whereIn('status', [Vacancy::STATUS_QUEUED])
                    ->where($dupWhere)
                    ->min('id');
                if (is_numeric($minQueuedDupId) && (int)$minQueuedDupId > 0 && (int)$minQueuedDupId < (int)$vac->id) {
                    $vac->status = Vacancy::STATUS_ARCHIVE;
                    $vac->save();
                  //  Log::info('DeliverVacancyJob dedupe: smaller queued exists, archived current', ['vacancy_id' => $vac->id, 'winner_id' => (int)$minQueuedDupId]);
                    return;
                }
            } catch (\Throwable $e) {
                // Dedupe check failure should not break delivery; continue
                Log::warning('DeliverVacancyJob dedupe check error', ['vacancy_id' => $vac->id, 'error' => $e->getMessage()]);
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
                Log::warning('DeliverVacancyJob target peer unresolved', ['vacancy_id' => $vac->id, 'attempt' => $this->attempts()]);
                if ($this->attempts() >= $this->tries) {
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                } else {
                    $this->release(30);
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
                        if ($this->attempts() >= ($this->tries - 1)) {
                            $vac->status = Vacancy::STATUS_FAILED;
                            $vac->save();
                        } else {
                            $this->release(5);
                        }
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
                            // Robustly extract message id from possible structures
                            $tMsgId = $resp['id'] ?? null;
                            if (!$tMsgId && isset($resp['message']['id'])) {
                                $tMsgId = $resp['message']['id'];
                            }
                            if (!$tMsgId && isset($resp['result']['message']['id'])) {
                                $tMsgId = $resp['result']['message']['id'];
                            }
                            if (!$tMsgId && isset($resp['updates']) && is_array($resp['updates'])) {
                                foreach ($resp['updates'] as $u) {
                                    if (isset($u['message']['id'])) { $tMsgId = $u['message']['id']; break; }
                                    if (isset($u['update']['message']['id'])) { $tMsgId = $u['update']['message']['id']; break; }
                                }
                            }
                            $tMsgId = $tMsgId ? (int) $tMsgId : null;
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
                    // Could not acquire throttle; release to retry later or fail near limit
                    if ($this->attempts() >= ($this->tries - 1)) {
                        $vac->status = Vacancy::STATUS_FAILED;
                        $vac->save();
                    } else {
                        $this->release($tBlock > 0 ? $tBlock : 5);
                    }
                    return;
                }

                // Handle FLOOD_WAIT gracefully: set global cool-down and re-schedule
                if ($floodDelay > 0) {
                    Log::warning('DeliverVacancyJob FLOOD_WAIT', [
                        'vacancy_id' => $vac->id,
                        'delay' => $floodDelay,
                    ]);
                    try {
                        $coolKey = (string) (config('telegramchannel_relay.throttle.publish.cooldown_key', 'tg:publish:cooldown_until'));
                        $until = time() + (int) $floodDelay;
                        Cache::put($coolKey, $until, max(1, (int) $floodDelay + 2));
                    } catch (\Throwable $ie) {}
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
