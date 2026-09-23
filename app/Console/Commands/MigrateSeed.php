<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;

class MigrateSeed extends Command
{
    use ConfirmableTrait;

    protected $signature = 'migrate:seed {--no-fresh : Use migrate --seed instead of migrate:fresh --seed}
        {--if-not-seeded : With --no-fresh, only migrate when the database already has users}
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
        $seed = $fresh || ! $this->option('if-not-seeded') || ! $this->isSeeded();

        try {
            $exitCode = $this->call($command, [
                '--seed' => $seed,
                '--force' => true,
            ]);
        } finally {
            config()->set('app.seed_demo_data', $previous);
        }

        return $exitCode;
    }

    /**
     * The database counts as seeded once it has a user: a first seed that
     * failed before creating the bootstrap admin is run again.
     */
    protected function isSeeded(): bool
    {
        return Schema::hasTable('users') && User::withTrashed()->exists();
    }
}
