<?php

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
