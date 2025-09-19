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
        ],
        // Normalizatsiyadan so‘ng title shu qiymatlardan biri bo‘lsa — SKIP
        'title_blacklist' => [
            'ish joyi kerak',
            'xodim kerak',
            'vakansiya',
            'резюме',
            'ищу работу',
            'resume',
            'cv',
        ],
    ],
    'dedupe' => [
        // Your strict policy: never republish duplicates
        'republish_archived' => false,
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
        '@UstozShogird' => [
            'replace' => [
                [
                    'type' => 'regex',
                    // Pastda imzo qatordagi UstozShogird manzilini {target_username} bilan almashtirish
                    'pattern' => '/^👉\s*@UstozShogird.*$/mi',
                    'to' => '👉 {target_username} kanaliga ulanish',
                ],
            ],
        ],
        '@UstozShogirdSohalar' => [
            'replace' => [
                [
                    'type' => 'regex',
                    'pattern' => '/^👉\s*@UstozShogirdSohalar.*$/mi',
                    'to' => '👉 {target_username} kanaliga ulanish',
                ],
            ],
        ],
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
        'batch_limit' => 100,
        'sleep_sec'   => 2,
        // Short-running mode: how many while-loop cycles per run
        // For scheduler-based execution, keep this 1 to minimize memory/time
        'max_loops_per_run' => 1,
    ],
];
