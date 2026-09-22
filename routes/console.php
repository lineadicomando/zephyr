<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$backupsEnabled = fn (): bool => (bool) config('backup.enabled');

Schedule::command('backup:run')->hourly()->withoutOverlapping()->when($backupsEnabled);
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping()->when($backupsEnabled);
Schedule::command('backup:monitor')->dailyAt('03:00')->when($backupsEnabled);
Schedule::command('db:check')->daily();
Schedule::command('scopes:purge-pending')->hourly()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
