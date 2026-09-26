<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recurring ops (cron hits `schedule:run` every minute):
Schedule::command('dispatch:orders')->everyMinute();
Schedule::command('schedule:send')->everyMinute();
