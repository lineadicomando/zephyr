<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class ListApiTokens extends Command
{
    protected $signature = 'api-tokens:list {email? : Only the tokens of this user}';

    protected $description = 'List the API tokens';

    public function handle(): int
    {
        $tokens = PersonalAccessToken::query()
            ->with('tokenable')
            ->when($this->argument('email'), fn ($query, string $email) => $query->whereHasMorph(
                'tokenable',
                '*',
                fn ($query) => $query->where('email', $email),
            ))
            ->orderBy('id')
            ->get();

        $this->table(
            ['#', 'User', 'Name', 'Abilities', 'Last used', 'Expires'],
            $tokens->map(fn (PersonalAccessToken $token): array => [
                $token->getKey(),
                $token->tokenable?->getAttribute('email'),
                $token->name,
                implode(', ', $token->abilities ?? []),
                $token->last_used_at?->toDateTimeString() ?? '-',
                $this->expiration($token),
            ])->all(),
        );

        return self::SUCCESS;
    }

    /**
     * Tokens expire at their own date or, when configured, after
     * sanctum.expiration minutes from their creation.
     */
    private function expiration(PersonalAccessToken $token): string
    {
        $expiration = config('sanctum.expiration');
        $expiresAt = $token->expires_at ?? ($expiration ? $token->created_at->addMinutes($expiration) : null);

        return $expiresAt?->toDateTimeString() ?? 'never';
    }
}
