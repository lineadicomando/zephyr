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

        $previous = config('app.seed_demo_data');
        config()->set('app.seed_demo_data', false);

        $command = $fresh ? 'migrate:fresh' : 'migrate';

        try {
            $exitCode = $this->call($command, [
                '--seed' => true,
                '--force' => true,
            ]);
        } finally {
            config()->set('app.seed_demo_data', $previous);
        }

        return $exitCode;
    }
}
