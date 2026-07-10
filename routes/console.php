<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Shared hosting (Hostinger) has no long-running worker, so drain the
// queue every minute. --stop-when-empty exits once the queue is drained
// and --max-time keeps each run under the next scheduler tick.
Schedule::command('queue:work database --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping();
