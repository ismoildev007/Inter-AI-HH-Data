<?php

return [
    // Caching for GPT operations used by RelayService
    'cache' => [
        'classification' => [
            'enabled' => true,
            // Cache classification result (label, confidence, language)
            'ttl_sec' => env('TG_RELAY_CLASSIFICATION_TTL', 172800), // 2 days
        ],
        'normalization' => [
            'enabled' => true,
            'ttl_sec' => env('TG_RELAY_NORMALIZATION_TTL', 86400), // 1 day
        ],
        // Cache to avoid repeated failures on the same content
        'error' => [
            'enabled' => true,
            'ttl_sec' => env('TG_RELAY_ERROR_TTL', 7200), // 2 hours
        ],
    ],

    // OpenAI token limits (RelayService-related services only)
    'openai' => [
        // Classification: short JSON, keep small
        'classification_max_tokens' => 9000,
        'classification_hard_cap'   => 10000,

        // Normalization: richer JSON; base + hard cap
        'normalization_max_tokens' => 9000,
        'normalization_hard_cap'   => 10000,
    ],
    // Filtering and dedupe policies
    'filtering' => [
        'use_channel_rules' => true,
        'classification_threshold' => 0.8,
        'require_contact' => true,
        // Agar matn quyidagi iboralar bilan boshlansa — darhol SKIP (job seeker/rezyume postlari)
        // Case-insensitive, boshlanishiga mos kelish sinovi qilinadi
        'banned_phrases' => [
            'ish joyi kerak',
            'ish qidiryapman',
            'ish qidirmoqdaman',
            'ищу работу',
            'резюме',
            'ищу вакансию',
            'resume',
            'cv',
            'afitsant',
            'ofitsiant',
            'ofitsantka',
            'ofisiant',
            'sexy',
            'go\'zal',
            'gozal',
            'go\'zallik',
            'tikuvchi',
            'tikuvchilik',
            'sexi',
            'tikuv',
            'seh',
            'quruvchi',
            'quruvchilik',
            'quruvchilar',
            'quruvchisi',
            'quruvchiga',
            'quruvchidan',
            'quruvchimiz',
            'quruvchilarni',
            'quruvchilar uchun'
        ],
        // Normalizatsiyadan so‘ng title shu qiymatlardan biri bo‘lsa — SKIP
        'title_blacklist' => [
            'ish joyi kerak',
            //'xodim kerak',
            'vakansiya',
            'резюме',
            'ищу работу',
            'resume',
            'cv',
            'afitsant',
            'ofitsiant',
            'ofitsantka',
            'ofisiant',
            'sexy',
            'go\'zal',
            'gozal',
            'go\'zallik',
            'tikuvchi',
            'tikuvchilik',
            'sexi',
            'tikuv',
            'seh',
            'quruvchi',
            'quruvchilik',
            'quruvchilar',
            'quruvchisi',
            'quruvchiga',
            'quruvchidan',
            'quruvchimiz',
            'quruvchilarni',
            'quruvchilar uchun'
        ],
    ],
    'dedupe' => [
        // Skip if there is already a PUBLISHED record with the same signature
        'skip_if_published' => true, // legacy flag
        'skip_if_signature_published' => true,
        'skip_if_raw_hash_published' => true,
        'skip_if_normalized_hash_published' => true,
        // Allow multiple ARCHIVED rows with the same signature (requires dropping unique on signature)
        'allow_multiple_archived' => true,
        // Footer handles to strip when hashing raw or normalized content
        'footer_handles' => [
            '@UstozShogird',
            '@UstozShogirdSohalar',
        ],
        // Auto-archive after N days (affects PUBLISHED rows)
        'auto_archive_days' => 60,
    ],
    'locks' => [
        'session_ttl' => env('TG_SESSION_LOCK_TTL', 120),
        'session_wait' => env('TG_SESSION_LOCK_WAIT', 45),
        'session_retry' => env('TG_SESSION_LOCK_RETRY', 20),
    ],
    'rules' => [
        // '@AaaaElnurbek' => [
        //     'type' => 'starts_with',
        //     'pattern' => '',
        //     'case_insensitive' => true,
        // ],
        '@UstozShogird' => [
            // Faqat "Xodim kerak" bilan boshlanadigan e'lonlar
            'type' => 'starts_with',
            'pattern' => 'Xodim kerak',
            'case_insensitive' => true,
        ],
        '@UstozShogirdSohalar' => [
            // Faqat "Xodim kerak" bilan boshlanadigan e'lonlar
            'type' => 'starts_with',
            'pattern' => 'Xodim kerak',
            'case_insensitive' => true,
        ],
    ],
    // Kanalga xos matn transformatsiyalari: faqat ko'rsatilgan kanallarga ta'sir qiladi
    // Hozir: UstozShogird va UstozShogirdSohalar
    // Operation: replace[type=regex], "to" satrida {target_username} ni ishlatish mumkin
    'transforms' => [
        // '@UstozShogird' => [
        //     'replace' => [
        //         [
        //             'type' => 'regex',
        //             // Pastda imzo qatordagi UstozShogird manzilini {target_username} bilan almashtirish
        //             'pattern' => '/^👉\s*@UstozShogird.*$/mi',
        //             'to' => '👉 {target_username} kanaliga ulanish',
        //         ],
        //     ],
        // ],
        // '@UstozShogirdSohalar' => [
        //     'replace' => [
        //         [
        //             'type' => 'regex',
        //             'pattern' => '/^👉\s*@UstozShogirdSohalar.*$/mi',
        //             'to' => '👉 {target_username} kanaliga ulanish',
        //         ],
        //     ],
        // ],
        // '@AaaaElnurbek' => [
        //     'replace' => [
        //         [
        //             'type' => 'regex',
        //             'pattern' => '/^👉\s*@AaaaElnurbek.*$/mi',
        //             'to' => '👉 {target_username} kanaliga ulanish',
        //         ],
        //     ],
        // ],
    ],
    'fetch' => [
        'batch_limit' => 50,
        'sleep_sec'   => 2,
        // Short-running mode: how many while-loop cycles per run
        // For scheduler-based execution, keep this 1 to minimize memory/time
        'max_loops_per_run' => 1,
        // If true, do NOT advance last_message_id past the last successfully sent message.
        // This makes failed sends reprocessed on the next run (safe retry), at the cost of potential reprocessing.
        'reprocess_on_send_failure' => true,
    ],
    // Additional safety limits and counters (RelayService only)
    'limits' => [
        // Max number of GPT calls (classification + normalization) per run. 0 = unlimited.
        'max_gpt_calls_per_run' => env('TG_RELAY_MAX_GPT_PER_RUN', 0),
    ],
    'metrics' => [
        // TTL for per-minute OpenAI counters
        'ttl_sec' => env('TG_RELAY_METRICS_TTL', 7200),
        // Detailed per-call token usage logging (prompt/completion/total)
        'log_usage' => true,
    ],
    // Dispatch policy: round-robin per minute to smooth load
    'dispatch' => [
        // Nechta source har daqiqada ishga tushsin (round-robin)
        // 500 source va 50 chunk => ~10 daqiqada to'liq aylanma
        'chunk_size' => 25,
        'offset_cache_key' => 'tg:relay:offset',
    ],
    'debug' => [
        // Enable to log memory usage per fetch loop
        'log_memory' => true,
        // Also log peers with zero new messages
        'log_empty_peers' => true,
    ],
    'maintenance' => [
        // How many similar errors within ~2 minutes should trigger a soft reset
        'auto_heal_threshold' => env('TG_AUTO_HEAL_THRESHOLD', 12),
    ],
    // Global publish throttle to reduce FLOOD_WAIT
    'throttle' => [
        'publish' => [
            'key' => 'tg:publish',
            // ruxsat etilgan yuborishlar soni / 60s (ENV talab qilinmaydi)
            // Kerak bo'lsa shu faylda sonni o'zgartiring
            'allow' => 30, // per minute (config-driven)
            'every' => 60, // seconds
            'block' => 5,  // acquire up to N seconds per inner attempt
            // DeliverVacancyJob ichida bir attempt davomida nechta ichki acquire qilish (config-driven)
            'inner_retries' => 6,
        ],
    ],
];
