<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeApiToken extends Command
{
    protected $signature = 'api-tokens:revoke {id : Id of the token, as shown by api-tokens:list}';

    protected $description = 'Revoke an API token';

    public function handle(): int
    {
        $deleted = PersonalAccessToken::query()->whereKey($this->argument('id'))->delete();

        if ($deleted === 0) {
            $this->error('Token not found.');

            return self::FAILURE;
        }

        $this->info('Token revoked.');

        return self::SUCCESS;
    }
}
