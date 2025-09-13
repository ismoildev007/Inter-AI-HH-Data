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
];

