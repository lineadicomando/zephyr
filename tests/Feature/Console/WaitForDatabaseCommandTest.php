<?php

use Illuminate\Support\Sleep;

it('succeeds when the database accepts connections', function () {
    $this->artisan('db:wait', ['--timeout' => 0])
        ->expectsOutputToContain('is ready')
        ->assertSuccessful();
});

it('retries until the timeout and fails when the database is unreachable', function () {
    Sleep::fake(syncWithCarbon: true);

    config()->set('database.connections.unreachable', [
        'driver' => 'sqlite',
        'database' => '/nonexistent/path/zephyr.sqlite',
    ]);
    config()->set('database.default', 'unreachable');

    $this->artisan('db:wait', ['--timeout' => 6, '--interval' => 2])
        ->expectsOutputToContain('Could not connect to the [unreachable] database within 6 seconds')
        ->assertFailed();

    Sleep::assertSleptTimes(3);
});
