<?php

use Illuminate\Console\Scheduling\Schedule;

function scheduledBackupEvent(string $command): mixed
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_ends_with((string) $event->command, $command));
}

it('schedules backups, their cleanup and their monitoring', function (string $command, string $expression) {
    $event = scheduledBackupEvent($command);

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe($expression);
})->with([
    'backup' => ['backup:run', '0 * * * *'],
    'cleanup' => ['backup:clean', '30 1 * * *'],
    'monitor' => ['backup:monitor', '0 3 * * *'],
]);

it('does not overlap backup runs', function () {
    expect(scheduledBackupEvent('backup:run')->withoutOverlapping)->toBeTrue();
});

it('sends backup notifications to the configured recipient', function () {
    expect(config('backup.notifications.mail.to'))->not->toBe('your@example.com');
});
