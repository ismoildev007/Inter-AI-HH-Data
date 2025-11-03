<?php

namespace Modules\TelegramChannel\Services;

use Modules\TelegramChannel\Actions\ExtractTextFromMessage;
use Modules\TelegramChannel\Actions\ChannelRuleMatcher;
use Modules\TelegramChannel\Actions\TransformMessageText;
use App\Models\Vacancy;
use Modules\TelegramChannel\Services\Telegram\MadelineClient;
use App\Models\TelegramChannel; // Sizning Controller shuni ishlatyapti
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Modules\TelegramChannel\Support\ContentFingerprint;

class RelayService
{
    public function __construct(
        private MadelineClient $tg,
        private ExtractTextFromMessage $extract,
        private \Modules\TelegramChannel\Actions\ExtractApplyUrlFromMessage $extractApply,
        private ChannelRuleMatcher $matcher,
        private TransformMessageText $transform,
        private VacancyClassificationService $classifier,
        private VacancyNormalizationService $normalizer,
        private VacancyCategoryService $categorizer,
    ) {}

    /**
     * Increment per-minute OpenAI call metrics for relay (cls|norm).
     * Uses Cache::add to set TTL on first write, then increment.
     */
    private function incOaiMetric(string $op): void
    {
        try {
            $ttl = (int) config('telegramchannel_relay.metrics.ttl_sec', 7200);
            $bucket = date('YmdHi'); // per-minute bucket
            $key = 'oai:relay:' . $op . ':' . $bucket;
            // ensure key exists with TTL, then increment
            \Cache::add($key, 0, $ttl > 0 ? $ttl : 7200);
            \Cache::increment($key);
        } catch (\Throwable $e) {
            // metrics are best-effort; ignore failures
        }
    }

    public function syncOneByUsername(string $peer): int
    {
        // DB da shu kanal bor deb faraz qilamiz (username yoki channel_id orqali)
        if (preg_match('/^-?\d+$/', (string) $peer)) {
            $channel = TelegramChannel::where('channel_id', (string) $peer)->first();
            $ruleKey = (string) $peer; // Rul topilmasa default allow
        } else {
            $channel = TelegramChannel::where('username', ltrim((string) $peer, '@'))->first();
            $ruleKey = (string) $peer; // matcher ichida '@' bilan/bo'lmasdan tekshiriladi
        }
        $target  = TelegramChannel::where('is_target', true)->first();

        // Agar yo'q bo'lsa — hech bo'lmasa "stateless" qilib o'qish
        $lastId  = (int) ($channel?->last_message_id ?? 0);

        // Birinchi ishga tushirishda eski postlarni OLMASLIK: hozirgi eng so'nggi id ni anchor qilib qo'yamiz
        if ($lastId <= 0) {
            try {
                $latest = $this->tg->getHistory($peer, 0, 1);
            } catch (\Throwable $e) {
                // Handle known transient / invalid peer cases
                $delay = $this->parseFloodWait($e->getMessage());
                if ($delay > 0) {
                    Log::warning('Telegram relay: FLOOD_WAIT on initial getHistory', ['peer' => $peer, 'delay' => $delay]);
                    return $delay;
                }
                if ($this->isChannelInvalid($e)) {
                    Log::warning('Telegram relay: CHANNEL_INVALID on initial getHistory, disabling source', ['peer' => $peer]);
                    if ($channel) {
                        $channel->is_source = false;
                        $channel->save();
                    }
                    return 0;
                }
                $etype = $this->classifyError($e);
                switch ($etype) {
                    case 'SIGTERM':
                    case 'SIGINT':
                        Log::info('Telegram relay: getHistory interrupted by signal', ['peer' => $peer, 'phase' => 'initial', 'signal' => $etype]);
                        return 0;
                    case 'PEER_DB_MISS':
                        Log::warning('Telegram relay: PEER_DB_MISS on initial getHistory', ['peer' => $peer, 'error' => $e->getMessage()]);
                        return 0;
                    case 'NETWORK':
                        Log::warning('Telegram relay: NETWORK_ERROR on initial getHistory', ['peer' => $peer, 'error' => $e->getMessage()]);
                        return 0;
                    default:
                        Log::warning('Telegram relay: getHistory failed (initial)', ['peer' => $peer, 'error' => $e->getMessage()]);
                        return 0;
                }
            }
            $messages = $latest['messages'] ?? [];
            $latestId = 0;
            foreach ($messages as $m) {
                $id = (int) ($m['id'] ?? 0);
                if ($id > $latestId) $latestId = $id;
            }
            if ($channel && $latestId > 0) {
                $channel->last_message_id = $latestId;
                $channel->save();
            }
            // Eski postlarni relay qilmaymiz
            return 0;
        }

        $limit = (int) config('telegramchannel_relay.fetch.batch_limit', 100);
        $sleep = (int) config('telegramchannel_relay.fetch.sleep_sec', 2);

        $maxLoops = (int) config('telegramchannel_relay.fetch.max_loops_per_run', 1);
        $loops = 0;
        $floodWait = 0;
        $stopLoop = false;
        while (true) {
            try {
                $hist = $this->tg->getHistory($peer, $lastId, $limit);
            } catch (\Throwable $e) {
                $delay = $this->parseFloodWait($e->getMessage());
                if ($delay > 0) {
                    Log::warning('Telegram relay: FLOOD_WAIT on getHistory', ['peer' => $peer, 'delay' => $delay]);
                    $floodWait = max($floodWait, $delay);
                    break; // stop loop; job will be re-queued with delay
                }
                if ($this->isChannelInvalid($e)) {
                    Log::warning('Telegram relay: CHANNEL_INVALID on getHistory, disabling source', ['peer' => $peer]);
                    if ($channel) {
                        $channel->is_source = false;
                        $channel->save();
                    }
                    break;
                }
                $etype = $this->classifyError($e);
                switch ($etype) {
                    case 'SIGTERM':
                    case 'SIGINT':
                        Log::info('Telegram relay: getHistory interrupted by signal', ['peer' => $peer, 'phase' => 'loop', 'signal' => $etype]);
                        break;
                    case 'CANCELLED':
                        Log::warning('Telegram relay: OPERATION_CANCELLED on getHistory', ['peer' => $peer]);
                        $this->maybeAutoHeal('cancelled');
                        break;
                    case 'PEER_DB_MISS':
                        Log::warning('Telegram relay: PEER_DB_MISS on getHistory', ['peer' => $peer, 'error' => $e->getMessage()]);
                        $this->maybeAutoHeal('peer_db');
                        break;
                    case 'NETWORK':
                        Log::warning('Telegram relay: NETWORK_ERROR on getHistory', ['peer' => $peer, 'error' => $e->getMessage()]);
                        break;
                    default:
                        Log::warning('Telegram relay: getHistory failed', ['peer' => $peer, 'error' => $e->getMessage()]);
                        break;
                }
                break;
            }
            $messages = $hist['messages'] ?? [];
            // Drop large unused sections ASAP to reduce peak memory
            if (isset($hist['users']) || isset($hist['chats']) || isset($hist['updates']) || isset($hist['users_nearby'])) {
                unset($hist['users'], $hist['chats'], $hist['updates'], $hist['users_nearby']);
            }
            if (empty($messages)) {
                if ((bool) config('telegramchannel_relay.debug.log_empty_peers', true)) {
                    Log::debug('Relay loop EMPTY', [
                        'peer' => $peer,
                        'loop' => $loops,
                        'messages' => 0,
                    ]);
                }
                break;
            }

            // Optional memory diagnostics per loop
            if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
                $usage = round(memory_get_usage(true) / 1048576, 1);
                $peak  = round(memory_get_peak_usage(true) / 1048576, 1);
                Log::debug('Relay loop memory', [
                    'peer' => $peer,
                    'loop' => $loops,
                    'messages' => count($messages),
                    'usage_mb' => $usage,
                    'peak_mb'  => $peak,
                ]);
            }

            // gpt apiga sorov jonatiladi 
            $maxId = $lastId;
            // Track highest successfully SENT message id (for optional retry policy)
            $maxDeliveredId = $lastId;
            $seenIds = [];
            // Optional per-run GPT call cap (classification + normalization)
            $gptCap = (int) config('telegramchannel_relay.limits.max_gpt_calls_per_run', 0); // 0 = unlimited
            $gptCalls = 0;

            foreach ($messages as $m) {
                // If throttled/FLOOD_WAIT detected during this batch, stop processing further messages
                if ($stopLoop || $floodWait > 0) {
                    break;
                }
                $id = (int) ($m['id'] ?? 0);
                if ($id <= 0) continue;
                if (isset($seenIds[$id])) {
                    continue; // duplicate id within the same batch
                }
                $seenIds[$id] = true;
                if ($id > $maxId) $maxId = $id;

                $text = $this->extract->handle($m);
                if ($text === null) continue;

                $rawHash = ContentFingerprint::raw($text);
                $dedupeConfig = (array) config('telegramchannel_relay.dedupe', []);
                $allowArchivedDuplicates = (bool) ($dedupeConfig['allow_multiple_archived'] ?? true);
                $skipIfRawPublished = (bool) ($dedupeConfig['skip_if_raw_hash_published'] ?? true);
                $skipSignaturePublished = (bool) ($dedupeConfig['skip_if_signature_published'] ?? ($dedupeConfig['skip_if_published'] ?? true));
                $normalizedHash = null;
                if ($rawHash !== '') {
                    if ($skipIfRawPublished) {
                        $rawPublished = Vacancy::where('raw_hash', $rawHash)
                            ->where('status', Vacancy::STATUS_PUBLISH)
                            ->exists();
                        if ($rawPublished) {
                            continue;
                        }
                    }
                    if (!$allowArchivedDuplicates) {
                        $rawExists = Vacancy::where('raw_hash', $rawHash)->exists();
                        if ($rawExists) {
                            continue;
                        }
                    }
                }

                // Extract external apply/career URL if present
                $applyUrl = $this->extractApply->handle($m);

                // Pre-filter (optional) by channel rules
                if ((bool) config('telegramchannel_relay.filtering.use_channel_rules', true)) {
                    if (!$this->matcher->matches($ruleKey, $text)) {
                        continue;
                    }
                }

                // Quick skip by banned phrases (job seeker banners like "Ish joyi kerak", etc.)
                $banned = (array) config('telegramchannel_relay.filtering.banned_phrases', []);
                if (!empty($banned)) {
                    $lower = mb_strtolower($text);
                    foreach ($banned as $phrase) {
                        $p = mb_strtolower((string) $phrase);
                        if ($p !== '' && preg_match('/^\s*' . preg_quote($p, '/') . '\b/u', $lower)) {
                            continue 2; // skip this message
                        }
                    }
                }

                // Build source link and quick dedupe by (source_id, source_message_id)
                // Support both public (@username) and private (-100...) sources
                $plainSource   = null; // username without @ if available
                $channelIdRaw  = null; // string like -100xxxxxxxxxxxx if available
                if (preg_match('/^-?\d+$/', (string) $peer)) {
                    // numeric peer: prefer DB channel username if present
                    $plainSource  = $channel?->username ? ltrim((string) $channel->username, '@') : null;
                    $channelIdRaw = (string) ($channel?->channel_id ?: $peer);
                } else {
                    // username peer
                    $plainSource  = ltrim((string) $peer, '@');
                    $channelIdRaw = (string) ($channel->channel_id ?? '');
                }

                $sourceId  = null;
                $sourceLink= null;
                if ($plainSource) {
                    // Public channel: use @username link
                    $sourceId   = '@' . $plainSource;
                    $sourceLink = 'https://t.me/' . $plainSource . '/' . $id;
                } elseif ($channelIdRaw !== '' && preg_match('/^-?\d+$/', (string) $channelIdRaw)) {
                    // Private channel: use t.me/c/<internalId>/<id>
                    $sourceId = 'cid:' . (string) $channelIdRaw;
                    $cid = ltrim((string) $channelIdRaw, '-');
                    if (str_starts_with($cid, '100')) { $cid = substr($cid, 3); }
                    $sourceLink = 'https://t.me/c/' . $cid . '/' . $id;
                } else {
                    Log::warning('Skipping message: cannot build source link', ['peer' => $peer, 'message_id' => $id]);
                    continue;
                }
                $existsByLink = Vacancy::where('source_id', $sourceId)
                    ->where('source_message_id', $sourceLink)
                    ->exists();
                if ($existsByLink) {
                    continue; // strict no-duplicate policy
                }

                // Note: lock for dedupe will be acquired right before sending

                // Classify content to ensure it's an employer vacancy (with cache + error cache + in-flight lock)
                $cls = null;
                $clsUsedCache = false;
                $clsCacheKey = null;
                $clsErrKey = null;
                $clsCfg = (array) config('telegramchannel_relay.cache.classification', []);
                $clsCacheEnabled = (bool) ($clsCfg['enabled'] ?? true);
                $clsTtlSec = (int) ($clsCfg['ttl_sec'] ?? 172800);
                $errCfg = (array) config('telegramchannel_relay.cache.error', []);
                $errTtlSec = (int) ($errCfg['ttl_sec'] ?? 7200);

                $model = (string) config('telegramchannel.openai_model', env('OPENAI_MODEL', 'gpt-4.1-nano'));
                if ($rawHash !== '') {
                    $clsCacheKey = 'tg:cls:v1:' . $rawHash . ':' . $model;
                    $clsErrKey   = 'tg:oai:err:cls:v1:' . $rawHash . ':' . $model;
                }

                // Skip if recent error cached
                if ($clsErrKey && Cache::has($clsErrKey)) {
                    continue;
                }

                // Try cache
                if ($clsCacheEnabled && $clsCacheKey) {
                    try {
                        $c = Cache::get($clsCacheKey);
                        if (is_array($c) && isset($c['label'])) {
                            $cls = $c;
                            $clsUsedCache = true;
                        }
                    } catch (\Throwable $e) {
                        // ignore cache read failure
                    }
                }

                // Not cached — call classifier (respect per-run GPT cap) with in-flight lock per rawHash+model
                if (!$cls) {
                    if ($gptCap > 0 && $gptCalls >= $gptCap) {
                        $stopLoop = true;
                        break;
                    }
                    $clsLock = null;
                    $acq = false;
                    try {
                        if ($rawHash !== '') {
                            $lkey = 'tg:cls:lock:v1:' . $rawHash . ':' . $model;
                            $clsLock = Cache::lock($lkey, 120);
                            try {
                                $clsLock->block(1);
                                $acq = true;
                            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                                // Another worker is classifying the same content; skip this message
                                continue;
                            }
                        }
                        // Re-check cache after acquiring lock: another worker might have finished meanwhile
                        if ($clsCacheEnabled && $clsCacheKey) {
                            $c2 = Cache::get($clsCacheKey);
                            if (is_array($c2) && isset($c2['label'])) {
                                $cls = $c2;
                                $clsUsedCache = true;
                            }
                        }
                        if (!$cls) {
                            // metrics: count classification API calls per minute bucket
                            $this->incOaiMetric('cls');
                            $cls = $this->classifier->classify($text);
                            $gptCalls++;
                            if ($clsCacheEnabled && $clsCacheKey) {
                                try { Cache::put($clsCacheKey, $cls, $clsTtlSec); } catch (\Throwable $e) {}
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Classification error', ['err' => $e->getMessage()]);
                        // error-result cache to avoid repeated failures for the same content
                        if ($clsErrKey) { try { Cache::put($clsErrKey, 1, $errTtlSec); } catch (\Throwable $ie) {} }
                        continue; // skip on error; keep loop running
                    } finally {
                        if ($acq && $clsLock) { optional($clsLock)->release(); }
                    }
                }
                $threshold = (float) config('telegramchannel_relay.filtering.classification_threshold', 0.8);
                if (($cls['label'] ?? '') !== 'employer_vacancy' || (float) ($cls['confidence'] ?? 0) < $threshold) {
                    continue;
                }

                // Normalize via OpenAI with caching to avoid re-prompting on retries
                $normalized = null;
                $category = null;
                $usedCache = false;
                $cacheCfg = (array) config('telegramchannel_relay.cache.normalization', []);
                $cacheEnabled = (bool) ($cacheCfg['enabled'] ?? true);
                $ttlSec = (int) ($cacheCfg['ttl_sec'] ?? 86400);
                $normCacheKey = ($cacheEnabled && $rawHash !== '') ? ('tg:norm:v1:' . $rawHash) : null;
                if ($normCacheKey) {
                    try {
                        $cached = Cache::get($normCacheKey);
                        if (is_array($cached) && isset($cached['normalized']) && is_array($cached['normalized'])) {
                            $normalized = $cached['normalized'];
                            if (isset($cached['category']) && is_string($cached['category'])) {
                                $category = $cached['category'];
                            }
                            $usedCache = true;
                        }
                    } catch (\Throwable $e) {
                        // Cache read failure should not break the flow
                    }
                }
                if (!$normalized) {
                    // Skip if recent normalization error cached
                    $normErrKey = $normCacheKey ? ('tg:oai:err:norm:v1:' . $rawHash . ':' . $model) : null;
                    if ($normErrKey && Cache::has($normErrKey)) {
                        continue;
                    }
                    if ($gptCap > 0 && $gptCalls >= $gptCap) {
                        $stopLoop = true;
                        break;
                    }
                    $normLock = null;
                    $acqN = false;
                    try {
                        if ($rawHash !== '' && $model !== '') {
                            $lkeyN = 'tg:norm:lock:v1:' . $rawHash . ':' . $model;
                            $normLock = Cache::lock($lkeyN, 180);
                            try {
                                $normLock->block(1);
                                $acqN = true;
                            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                                // Another worker is normalizing same content; skip and let cache be used next run
                                continue;
                            }
                        }
                        // Re-check normalization cache after acquiring lock
                        if ($normCacheKey) {
                            $cached2 = Cache::get($normCacheKey);
                            if (is_array($cached2) && isset($cached2['normalized']) && is_array($cached2['normalized'])) {
                                $normalized = $cached2['normalized'];
                                if (isset($cached2['category']) && is_string($cached2['category'])) {
                                    $category = $cached2['category'];
                                }
                                $usedCache = true;
                            }
                        }
                        if (!$normalized) {
                            // metrics: count normalization API calls per minute bucket
                            $this->incOaiMetric('norm');
                            $normalized = $this->normalizer->normalize($text, '@'.$plainSource, $id);
                            $gptCalls++;
                        }
                    } catch (\Throwable $e) {
                        Log::error('Normalization error', ['err' => $e->getMessage()]);
                        if ($normErrKey) { try { Cache::put($normErrKey, 1, $errTtlSec); } catch (\Throwable $ie) {} }
                        continue; // skip on error
                    } finally {
                        if ($acqN && $normLock) { optional($normLock)->release(); }
                    }
                }

                $normalizedHash = ContentFingerprint::normalized($normalized);
                if ($normalizedHash !== '') {
                    $skipNormalizedPublished = (bool) ($dedupeConfig['skip_if_normalized_hash_published'] ?? true);
                    if ($skipNormalizedPublished) {
                        $existsNormalizedPublished = Vacancy::where('normalized_hash', $normalizedHash)
                            ->where('status', Vacancy::STATUS_PUBLISH)
                            ->exists();
                        if ($existsNormalizedPublished) {
                            continue;
                        }
                    }
                    if (!$allowArchivedDuplicates) {
                        $existsNormalizedAny = Vacancy::where('normalized_hash', $normalizedHash)->exists();
                        if ($existsNormalizedAny) {
                            continue;
                        }
                    }
                } else {
                    $normalizedHash = null;
                }

                // Post-normalization guard: if title is blacklisted (generic slogans) — skip
                $sanTitle = trim((string) ($normalized['title'] ?? ''));
                $sanTitle = trim($sanTitle, " :\t\r\n");
                $titleLower = mb_strtolower($sanTitle);
                $titleBlacklist = (array) config('telegramchannel_relay.filtering.title_blacklist', []);
                foreach ($titleBlacklist as $tb) {
                    if ($titleLower === mb_strtolower((string) $tb)) {
                        continue 2;
                    }
                }

                // Require contact: at least one phone or telegram username must be present
                $requireContact = (bool) config('telegramchannel_relay.filtering.require_contact', false);
                $phones = (array) ($normalized['contact']['phones'] ?? []);
                $users  = (array) ($normalized['contact']['telegram_usernames'] ?? []);
                if ($requireContact && empty($phones) && empty($users)) {
                    continue; // skip if no contacts provided
                }

                // Compute signature and dedupe by status policy
                $signature = \Modules\TelegramChannel\Support\Signature::fromNormalized($normalized);
                if ($signature !== '') {
                    if ($skipSignaturePublished) {
                        $existsPublished = Vacancy::where('signature', $signature)
                            ->where('status', Vacancy::STATUS_PUBLISH)
                            ->exists();
                        if ($existsPublished) {
                            continue;
                        }
                    }
                    if (!$allowArchivedDuplicates) {
                        $existsAny = Vacancy::where('signature', $signature)->exists();
                        if ($existsAny) {
                            continue;
                        }
                    }
                }


                // Determine category: trust AI output strictly against allowed labels; no heuristic fallback
                if (!is_string($category) || $category === '') {
                    $aiCategory = (string) ($normalized['category'] ?? '');
                    $category = null;
                    try {
                        // Include 'Other' in allowed labels (use canonical list)
                        $allowedAssoc = $this->categorizer->getCanonicalCategories(); // slug => label
                        $allowed = array_values($allowedAssoc);
                        foreach ($allowed as $label) {
                            if (mb_strtolower($label, 'UTF-8') === mb_strtolower($aiCategory, 'UTF-8')) {
                                $category = $label;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        // If allowed list not available, keep null
                        $category = $aiCategory !== '' ? $aiCategory : null;
                    }
                }

                // Store normalization+category to cache for retry reuse
                if (!$usedCache && $normCacheKey) {
                    try {
                        Cache::put($normCacheKey, [
                            'normalized' => $normalized,
                            'category' => $category,
                        ], $ttlSec);
                    } catch (\Throwable $e) {
                        // Cache write failure is non-fatal
                    }
                }

                // Render post in your house style (Blade)
                // $phones and $users already prepared above
                $targetUsername = $target?->username ? '@'.ltrim((string) $target->username, '@') : null;
                $html = view('telegramchannel::templates.vacancy_post', [
                    'title' => $normalized['title'] ?? '',
                    'company' => $normalized['company'] ?? '',
                    'phones' => $phones,
                    'usernames' => $users,
                    'description' => $normalized['description'] ?? '',
                    'source_link' => $sourceLink,
                    'apply_url' => $applyUrl,
                    'plain_username' => $plainSource,
                    'target_username' => $targetUsername,
                ])->render();

                // Optional final transform layer (regex replacements)
                $out = $this->transform->handle($ruleKey, $html, $target);

                // Send to target
                $targetLink = null;
                if ($target) {
                    // Prefer @username for peer; fallback to numeric channel_id
                    $to = null;
                    if (!empty($target->username)) {
                        $to = '@' . ltrim((string) $target->username, '@');
                    } elseif (!empty($target->channel_id)) {
                        $to = (string) $target->channel_id;
                    }
                    if ($to) {
                        // Acquire distributed lock to ensure only one send/save per message
                        $lockParts = [$sourceId, $sourceLink];
                        if (!empty($rawHash)) {
                            $lockParts[] = $rawHash;
                        }
                        if (!empty($normalizedHash)) {
                            $lockParts[] = $normalizedHash;
                        }
                        if (!empty($signature)) {
                            $lockParts[] = $signature;
                        }
                        $lockKey = 'tg:msg:' . sha1(implode('|', $lockParts));
                        $lock = Cache::lock($lockKey, 600);
                        if (!$lock->get()) {
                            if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
                                Log::debug('Dedup: message lock busy, skipping send', ['peer' => $peer, 'id' => $id, 'lock' => $lockKey]);
                            }
                            // Another worker is processing the same message
                            continue;
                        }
                        try {
                            // Additionally, protect by signature lock to avoid double-send across sources
                            $sigLock = null;
                            if ($signature !== '') {
                                $sigLock = Cache::lock('tg:sig:'. $signature, 600);
                                if (!$sigLock->get()) {
                                    if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
                                        Log::debug('Dedup: signature lock busy, skipping send', ['peer' => $peer, 'id' => $id, 'sig' => $signature]);
                                    }
                                    continue; // another worker is handling this signature
                                }
                            }
                            $thr = (array) config('telegramchannel_relay.throttle.publish', []);
                            $tKey   = (string) ($thr['key'] ?? 'tg:publish');
                            $tAllow = (int) ($thr['allow'] ?? 20);
                            $tEvery = (int) ($thr['every'] ?? 60);
                            $tBlock = (int) ($thr['block'] ?? 5);

                            $acquired = false;
                            // Initialize here so it's defined even if send fails
                            $tMsgId = $tMsgId ?? null;
                            $sent = false;
                            Redis::throttle($tKey)
                                ->allow($tAllow)
                                ->every($tEvery)
                                ->block($tBlock)
                                ->then(function () use (&$acquired, $to, $out, $target, &$targetLink, &$tMsgId, $peer, $id, &$floodWait, &$stopLoop, &$sent) {
                                $acquired = true;
                                // Try to send; handle FLOOD_WAIT and other errors gracefully
                                try {
                                $resp = $this->tg->sendMessage($to, $out);
                                // Extract message id robustly
                                $tMsgId = $resp['id'] ?? null;
                                if (!$tMsgId && isset($resp['message']['id'])) {
                                    $tMsgId = $resp['message']['id'];
                                }
                                if (!$tMsgId && isset($resp['updates']) && is_array($resp['updates'])) {
                                    foreach ($resp['updates'] as $u) {
                                        if (isset($u['message']['id'])) { $tMsgId = $u['message']['id']; break; }
                                        if (isset($u['update']['message']['id'])) { $tMsgId = $u['update']['message']['id']; break; }
                                    }
                                }
                                $tMsgId = $tMsgId ? (int) $tMsgId : null;
                                // Build link by username or /c/ internal id
                                $tUser = $target->username ? ltrim((string) $target->username, '@') : null;
                                if ($tUser && $tMsgId) {
                                    $targetLink = 'https://t.me/' . $tUser . '/' . $tMsgId;
                                } elseif ($tMsgId && !empty($target->channel_id)) {
                                    $cid = (string) $target->channel_id;
                                    $cid = ltrim($cid, '-');
                                    if (str_starts_with($cid, '100')) { $cid = substr($cid, 3); }
                                    $targetLink = 'https://t.me/c/' . $cid . '/' . $tMsgId;
                                }
                                // Mark send as successful
                                $sent = true;
                                } catch (\Throwable $e) {
                                $delay = $this->parseFloodWait($e->getMessage());
                                if ($delay > 0) {
                                    // Avoid capturing outer $peer inside limiter closure to prevent scope issues
                                    Log::warning('Telegram relay: FLOOD_WAIT on sendMessage', ['delay' => $delay]);
                                    $floodWait = max($floodWait, $delay);
                                    $stopLoop = true;
                                } else {
                                    Log::warning('Telegram relay: sendMessage failed', [
                                        'error' => $e->getMessage(),
                                        'to' => $to,
                                        // do not reference $peer inside this closure
                                        'message_id' => $id,
                                    ]);
                                }
                                // On send failure, skip saving
                                return;
                                }
                            }, function () use (&$acquired) {
                                $acquired = false;
                            });

                            if (!$acquired) {
                                // Could not acquire throttle; stop this run, resume next tick
                                $stopLoop = true;
                                continue;
                            }

                            // Save only if send was successful
                            if ($sent) {
                                try {
                                    Vacancy::create([
                                        'source' => 'telegram',
                                        'title' => $normalized['title'] ?? null,
                                        'company' => $normalized['company'] ?? null,
                                        'category' => $category ?: null,
                                        'contact' => [
                                            'phones' => $phones,
                                            'telegram_usernames' => $users,
                                        ],
                                        'description' => $normalized['description'] ?? null,
                                        'language' => $normalized['language'] ?? null,
                                        'status' => Vacancy::STATUS_PUBLISH,
                                        'apply_url' => $applyUrl,
                                        'source_id' => $sourceId,
                                        'source_message_id' => $sourceLink,
                                        'target_message_id' => $targetLink,
                                        'target_msg_id' => $tMsgId,
                                        'signature' => $signature,
                                        'raw_hash' => $rawHash ?: null,
                                        'normalized_hash' => $normalizedHash ?: null,
                                    ]);
                                } catch (\Throwable $e) {
                                    Log::error('Failed to save Vacancy', ['err' => $e->getMessage()]);
                                }
                                // Mark delivery progress for optional retry policy
                                if ($id > $maxDeliveredId) { $maxDeliveredId = $id; }
                            } else {
                                if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
                                    Log::debug('Skip save: send not successful', ['peer' => $ruleKey ?? null, 'id' => $id ?? null]);
                                }
                            }
                        } finally {
                            optional($lock)->release();
                            if (isset($sigLock)) optional($sigLock)->release();
                        }
                    }
                }

            }

            // Oxirgi ko'rilgan id ni saqlash siyosati:
            // Agar reprocess_on_send_failure yoqilgan bo'lsa, faqat muvaffaqiyatli yuborilgan eng katta id gacha suramiz.
            // Aks holda avvalgidek $maxId gacha suramiz.
            $advanceOnFailure = !(bool) config('telegramchannel_relay.fetch.reprocess_on_send_failure', false);
            $newLastId = $advanceOnFailure ? $maxId : $maxDeliveredId;
            if ($channel && $newLastId > $lastId) {
                $channel->last_message_id = $newLastId;
                $channel->save();
            }
            $lastId = $newLastId;
            $loops++;
            if ($loops >= $maxLoops || $stopLoop || $floodWait > 0) {
                // Per-run cleanup to reduce retained memory in long workers
                unset($hist, $messages);
                gc_collect_cycles();
                break;
            }
            // Inter-loop cleanup and pacing
            unset($hist, $messages);
            gc_collect_cycles();
            sleep($sleep);
        }
        return $floodWait;
    }

    private function parseFloodWait(?string $message): int
    {
        if (!$message) return 0;
        if (preg_match('/FLOOD_WAIT_(\d+)/', $message, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    private function isChannelInvalid(\Throwable $e): bool
    {
        $m = $e->getMessage();
        if (!$m) return false;
        foreach (['CHANNEL_INVALID', 'USERNAME_INVALID', 'CHANNEL_PRIVATE'] as $needle) {
            if (str_contains($m, $needle)) return true;
        }
        return false;
    }

    private function classifyError(\Throwable $e): string
    {
        $m = (string) $e->getMessage();
        $u = strtoupper($m);
        if ($m === '') return 'UNKNOWN';
        if (str_contains($u, 'SIGTERM')) return 'SIGTERM';
        if (str_contains($u, 'SIGINT'))  return 'SIGINT';
        if (str_contains($u, 'OPERATION WAS CANCELLED') || str_contains($u, 'CANCELLED')) return 'CANCELLED';
        if (str_contains($u, 'INTERNAL PEER DATABASE')) return 'PEER_DB_MISS';
        // crude network hints
        foreach (['TIMEOUT', 'TIMED OUT', 'ECONNRESET', 'FAILED TO CONNECT', 'SSL', 'TLS', 'CONNECTION RESET', 'NETWORK'] as $needle) {
            if (str_contains($u, $needle)) return 'NETWORK';
        }
        return 'UNKNOWN';
    }

    private function maybeAutoHeal(string $type): void
    {
        // Throttle: if too many errors of a kind in a short period, soft reset client once
        $key = 'tg:heal:cnt:' . $type;
        $cool = 'tg:heal:cooldown';
        if (\Cache::has($cool)) {
            return; // in cooldown period
        }
        $cnt = (int) (\Cache::increment($key) ?: 0);
        if ($cnt === 1) {
            \Cache::put($key, 1, 120); // 2 minutes TTL
        }
        $threshold = (int) config('telegramchannel_relay.maintenance.auto_heal_threshold', 12);
        if ($cnt >= $threshold) {
            try {
                $this->tg->softReset();
                \Log::info('Telegram relay: auto-heal soft reset executed', ['type' => $type, 'count' => $cnt]);
            } catch (\Throwable $e) {
                \Log::warning('Telegram relay: auto-heal soft reset failed', ['error' => $e->getMessage()]);
            } finally {
                \Cache::put($cool, 1, 300); // 5 minutes cooldown
                \Cache::forget($key);
            }
        }
    }
}
