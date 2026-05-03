<?php

namespace App\Models\Concerns;

use App\Contracts\ScopeContext;
use App\Models\Scope;
use App\Models\Scopes\ActiveScopeGlobalScope;
use App\Support\Scope\ScopeAccessResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToScope
{
    public static function bootBelongsToScope(): void
    {
        static::addGlobalScope(new ActiveScopeGlobalScope);

        static::creating(function ($model): void {
            if (! empty($model->scope_id)) {
                return;
            }

            $scopeId = app(ScopeContext::class)->activeScopeId();

            if ($scopeId !== null) {
                $model->scope_id = $scopeId;
                return;
            }

            $defaultScopeId = app(ScopeAccessResolver::class)->defaultScopeId();

            if ($defaultScopeId === null) {
                throw new \RuntimeException('Default scope is missing. Run seeders before creating scoped records.');
            }

            $model->scope_id = $defaultScopeId;
        });
    }

    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }
}
