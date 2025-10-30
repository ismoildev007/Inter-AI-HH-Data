<?php

return [
    'refresh' => [
        // Refresh tokens that expire within this window (in hours)
        'window_hours' => env('HH_REFRESH_WINDOW_HOURS', 6),

        // Optional cron expression to override default hourly schedule.
        // Example: '0 */6 * * *' to run every 6 hours.
        'cron' => env('HH_REFRESH_CRON', null),
    ],
];

