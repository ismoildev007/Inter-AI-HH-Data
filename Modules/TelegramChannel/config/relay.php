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
    'fetch' => [
        'batch_limit' => 100,
        'sleep_sec'   => 2,
    ],
];
