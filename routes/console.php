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

// Subscriptions: expire past-due ones (active -> expired)
Artisan::command('subscriptions:expire', function () {
    $affected = Subscription::query()
        ->where('status', 'active')
        ->whereDate('ends_at', '<', today())
        ->update(['status' => 'expired']);

    $this->info("Expired subscriptions updated: {$affected}");
})->purpose('Mark past-due subscriptions as expired');

// HTTP-trigger qilinadigan ishlar (unscheduled):
// - queue: default (job: App\Jobs\TrackVisitJob) — app/Http/Middleware/TrackVisits.php dan dispatch qilinadi.
//   Scheduler shart emas; default worker/Horizon ishlashi kifoya.

// HH integratsiyasi (misol):
Schedule::command('hh:sync-negotiations')
    ->everyFiveMinutes()
    ->withoutOverlapping();
// queue: default (Modules\Interviews\Jobs...) — Horizon default navbatini tinglashi kerak

// Auto apply (misol):
Schedule::command('autoapply:start')
    ->everyMinute()
    // ->onOneServer()
    ->withoutOverlapping();
// queue: autoapply — Horizon `--queue=autoapply` tinglab turadi (Horizon UI orqali sozlanadi)



// Vakansiyalarni arxivlash (to‘g‘ridan‑to‘g‘ri DB)
Schedule::command('telegram:vacancies:auto-archive')
    ->everyMinute()
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// Sessiya zaxirasi (12 soatda bir marta)
Schedule::command('telegram:session:backup')
    ->cron('0 */12 * * *')
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// Eslatma: Auto‑healing (soft reset) getHistory ichida allaqachon ishlaydi; quyidagini faqat istasangiz yoqing.
Schedule::command('telegram:session:soft-reset')
    ->dailyAt('04:30')
    //->onOneServer()
    ->withoutOverlapping();
// queue kerak emas

// 🔔 Nightly matching and notification job
Schedule::command('app:send-notification-command')
    ->dailyAt('06:30')
    ->withoutOverlapping();

Schedule::command('app:send-notification-command')
    ->dailyAt('18:30')
    ->withoutOverlapping();

// Foydalanuvchi trial davrini nazorat qiluvchi cron
Schedule::command('users:trials:deactivate')
    ->daily()
    ->withoutOverlapping();

// Subscriptions expiry checker (00:10 daily)
Schedule::command('subscriptions:expire')
    ->dailyAt('00:10')
    ->withoutOverlapping();

try {
    $window = (int) config('users.hh.refresh.window_hours', 6);
    $cron = config('users.hh.refresh.cron');
    $cmd = "hh:refresh-tokens --window={$window}";

    if ($cron) {
        Schedule::command($cmd)
            ->cron($cron)
            //->onOneServer()
            ->withoutOverlapping();
    } else {
        Schedule::command($cmd)
            ->hourly()
            //->onOneServer()
            ->withoutOverlapping();
    }
} catch (\Throwable $e) {
    // Agar config hali merge bo'lmagan bo'lsa yoki modul yuklanmasa — schedule bosqichida yiqilmasin
}

Schedule::command('relay:run --once')
    ->everyMinute()
    ->withoutOverlapping();

// Schedule::command('linkedin:fetch')
//     ->everyMinute();


// queue: telegram-relay (Modules\TelegramChannel\Jobs\SyncSourceChannelJob) — Horizon `telegram-relay` ni tinglaydi

// Har daqiqada FAILED vakansiyalarni avtomatik re-queue qilish (queued + dispatch)
Artisan::command('telegram:vacancies:requeue-failed {--limit=500}', function () {
    $limit = (int) $this->option('limit');
    if ($limit <= 0) {
        $limit = 500;
    }

    $lock = Cache::lock('tg:requeue_failed', 55);
    if (!$lock->get()) {
        $this->warn('Requeue lock busy; skip this minute.');
        return 0;
    }
    try {
        $ids = Vacancy::query()
            ->where('status', Vacancy::STATUS_FAILED)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $count = 0;
        foreach ($ids as $id) {
            // Atomically move to queued if still failed
            $updated = Vacancy::where('id', $id)
                ->where('status', Vacancy::STATUS_FAILED)
                ->update(['status' => Vacancy::STATUS_QUEUED]);
            if ($updated) {
                DeliverVacancyJob::dispatch($id)->onQueue('telegram-deliver');
                $count++;
            }
        }
        $this->info("Re-queued {$count} failed vacancy(ies) (limit={$limit}).");
        return 0;
    } finally {
        optional($lock)->release();
    }
})->purpose('Re-queue failed vacancies for delivery');

// Jadval: har daqiqada ishga tushadi
Schedule::command('telegram:vacancies:requeue-failed')
    ->everyMinute()
    ->withoutOverlapping();

// Queued (status=queued) vakansiyalarni qayta-dispatch qilish (safety net)
Schedule::call(function () {
    try {
        $cfg = (array) config('telegramchannel_relay.dispatch', []);
        $limit = (int) ($cfg['deliver_batch_size'] ?? 50);
        if ($limit <= 0) {
            $limit = 50;
        }
        $ids = Vacancy::query()
            ->where('status', Vacancy::STATUS_QUEUED)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
        foreach ($ids as $id) {
            $lock = Cache::lock('tg:dispatch:v' . $id, 10);
            if (!$lock->get()) {
                continue;
            }
            try {
                \Modules\TelegramChannel\Jobs\DeliverVacancyJob::dispatch($id)->onQueue('telegram-deliver');
            } finally {
                optional($lock)->release();
            }
        }
    } catch (\Throwable $e) {
        \Log::warning('dispatch-queued scheduler error', ['error' => $e->getMessage()]);
    }
})
    ->everyThirtySeconds()
    ->name('telegram:vacancies:dispatch-queued')
    ->withoutOverlapping();


Schedule::command('hh:telegram-send-negotiations')
    ->dailyAt('13:00')
    ->withoutOverlapping();

Schedule::command('career-tracking')
    ->dailyAt('22:10')
    ->withoutOverlapping();

// Database: har kuni soat kechgi payt soat 12 da ishga tushadi
Schedule::command('db:backup')
    ->dailyAt('00:00')
    ->withoutOverlapping();
