<?php

namespace App\Http\Middleware;

use App\Contracts\ScopeContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveScope
{
    public function __construct(
        private readonly ScopeContext $scopeContext,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();
        $activeScopeId = $this->scopeContext->activeScopeId();

        $allowedScopes = $user->scopes()
            ->where('scopes.is_active', true);

        if (! (clone $allowedScopes)->exists()) {
            abort(403, 'No active scope assigned to the authenticated user.');
        }

        if ($activeScopeId !== null && (clone $allowedScopes)->whereKey($activeScopeId)->exists()) {
            return $next($request);
        }

        $fallbackScopeId = (clone $allowedScopes)
            ->orderBy('scopes.id')
            ->value('scopes.id');

        if (! $fallbackScopeId) {
            abort(403, 'No active scope assigned to the authenticated user.');
        }

        $this->scopeContext->setActiveScopeId((int) $fallbackScopeId);

        return $next($request);
    }
}
