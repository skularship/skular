<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:requeue-stuck')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('payments:reconcile-pending')->hourly()->withoutOverlapping();
Schedule::command('reminders:dispatch')->dailyAt('07:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('events:remind')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('subscriptions:remind-expiry')->dailyAt('07:10')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('tryout:close-expired')->everyMinute()->withoutOverlapping();
