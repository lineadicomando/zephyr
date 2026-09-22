<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run')->hourly()->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:00');
Schedule::command('db:check')->daily();
Schedule::command('scopes:purge-pending')->hourly()->withoutOverlapping();
