<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'api-tokens:create
        {email : Email of the user the token acts as}
        {--name=api : Name of the token, to recognize it when listing or revoking}
        {--ability=* : Ability granted to the token (repeat the option for several)}';

    protected $description = 'Create an API token for a user and print it';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $knownAbilities = array_keys(config('sanctum.abilities'));
        $abilities = $this->option('ability') ?: $this->choice(
            'Abilities of the token (comma separated)',
            $knownAbilities,
            multiple: true,
        );

        if ($unknown = array_diff($abilities, $knownAbilities)) {
            $this->error('Unknown abilities: '.implode(', ', $unknown).'. Known abilities: '.implode(', ', $knownAbilities).'.');

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'), array_values($abilities));

        $this->info("Token #{$token->accessToken->getKey()} created for {$user->email} with abilities: ".implode(', ', $abilities).'.');
        $this->line('Copy it now, it is not shown again:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
