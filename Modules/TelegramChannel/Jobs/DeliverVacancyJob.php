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

    public int $tries = 3;

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
        $html = view('telegramchannel::templates.vacancy_post', [
            'title' => (string) ($vac->title ?? ''),
            'company' => (string) ($vac->company ?? ''),
            'phones' => $phones,
            'usernames' => $users,
            'description' => (string) ($vac->description ?? ''),
            'source_link' => (string) ($vac->source_message_id ?? ''),
            'apply_url' => (string) ($vac->apply_url ?? ''),
            'plain_username' => ltrim((string) ($vac->source_id ?? ''), '@'),
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
                $sigLock = Cache::lock('tg:sig:' . $sig, 600);
                if (!$sigLock->get()) {
                    // Already being processed elsewhere
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
            Redis::throttle($tKey)
                ->allow($tAllow)
                ->every($tEvery)
                ->block($tBlock)
                ->then(function () use (&$acquired, $tg, $to, $html, $target, &$tMsgId, &$targetLink) {
                    $acquired = true;
                    $resp = $tg->sendMessage($to, $html);
                    $tMsgId = $resp['id'] ?? null;
                    if (!$tMsgId && isset($resp['message']['id'])) {
                        $tMsgId = $resp['message']['id'];
                    }
                    if (is_numeric($tMsgId)) {
                        $plain = $target?->username ? ltrim((string) $target->username, '@') : null;
                        if ($plain) {
                            $targetLink = 'https://t.me/' . $plain . '/' . $tMsgId;
                        }
                    }
                }, function () use (&$acquired) {
                    $acquired = false;
                });

            if (!$acquired) {
                // Could not acquire throttle; release to retry later
                $this->release($tBlock > 0 ? $tBlock : 5);
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
                    $vac->status = Vacancy::STATUS_FAILED;
                    $vac->save();
                } else {
                    $this->release(10);
                }
            }
        } finally {
            optional($sigLock)->release();
        }
    }
}

