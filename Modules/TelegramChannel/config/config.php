<?php

return [
    // Secrets/qimmatli ma'lumotlar ENV'da qoladi
    //'relay_mode' => env('TG_RELAY_MODE', 'forward'),
    //'text_only' => env('TG_TEXT_ONLY', false),
    'name'    => 'TelegramChannel',
    'api_id' => env('TG_API_ID'),
    'api_hash' => env('TG_API_HASH'),
    'session' => env('TG_SESSION_PATH', storage_path('app/telegram/session.madeline')),
    // OpenAI config
    'openai_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
];
