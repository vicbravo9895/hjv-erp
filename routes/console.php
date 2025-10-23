<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Samsara Sync Scheduling - Execute every minute as per requirements
app(Schedule::class)->command('samsara:sync-vehicles')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->when(function () {
        return config('samsara.sync.enable_vehicles_sync', true);
    });

app(Schedule::class)->command('samsara:sync-trailers')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->when(function () {
        return config('samsara.sync.enable_trailers_sync', true);
    });

// Cleanup old sync logs daily at 2 AM
app(Schedule::class)->command('samsara:cleanup-logs --mark-stuck')
    ->dailyAt('02:00')
    ->withoutOverlapping();
