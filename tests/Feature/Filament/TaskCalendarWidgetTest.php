<?php

use App\Filament\Resources\TaskResource\Widgets\TaskCalendarWidget;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

function calendarSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function setActiveScopeForCalendar(User $user): int
{
    $scopeId = DB::table('scopes')->insertGetId([
        'name' => 'Scope Calendar ' . str()->uuid(),
        'slug' => 'scope-calendar-' . str()->lower((string) str()->ulid()),
        'type' => 'company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('scope_user')->insert([
        'scope_id' => $scopeId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    test()->withSession(['active_scope_id' => $scopeId]);

    return $scopeId;
}

function makeTaskForCalendar(User $user, string $start, ?string $end = null): Task
{
    $status = TaskStatus::query()->create([
        'name' => 'Status ' . str()->uuid(),
        'order' => 1,
        'color' => 'info',
        'default' => true,
        'completed' => false,
    ]);

    $type = TaskType::query()->create([
        'name' => 'Type ' . str()->uuid(),
        'chart' => false,
        'chart_color' => '#ff0000',
    ]);

    return Task::query()->create([
        'starts_at' => $start,
        'ends_at' => $end,
        'all_day' => false,
        'task_type_id' => $type->id,
        'task_status_id' => $status->id,
        'user_id' => $user->id,
        'description' => 'Calendar task',
    ]);
}

it('updates task dates when an event is dropped', function () {
    $user = calendarSuperAdmin();
    setActiveScopeForCalendar($user);
    $this->actingAs($user);

    $task = makeTaskForCalendar($user, '2026-05-01 09:00:00', '2026-05-01 10:00:00');

    $widget = new TaskCalendarWidget();

    $widget->onEventDrop(
        event: [
            'id' => $task->id,
            'start' => '2026-05-02 14:30:00',
            'end' => '2026-05-02 15:30:00',
        ],
        oldEvent: [],
        relatedEvents: [],
        delta: [],
        oldResource: null,
        newResource: null,
    );

    $task->refresh();

    expect($task->starts_at)->toBe('2026-05-02 14:30:00')
        ->and($task->ends_at)->toBe('2026-05-02 15:30:00');
});

it('fetches events that overlap the selected window', function () {
    $user = calendarSuperAdmin();
    setActiveScopeForCalendar($user);
    $this->actingAs($user);

    // Overlaps window: starts before window start, ends inside window.
    $overlapping = makeTaskForCalendar($user, '2026-05-10 08:00:00', '2026-05-10 12:00:00');

    $widget = new TaskCalendarWidget();

    $events = $widget->fetchEvents([
        'start' => '2026-05-10 10:00:00',
        'end' => '2026-05-10 18:00:00',
    ]);

    $eventIds = array_column($events, 'id');

    expect($eventIds)->toContain($overlapping->id);
});

it('normalizes selected datetimes to calendar timezone for create dialog', function () {
    config()->set('app.calendar_timezone', 'Europe/Rome');
    $widget = new TaskCalendarWidget();

    $method = new \ReflectionMethod(TaskCalendarWidget::class, 'toCalendarTimezone');
    $method->setAccessible(true);

    $normalizedUtc = $method->invoke($widget, '2026-05-10T10:00:00+00:00');
    $normalizedLocal = $method->invoke($widget, '2026-05-10T10:00:00');

    expect($normalizedUtc)->toBe('2026-05-10 12:00:00')
        ->and($normalizedLocal)->toBe('2026-05-10 10:00:00');
});
