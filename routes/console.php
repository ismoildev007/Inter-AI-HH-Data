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
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;
use App\Models\Vacancy;
use Modules\TelegramChannel\Jobs\DeliverVacancyJob;
use Modules\TelegramChannel\Console\Commands\DispatchQueuedVacanciesCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command('linkedin:fetch')
    ->everyMinute();


