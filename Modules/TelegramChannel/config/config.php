<?php

return [
    // Secrets/qimmatli ma'lumotlar ENV'da qoladi
    'relay_mode' => env('TG_RELAY_MODE', 'forward'),
    'text_only' => env('TG_TEXT_ONLY', false),
    'api_id' => env('TG_API_ID'),
    'api_hash' => env('TG_API_HASH'),
    'session' => env('TG_SESSION_PATH', storage_path('app/telegram/session.madeline')),
    'sources' => [
        // 'source_channel_username1', 'source_channel_username2'
    ],
    'target' => env('TG_TARGET_CHANNEL'),

    // TTL (seconds) for auto-archiving vacancies
    'vacancy_ttl_seconds' => 604800,

    // Rate limiters
    'global_rps' => 12,
    'per_chat_rps' => 1,

    // Scan limit per channel per pass
    'scan_limit' => 20,

    // Scan interval for scan-loop daemon (seconds)
    'scan_interval_seconds' => 5,
    // Number of channel shards (per pass scans only 1 shard)
    'scan_shards' => 10,

    // Queue name for sender
    'send_queue' => 'telegram',

    // Orchestrator options
    // How often to restart child processes (seconds). 0 = never.
    'orchestrator_cycle_seconds' => env('TG_ORCHESTRATOR_CYCLE', 86400),
    // Delay (seconds) between starting redis, scanner and worker
    'orchestrator_start_delay' => env('TG_ORCHESTRATOR_DELAY', 2),
    // Optional command to start redis if not running. Example: "redis-server --daemonize yes"
    // Leave empty to auto-detect or skip.
    'redis_start' => env('REDIS_START_CMD', ''),
];
