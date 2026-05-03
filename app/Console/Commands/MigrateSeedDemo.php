<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateSeedDemo extends Command
{
    protected $signature = 'migrate:seed_demo {--no-fresh : Use migrate --seed instead of migrate:fresh --seed}';

    protected $description = 'Run migrations and seed baseline plus demo data';

    public function handle(): int
    {
        $previous = getenv('SEED_DEMO_DATA');

        putenv('SEED_DEMO_DATA=true');
        $_ENV['SEED_DEMO_DATA'] = 'true';
        $_SERVER['SEED_DEMO_DATA'] = 'true';

        $command = $this->option('no-fresh') ? 'migrate' : 'migrate:fresh';

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
