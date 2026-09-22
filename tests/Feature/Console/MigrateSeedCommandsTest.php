<?php

use App\Console\Commands\MigrateSeed;
use App\Console\Commands\MigrateSeedDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

it('migrate seed disables demo data while seeding and restores the previous value', function () {
    config()->set('app.seed_demo_data', true);

    $command = Mockery::mock(MigrateSeed::class)->makePartial();
    $command->shouldReceive('option')->once()->with('no-fresh')->andReturnFalse();
    $command->shouldReceive('confirmToProceed')->once()->andReturnTrue();
    $command->shouldReceive('call')->once()->with('migrate:fresh', [
        '--seed' => true,
        '--force' => true,
    ])->andReturnUsing(function (): int {
        expect(config('app.seed_demo_data'))->toBeFalse();

        return 0;
    });

    expect($command->handle())->toBe(0)
        ->and(config('app.seed_demo_data'))->toBeTrue();
});

it('migrate seed demo enables demo data while seeding and restores the previous value', function () {
    config()->set('app.seed_demo_data', false);

    $command = Mockery::mock(MigrateSeedDemo::class)->makePartial();
    $command->shouldReceive('option')->once()->with('no-fresh')->andReturnTrue();
    $command->shouldNotReceive('confirmToProceed');
    $command->shouldReceive('call')->once()->with('migrate', [
        '--seed' => true,
        '--force' => true,
    ])->andReturnUsing(function (): int {
        expect(config('app.seed_demo_data'))->toBeTrue();

        return 0;
    });

    expect($command->handle())->toBe(0)
        ->and(config('app.seed_demo_data'))->toBeFalse();
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
