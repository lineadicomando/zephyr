<?php

use App\Console\Commands\ZephyrSetup;
use Dotenv\Dotenv;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

it('derives the log level from the application environment', function (string $appEnv, string $logLevel) {
    expect(callZephyrSetup('environmentValues', $appEnv))->toBe([
        'APP_ENV' => $appEnv,
        'LOG_LEVEL' => $logLevel,
    ]);
})->with([
    'production' => ['production', 'error'],
    'local' => ['local', 'debug'],
]);

/**
 * Answers the password prompts of the setup command with the given inputs, in order.
 *
 * @param  list<string>  $answers
 */
function promptZephyrSetupPassword(array $answers, string $label, bool $allowEmpty): string
{
    Prompt::fallbackWhen(true);
    PasswordPrompt::fallbackUsing(function () use (&$answers): string {
        expect($answers)->not->toBeEmpty();

        return array_shift($answers);
    });

    $command = app(ZephyrSetup::class);
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    $password = (new ReflectionMethod(ZephyrSetup::class, 'promptPasswordWithConfirmation'))
        ->invoke($command, $label, $allowEmpty);

    expect($answers)->toBeEmpty();

    return $password;
}

it('keeps an empty optional password empty without asking for confirmation', function () {
    expect(promptZephyrSetupPassword([''], 'Database password', true))->toBe('');
});

it('asks again for a password until the confirmation matches', function () {
    expect(promptZephyrSetupPassword(['ab', 'x', 'ab', 'ab'], 'Backup archive password', true))->toBe('ab');
});

it('does not accept an empty required password', function () {
    expect(promptZephyrSetupPassword(['', 's', 's'], 'Bootstrap admin password', false))->toBe('s');
});

it('keeps the customized values of the existing env file when generating it again', function () {
    $template = "APP_NAME=Zephyr\nTRUSTED_PROXIES=\nMAIL_FROM_NAME=\"\${APP_NAME}\"\nDB_PASSWORD=password\n";
    $existing = "APP_NAME=Zephyr\nTRUSTED_PROXIES=10.0.0.1\nMAIL_FROM_NAME=\"\${APP_NAME}\"\nDB_PASSWORD=old\nCUSTOM_KEY='kept value'\n";

    $customized = callZephyrSetup(
        'customizedValues',
        callZephyrSetup('parseEnv', $existing),
        callZephyrSetup('parseEnv', $template),
    );

    $content = callZephyrSetup('applyValuesToTemplate', $template, array_merge($customized, ['DB_PASSWORD' => 'new']));

    expect($customized)->toBe(['TRUSTED_PROXIES' => '10.0.0.1', 'DB_PASSWORD' => 'old', 'CUSTOM_KEY' => 'kept value'])
        ->and(Dotenv::parse($content))
        ->TRUSTED_PROXIES->toBe('10.0.0.1')
        ->MAIL_FROM_NAME->toBe('Zephyr')
        ->DB_PASSWORD->toBe('new')
        ->CUSTOM_KEY->toBe('kept value');
});
