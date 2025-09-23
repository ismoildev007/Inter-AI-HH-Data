<?php

// ---------------------
// Scheduler (php artisan schedule:work) ishga tushirganda:
// - hh:sync-negotiations → doimiy ravishda HH dan holatlarni olib keladi.
//   * Agar holat 'interview' bo‘lsa: intervyu job(lar) dispatch qilinadi.
//   * Bu job(lar)ni qayta ishlash uchun Horizon/queue:work KERAK (default queue).
// - autoapply:start → avtomatik apply qiladi.
//   * Queue ishlatmaydi, Horizon KERAK EMAS.
// - (ixtiyoriy) telegram:vacancies:auto-archive → hozir schedule qilinmagan.
//   * Xohlasangiz, schedule ga qo‘shish mumkin (masalan, har kuni 03:00). Queue KERAK EMAS.
//
// Qo‘lda ishga tushiriladigan va queue talab qiladiganlar:
// - relay:run {--once} → Telegram sinxronlash uchun job(lar) yuboradi (queue: telegram-relay).
//   * Ishlashi uchun Horizon/queue:work KERAK (telegram-relay queue ni iste’mol qilishi kerak).
// php artisan schedule:work bilan shunda php artisan relay:run --once ishga tushirilishi mumkin doimiy ishlab turadi.
//php artisan horizon bilan birga queue worker ishlaydi telegramniki.
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
