<?php

return [
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
        'skip_if_published' => true,
        // Allow multiple ARCHIVED rows with the same signature (requires dropping unique on signature)
        'allow_multiple_archived' => true,
        // Auto-archive after N days (affects PUBLISHED rows)
        'auto_archive_days' => 7,
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
    // Global publish throttle to reduce FLOOD_WAIT
    'throttle' => [
        'publish' => [
            'key' => 'tg:publish',
            // ruxsat etilgan yuborishlar soni / davr
            'allow' => env('TG_PUBLISH_PER_MIN', 10), // per minute
            'every' => 60, // seconds
            'block' => 5,  // acquire up to N seconds, then skip current loop
        ],
    ],
];
