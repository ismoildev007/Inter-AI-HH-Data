<?php

return [
    'relay_mode' => env('TG_RELAY_MODE', 'forward'),
    'text_only' => env('TG_TEXT_ONLY', true),
    'api_id' => env('TG_API_ID'),
    'api_hash' => env('TG_API_HASH'),
    'session' => env('TG_SESSION_PATH', storage_path('app/telegram/session.madeline')),
    'sources' => [
        // 'source_channel_username1', 'source_channel_username2'
    ],
    'target' => env('TG_TARGET_CHANNEL'),

    // TTL (seconds) for auto-archiving vacancies (default: 1 week = 604800)
    'vacancy_ttl_seconds' => env('TG_VACANCY_TTL_SECONDS', 604800),

    // Rate limiters
    'global_rps' => env('TG_GLOBAL_RPS', 15),
    'per_chat_rps' => env('TG_PER_CHAT_RPS', 1),

    // Scan limit per channel per pass
    'scan_limit' => env('TG_SCAN_LIMIT', 20),

    // Scan interval for scan-loop daemon (seconds)
    'scan_interval_seconds' => env('TG_SCAN_INTERVAL_SECONDS', 15),

    // Queue name for sender
    'send_queue' => env('TG_SEND_QUEUE', 'telegram'),
];
