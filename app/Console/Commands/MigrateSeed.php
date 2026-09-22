<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class MigrateSeed extends Command
{
    use ConfirmableTrait;

    protected $signature = 'migrate:seed {--no-fresh : Use migrate --seed instead of migrate:fresh --seed}
        {--force : Force the operation to run when in production}';

    protected $description = 'Run migrations and seed baseline data (roles, permissions, bootstrap admin), without demo data';

    public function handle(): int
    {
        $fresh = ! $this->option('no-fresh');

        if ($fresh && ! $this->confirmToProceed('This will drop all tables of the database.')) {
            return self::FAILURE;
        }

        $previous = getenv('SEED_DEMO_DATA');

        putenv('SEED_DEMO_DATA=false');
        $_ENV['SEED_DEMO_DATA'] = 'false';
        $_SERVER['SEED_DEMO_DATA'] = 'false';

        $command = $fresh ? 'migrate:fresh' : 'migrate';

        $exitCode = $this->call($command, [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->restoreSeedDemoEnv($previous);

        return $exitCode;
    }

    private function restoreSeedDemoEnv(string|false $previous): void
    {
        if ($previous === false) {
            putenv('SEED_DEMO_DATA');
            unset($_ENV['SEED_DEMO_DATA'], $_SERVER['SEED_DEMO_DATA']);

            return;
        }

        putenv("SEED_DEMO_DATA={$previous}");
        $_ENV['SEED_DEMO_DATA'] = $previous;
        $_SERVER['SEED_DEMO_DATA'] = $previous;
    }
}
