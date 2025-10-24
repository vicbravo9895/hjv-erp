<?php

use Illuminate\Console\Scheduling\Schedule;

// Samsara Sync Scheduling - Execute every minute as per requirements
app(Schedule::class)->command('samsara:sync-vehicles --force')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(function () {
        return config('samsara.sync.enable_vehicles_sync', true);
    });

app(Schedule::class)->command('samsara:sync-trailers --force')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(function () {
        return config('samsara.sync.enable_trailers_sync', true);
    });

// Cleanup old sync logs daily at 2 AM
app(Schedule::class)->command('samsara:cleanup-logs --mark-stuck')
    ->dailyAt('02:00')
    ->withoutOverlapping();
