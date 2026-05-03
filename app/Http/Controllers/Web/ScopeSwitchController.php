<?php

namespace App\Http\Controllers\Web;

use App\Contracts\ScopeContext;
use App\Models\Scope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScopeSwitchController
{
    public function __invoke(Request $request, Scope $scope, ScopeContext $scopeContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $isAllowed = $user->scopes()
            ->where('scopes.id', $scope->id)
            ->where('scopes.is_active', true)
            ->exists();

        if (! $isAllowed) {
            abort(403, 'Scope not available for the authenticated user.');
        }

        $scopeContext->setActiveScopeId((int) $scope->id);

        return redirect()->back();
    }
}
