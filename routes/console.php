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

if (array_key_exists('relay:run', Artisan::all())) {
    Schedule::command('relay:run --once')
        ->everyMinute()
        ->withoutOverlapping();
}

if (array_key_exists('telegram:vacancies:auto-archive', Artisan::all())) {
    Schedule::command('telegram:vacancies:auto-archive')
        ->hourly()
        ->withoutOverlapping();
}

