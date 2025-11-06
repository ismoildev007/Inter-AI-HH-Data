<?php

return [
    'name' => 'Interviews',

    // Which HH statuses should trigger interview generation
    'trigger_statuses' => [
        'interview',
    ],

    // Number of questions to generate
    'max_questions' => 20,

    // Only run for this vacancy source
    'source_filter' => 'hh',

    // HH statuses that mean the application was declined/discarded
    'discard_statuses' => [
        'discard',
    ],

    // If exact statuses differ per account/locale, these substrings
    // will be checked case-insensitively to detect discard-like states.
    'discard_patterns' => [
        'discard', // en
        'reject',  // en: rejected
        'declin',  // en: decline/declined
        'отказ',   // ru: отказ
    ],

    // AI provider settings
    'ai' => [
        'provider' => 'openai',
        'model' => env('INTERVIEWS_AI_MODEL', 'gpt-4.1-nano'),
        'timeout' => env('INTERVIEWS_AI_TIMEOUT', 20),
        'retries' => env('INTERVIEWS_AI_RETRIES', 2),
        'language' => env('INTERVIEWS_AI_LANGUAGE', 'auto'),
    ],
];
