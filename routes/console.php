<?php

// ---------------------
// Qo‘lda, interaktiv:
// - telegram:login → Telegramga login (telefon/QR). Scheduler/Queue talab qilmaydi.
// ---------------------

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//----------------------------

Schedule::command('hh:sync-negotiations')
    ->everyFiveMinutes()
    ->withoutOverlapping();
// Workers/queues:
// - queue: default (jobs: Modules\Interviews\Jobs\HandleInterviewApplication → GenerateInterviewQuestionsJob)
// - Horizon/queue:work default kerak

//----------------------------

Schedule::command('autoapply:start')
    ->everyMinute()
    ->withoutOverlapping();
// Workers/queues:
// - queue: autoapply (job: App\Jobs\AutoApplyJob)
// - Horizon/queue:work --queue=autoapply kerak

//----------------------------

// TelegramChannel schedules (migrated from module provider)
if (Artisan::has('relay:run')) {
    Schedule::command('relay:run --once')
        ->everyMinute()
        ->withoutOverlapping();
    // Workers/queues:
    // - queue: telegram-relay (job: Modules\TelegramChannel\Jobs\SyncSourceChannelJob)
    // - Horizon/queue:work --queue=telegram-relay kerak
}

//----------------------------

if (Artisan::has('telegram:vacancies:auto-archive')) {
    Schedule::command('telegram:vacancies:auto-archive')
        ->hourly()
        ->withoutOverlapping();
    // Workers/queues: yo'q (queue ishlatilmaydi; to'g'ridan-to'g'ri DB update)
}
