<?php

/*
|--------------------------------------------------------------------------
| Konsol komandalar va jadval (O‘zbekcha sharh)
|--------------------------------------------------------------------------
|
| Qo‘lda bajariladigan (interactive) komandalar:
| - telegram:login
|     Vazifa: Telegramga login (telefon/QR/2FA). Sessiya faylini yaratadi.
|     Qachon: Birinchi sozlash yoki relogin kerak bo‘lganda.
|
| - telegram:session:soft-reset
|     Vazifa: MadelineProto klientini sessiya faylini o‘chirmasdan qayta ishga tushiradi.
|     Qachon: Loglarda “The operation was cancelled” / “PEER_DB_MISS” ko‘payganda
|             va avto‑healing yordam bermayotganda.
|
| - telegram:session:relogin [--force] [--path=]
|     Vazifa: Avval backup qiladi, so‘ng session.madeline* fayllarni o‘chiradi.
|             Keyin albatta `php artisan telegram:login` bilan qayta login qiling.
|     Qachon: Soft reset ham yordam bermagan muammolarda.
|
| Avtomatik (schedule) komandalar — php artisan schedule:work yoki cron orqali:
| - relay:run --once (har daqiqa)
|     Vazifa: Source peer’larni round‑robin asosida `telegram-relay` navbatiga yuboradi.
|     Eslatma: Ishlash uchun Horizon’da `telegram-relay` navbati tinglanyapgan bo‘lishi shart.
|
| - telegram:vacancies:auto-archive (har soat)
|     Vazifa: Eski publish qilingan vakansiyalarni avtomatik archive statusiga o‘tkazadi.
|     Eslatma: Queue ishlatmaydi.
|
| - telegram:session:backup (12 soatda bir marta)
|     Vazifa: session.madeline* fayllarni zaxiraga nusxalaydi, retention bilan.
|     Eslatma: Queue ishlatmaydi.
|
| Horizon (queue worker) haqida:
| - `php artisan horizon` — navbatlarni tinglaydi (default, telegram‑relay va boshqalar).
| - Horizon ishlayotgan bo‘lsa, alohida `queue:work` ishga tushirmang.
| - Telegram uchun barqarorlik qoidasi: “bitta sessiya = bitta process”.
|   config/horizon.php ichida `telegram-relay.maxProcesses = 1` qo‘yilgan.
|
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// HTTP-trigger qilinadigan ishlar (unscheduled):
// - queue: default (job: App\Jobs\TrackVisitJob) — app/Http/Middleware/TrackVisits.php dan dispatch qilinadi.
//   Scheduler shart emas; default worker/Horizon ishlashi kifoya.

// HH integratsiyasi (misol):
Schedule::command('hh:sync-negotiations')
    ->everyFiveMinutes()
    //->onOneServer()
    ->withoutOverlapping();
// queue: default (Modules\Interviews\Jobs...) — Horizon default navbatini tinglashi kerak

// Auto apply (misol):
Schedule::command('autoapply:start')
    ->everyMinute()
   // ->onOneServer()
    ->withoutOverlapping();
// queue: autoapply — Horizon `--queue=autoapply` tinglab turadi (Horizon UI orqali sozlanadi)

// TelegramChannel: source’larni bo‘lib‑bo‘lib dispatch qilish (round‑robin)
Schedule::command('relay:run --once')
    ->everyMinute()
    //->onOneServer()
    ->withoutOverlapping();
// queue: telegram-relay (Modules\TelegramChannel\Jobs\SyncSourceChannelJob) — Horizon `telegram-relay` ni tinglaydi

// Vakansiyalarni arxivlash (to‘g‘ridan‑to‘g‘ri DB)
Schedule::command('telegram:vacancies:auto-archive')
    ->hourly()
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// Sessiya zaxirasi (12 soatda bir marta)
Schedule::command('telegram:session:backup')
    ->cron('0 */12 * * *')
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// Ixtiyoriy: Agar rejalashtirilgan “yumshoq” profilaktika xohlasangiz, quyidagini YOQISH mumkin.
// Eslatma: Auto‑healing (soft reset) getHistory ichida allaqachon ishlaydi; quyidagini faqat istasangiz yoqing.
 Schedule::command('telegram:session:soft-reset')
    ->dailyAt('04:30')
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// 🔔 Nightly matching and notification job
Schedule::command('app:send-notification-command')
    ->dailyAt('03:30') // run every night at 03:30 AM
    // ->everyMinute()
    // ->onOneServer()    // ensures it runs only once across all servers
    ->withoutOverlapping();


