<?php

use Dotenv\Dotenv;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;

beforeEach(function () {
    // zephyr:setup exports the .env values to the process environment.
    $this->originalEnvironment = [getenv(), $_ENV, $_SERVER];

    $this->environmentPath = sys_get_temp_dir().'/zephyr-setup-'.uniqid();
    File::ensureDirectoryExists($this->environmentPath);
    File::copy(base_path('.env.example'), "{$this->environmentPath}/.env.example");
    $this->app->useEnvironmentPath($this->environmentPath);
});

afterEach(function () {
    File::deleteDirectory($this->environmentPath);

    [$variables, $_ENV, $_SERVER] = $this->originalEnvironment;

    foreach (array_diff_key(getenv(), $variables) as $name => $value) {
        putenv($name);
    }
    foreach ($variables as $name => $value) {
        putenv("{$name}={$value}");
    }
});

/**
 * Run zephyr:setup answering every prompt, with a SQLite database in the
 * temporary environment directory.
 *
 * @param  array<string, string>  $answers  answers overriding the defaults, by prompt label
 */
function runZephyrSetup(object $test, array $answers = [], ?Closure $afterDatabaseQuestions = null): PendingCommand
{
    $answers = [
        'Application environment' => 'production',
        'Application locale' => 'it',
        'Application URL' => 'https://zephyr.example.com',
        'Calendar timezone' => 'Europe/Rome',
        'Date format' => 'd/m/Y',
        'Datetime format' => 'd/m/Y H:i',
        'Workday start time (HH:MM)' => '08:00',
        'Workday end time (HH:MM)' => '17:00',
        'Working days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        'Database driver' => 'sqlite',
        'SQLite database path' => "{$test->environmentPath}/database.sqlite",
        'Backup archive password (leave empty to disable encryption) (hidden input)' => '',
        'Bootstrap admin name' => 'Root',
        'Bootstrap admin email' => 'root@example.com',
        'Bootstrap admin password (hidden input)' => 'bootstrap-secret',
        'Bootstrap admin password confirmation' => 'bootstrap-secret',
        ...$answers,
    ];

    $command = $test->artisan('zephyr:setup', ['--force' => true])
        ->expectsQuestion('Application environment', $answers['Application environment'])
        ->expectsQuestion('Application locale', $answers['Application locale'])
        ->expectsQuestion('Application URL', $answers['Application URL'])
        ->expectsConfirmation('Enable debug mode (APP_DEBUG)?', 'no')
        ->expectsQuestion('Calendar timezone', $answers['Calendar timezone'])
        ->expectsQuestion('Date format', $answers['Date format'])
        ->expectsQuestion('Datetime format', $answers['Datetime format'])
        ->expectsQuestion('Workday start time (HH:MM)', $answers['Workday start time (HH:MM)'])
        ->expectsQuestion('Workday end time (HH:MM)', $answers['Workday end time (HH:MM)'])
        ->expectsConfirmation('Use default working days (Mon-Sat)?', 'no')
        ->expectsQuestion('Working days', $answers['Working days'])
        ->expectsConfirmation('Load demo data?', 'no')
        ->expectsQuestion('Database driver', $answers['Database driver'])
        ->expectsQuestion('SQLite database path', $answers['SQLite database path'])
        ->expectsQuestion('Backup archive password (leave empty to disable encryption) (hidden input)', $answers['Backup archive password (leave empty to disable encryption) (hidden input)'])
        ->expectsQuestion('Bootstrap admin name', $answers['Bootstrap admin name'])
        ->expectsQuestion('Bootstrap admin email', $answers['Bootstrap admin email'])
        ->expectsQuestion('Bootstrap admin password (hidden input)', $answers['Bootstrap admin password (hidden input)'])
        ->expectsQuestion('Bootstrap admin password confirmation', $answers['Bootstrap admin password confirmation']);

    return $afterDatabaseQuestions ? $afterDatabaseQuestions($command) : $command;
}

it('writes the answers to .env, creates the database and removes the admin password', function () {
    runZephyrSetup($this)->assertSuccessful()->run();

    $env = Dotenv::parse(File::get("{$this->environmentPath}/.env"));

    expect($env)
        ->APP_ENV->toBe('production')
        ->APP_DEBUG->toBe('false')
        ->LOG_LEVEL->toBe('error')
        ->APP_URL->toBe('https://zephyr.example.com')
        ->APP_LOCALE->toBe('it')
        ->ZPH_WORK_DAYS->toBe('1,2,3,4,5')
        ->DB_CONNECTION->toBe('sqlite')
        ->BOOTSTRAP_ADMIN_EMAIL->toBe('root@example.com')
        ->BOOTSTRAP_ADMIN_PASSWORD->toBe('')
        ->and($env['APP_KEY'])->toStartWith('base64:')
        ->and(File::exists("{$this->environmentPath}/database.sqlite"))->toBeTrue();
});

it('keeps the application key and the customized values when run again', function () {
    File::put("{$this->environmentPath}/.env", "APP_KEY=base64:existing-key\nTRUSTED_PROXIES=10.0.0.1\n");

    runZephyrSetup($this)->assertSuccessful()->run();

    expect(Dotenv::parse(File::get("{$this->environmentPath}/.env")))
        ->APP_KEY->toBe('base64:existing-key')
        ->TRUSTED_PROXIES->toBe('10.0.0.1');
});

it('removes the admin password from .env when seeding fails', function () {
    // A directory is no SQLite database: the connection and then the seed fail.
    $setup = runZephyrSetup(
        $this,
        ['SQLite database path' => $this->environmentPath],
        fn (PendingCommand $command): PendingCommand => $command->expectsConfirmation('Do you want to continue anyway?', 'yes'),
    );

    expect(fn () => $setup->run())->toThrow(QueryException::class);

    expect(Dotenv::parse(File::get("{$this->environmentPath}/.env"))['BOOTSTRAP_ADMIN_PASSWORD'])->toBe('');
});
