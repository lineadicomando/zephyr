<?php

namespace App\Models\Scopes;

use App\Contracts\ScopeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;

class ActiveScopeGlobalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->shouldBypassScopeFilterForCurrentConsoleCommand()) {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        $scopeId = app(ScopeContext::class)->activeScopeId();

        if ($scopeId === null) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->qualifyColumn('scope_id'), $scopeId);
    }

    private function shouldBypassScopeFilterForCurrentConsoleCommand(): bool
    {
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? null;

        return self::shouldBypassScopeFilterForConsoleCommand($command);
    }

    public static function shouldBypassScopeFilterForConsoleCommand(?string $command): bool
    {
        if (! is_string($command) || $command === '') {
            return false;
        }

        /** @var array<int, string> $allowList */
        $allowList = config('scopes.console_bypass_commands', []);

        foreach ($allowList as $pattern) {
            if (Str::is($pattern, $command)) {
                return true;
            }
        }

        return false;
    }
}
