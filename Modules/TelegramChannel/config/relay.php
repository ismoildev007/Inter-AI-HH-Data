<?php

return [
    'rules' => [
        '@AaaaElnurbek' => [
            'type' => 'starts_with',
            'pattern' => 'salom',
            'case_insensitive' => true,
        ],
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
        '@AaaaElnurbek' => [
            'replace' => [
                [
                    'type' => 'regex',
                    'pattern' => '/^👉\s*@AaaaElnurbek.*$/mi',
                    'to' => '👉 {target_username} kanaliga ulanish',
                ],
            ],
        ],
    ],
    'fetch' => [
        'batch_limit' => 100,
        'sleep_sec'   => 2,
    ],
];
