<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use Throwable;

class WaitForDatabase extends Command
{
    protected $signature = 'db:wait
        {--timeout=120 : Maximum number of seconds to wait}
        {--interval=2 : Seconds between connection attempts}';

    protected $description = 'Wait until the default database connection accepts connections';

    public function handle(): int
    {
        $timeout = max(0, (int) $this->option('timeout'));
        $interval = max(1, (int) $this->option('interval'));
        $connection = config('database.default');
        $deadline = now()->addSeconds($timeout);

        while (true) {
            try {
                DB::purge($connection);
                DB::connection($connection)->getPdo();

                $this->info("Database connection [{$connection}] is ready.");

                return self::SUCCESS;
            } catch (Throwable $exception) {
                if (now()->greaterThanOrEqualTo($deadline)) {
                    $this->error("Could not connect to the [{$connection}] database within {$timeout} seconds: {$exception->getMessage()}");

                    return self::FAILURE;
                }
            }

            Sleep::for($interval)->seconds();
        }
    }
}
