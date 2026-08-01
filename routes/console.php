<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('typhoon:update-exchange-rates --source=coinapi')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.from.address'));

Schedule::command('typhoon:check-crypto-deposits')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('typhoon:process-standing-orders')
    ->dailyAt('02:00')
    ->withoutOverlapping();
