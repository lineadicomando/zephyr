<?php

use App\Console\Commands\ZephyrSetup;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function callZephyrSetup(string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod(ZephyrSetup::class, $method);

    return $reflection->invoke(app(ZephyrSetup::class), ...$arguments);
}

it('writes values that are read back unchanged by dotenv', function (string $value) {
    $template = "APP_NAME=Zephyr\nDB_PASSWORD=password\nMAIL_FROM_NAME=\"\${APP_NAME}\"\n";

    $content = callZephyrSetup('applyValuesToTemplate', $template, ['DB_PASSWORD' => $value]);

    expect(Dotenv::parse($content))
        ->DB_PASSWORD->toBe($value)
        ->APP_NAME->toBe('Zephyr')
        ->MAIL_FROM_NAME->toBe('Zephyr');
})->with([
    'plain' => 'secret',
    'backreference' => 'ab$1cd',
    'whole match reference' => 'x\\0y',
    'variable like' => '${APP_NAME}',
    'spaces and hash' => 'my pass #1',
    'single quote' => "it's \$1 \\ \"quoted\"",
    'double quotes' => 'say "hi"',
]);

it('appends keys missing from the template', function () {
    $content = callZephyrSetup('applyValuesToTemplate', "APP_NAME=Zephyr\n", ['APP_KEY' => 'base64:abc$1']);

    expect(Dotenv::parse($content))->APP_KEY->toBe('base64:abc$1');
});

it('detects whether the database already contains tables', function () {
    expect(callZephyrSetup('databaseHasTables', config('database.default')))->toBeTrue()
        ->and(callZephyrSetup('databaseHasTables', 'missing-connection'))->toBeFalse();
});

it('passes the collected setup values to the runtime configuration used by the seeders', function () {
    callZephyrSetup('syncRuntimeConfig', [
        'BOOTSTRAP_ADMIN_EMAIL' => 'setup@example.com',
        'BOOTSTRAP_ADMIN_PASSWORD' => 'setup-secret',
        'ZPH_TIME_START_WORK' => '08:00',
    ]);

    expect(config('app.bootstrap_admin.email'))->toBe('setup@example.com')
        ->and(config('app.bootstrap_admin.password'))->toBe('setup-secret')
        ->and(config('app.work_schedule.start'))->toBe('08:00');
});
