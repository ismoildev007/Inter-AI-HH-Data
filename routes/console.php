<?php

// ---------------------
// Scheduler (php artisan schedule:work) ishga tushirganda:
// - hh:sync-negotiations → HH dan holatlarni olib keladi (5 daqiqada 1 marta).
//   * Agar holat 'interview' bo‘lsa: intervyu job(lar) dispatch qilinadi (queue: default, Horizon/queue:work KERAK).
// - autoapply:start → avtomatik apply (har daqiqa). Queue talab qilmaydi.
// - relay:run --once → Telegram sinxronlash (har daqiqa). (queue: telegram-relay, Horizon/queue:work KERAK).
// - telegram:vacancies:auto-archive → eski vakansiyalarni arxivlash (soatlik). Queue talab qilmaydi.
//
// Eslatma: php artisan schedule:work (scheduler) va php artisan horizon yoki queue:work ishlashi kerak.
// Qo‘lda, interaktiv:
// - telegram:login → Telegramga login (telefon/QR). Scheduler/Queue talab qilmaydi.
// ---------------------


use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('hh:sync-negotiations')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('autoapply:start')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn() => \Log::info('Autoapply schedule hit'))->everyMinute();

// TelegramChannel schedules (migrated from module provider)
if (Artisan::has('relay:run')) {
    Schedule::command('relay:run --once')
        ->everyMinute()
        ->withoutOverlapping();
}
if (Artisan::has('telegram:vacancies:auto-archive')) {
    Schedule::command('telegram:vacancies:auto-archive')
        ->hourly()
        ->withoutOverlapping();
}
