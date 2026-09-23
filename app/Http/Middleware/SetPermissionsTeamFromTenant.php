<?php

namespace App\Http\Middleware;

use App\Models\Scope;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamFromTenant
{
    /**
     * Check roles and permissions against the current tenant: the roles of
     * the user in the scope of the URL, plus the global ones.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        setPermissionsTeamId(Filament::getTenant()?->getKey() ?? Scope::GLOBAL_PERMISSIONS_TEAM);

        // Roles loaded before the team was known would be those of another team.
        $request->user()?->unsetRelation('roles')->unsetRelation('permissions');

        return $next($request);
    }
}
