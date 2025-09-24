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

    // AI provider settings
    'ai' => [
        'provider' => 'openai',
        'model' => env('INTERVIEWS_AI_MODEL', 'gpt-4.1-nano'),
        'timeout' => env('INTERVIEWS_AI_TIMEOUT', 20),
        'retries' => env('INTERVIEWS_AI_RETRIES', 2),
        'language' => env('INTERVIEWS_AI_LANGUAGE', 'auto'),
    ],
];
