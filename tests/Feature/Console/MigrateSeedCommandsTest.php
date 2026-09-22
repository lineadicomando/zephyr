<?php

use App\Console\Commands\MigrateSeed;
use App\Console\Commands\MigrateSeedDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

it('migrate seed sets flag false and restores previous env value', function () {
    putenv('SEED_DEMO_DATA=keep-me');
    $_ENV['SEED_DEMO_DATA'] = 'keep-me';
    $_SERVER['SEED_DEMO_DATA'] = 'keep-me';

    $command = Mockery::mock(MigrateSeed::class)->makePartial();
    $command->shouldReceive('option')->once()->with('no-fresh')->andReturnFalse();
    $command->shouldReceive('confirmToProceed')->once()->andReturnTrue();
    $command->shouldReceive('call')->once()->with('migrate:fresh', [
        '--seed' => true,
        '--force' => true,
    ])->andReturn(0);

    $exitCode = $command->handle();

    expect($exitCode)->toBe(0)
        ->and(getenv('SEED_DEMO_DATA'))->toBe('keep-me')
        ->and($_ENV['SEED_DEMO_DATA'])->toBe('keep-me')
        ->and($_SERVER['SEED_DEMO_DATA'])->toBe('keep-me');
});

it('migrate seed demo sets flag true and unsets env when previously absent', function () {
    putenv('SEED_DEMO_DATA');
    unset($_ENV['SEED_DEMO_DATA'], $_SERVER['SEED_DEMO_DATA']);

    $command = Mockery::mock(MigrateSeedDemo::class)->makePartial();
    $command->shouldReceive('option')->once()->with('no-fresh')->andReturnTrue();
    $command->shouldNotReceive('confirmToProceed');
    $command->shouldReceive('call')->once()->with('migrate', [
        '--seed' => true,
        '--force' => true,
    ])->andReturn(0);

    $exitCode = $command->handle();

    expect($exitCode)->toBe(0)
        ->and(getenv('SEED_DEMO_DATA'))->toBeFalse()
        ->and(array_key_exists('SEED_DEMO_DATA', $_ENV))->toBeFalse()
        ->and(array_key_exists('SEED_DEMO_DATA', $_SERVER))->toBeFalse();
});

it('does not drop the database in production without confirmation', function (string $command) {
    app()->detectEnvironment(fn (): string => 'production');

    $this->withoutMockingConsoleOutput();

    $exitCode = Artisan::call($command, ['--no-interaction' => true]);

    expect($exitCode)->toBe(Command::FAILURE)
        ->and(Artisan::output())->toContain('Command cancelled');
})->with(['migrate:seed', 'migrate:seed_demo']);

it('does not run the fresh migration when confirmation is declined', function () {
    $command = Mockery::mock(MigrateSeed::class)->makePartial();
    $command->shouldReceive('option')->once()->with('no-fresh')->andReturnFalse();
    $command->shouldReceive('confirmToProceed')->once()->andReturnFalse();
    $command->shouldNotReceive('call');

    expect($command->handle())->toBe(MigrateSeed::FAILURE);
});
