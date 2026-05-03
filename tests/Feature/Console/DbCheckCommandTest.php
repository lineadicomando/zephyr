<?php

use App\Console\Commands\DbCheck;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('runs dbCheck only on models that expose the method', function () {
    if (! class_exists('App\\Models\\TestDbCheckFake')) {
        eval('namespace App\\Models; class TestDbCheckFake { public static bool $called = false; public static function dbCheck(bool $output = false): void { self::$called = $output; } }');
    }

    File::shouldReceive('allFiles')
        ->once()
        ->andReturn([
            new SplFileInfo('/tmp/TestDbCheckFake.php', '/tmp', 'TestDbCheckFake.php'),
            new SplFileInfo('/tmp/User.php', '/tmp', 'User.php'),
        ]);

    $command = Mockery::mock(DbCheck::class)->makePartial();
    $command->shouldReceive('info')->once();

    $command->handle();

    expect(\App\Models\TestDbCheckFake::$called)->toBeTrue();
});
