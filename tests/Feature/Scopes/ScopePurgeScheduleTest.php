<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('schedules scopes purge pending command hourly without overlapping', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($scheduledEvent): bool => str_contains((string) $scheduledEvent->command, 'scopes:purge-pending'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

