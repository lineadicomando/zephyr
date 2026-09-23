<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\PreventRelatedDeletion;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PreventRelatedDeletion, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->scopes()->where('is_active', true)->orderBy('name')->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->scopes()->where('is_active', true)->whereKey($tenant)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->scopes()->where('is_active', true)->orderBy('name')->first();
    }

    /**
     * Roles assigned in every scope (the global team) or in the current one.
     * The spatie/permission relation would include only the current team.
     */
    public function roles(): BelongsToMany
    {
        $registrar = app(PermissionRegistrar::class);
        $rolesTeamColumn = config('permission.table_names.roles').'.'.$registrar->teamsKey;

        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            $registrar->pivotRole,
        )
            ->withPivot($registrar->teamsKey)
            ->wherePivotIn($registrar->teamsKey, self::visiblePermissionTeams())
            ->where(fn (Builder $query) => $query->whereNull($rolesTeamColumn)->orWhere($rolesTeamColumn, getPermissionsTeamId()));
    }

    /**
     * Direct permissions assigned in every scope or in the current one.
     */
    public function permissions(): BelongsToMany
    {
        $registrar = app(PermissionRegistrar::class);

        return $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key'),
            $registrar->pivotPermission,
        )
            ->withPivot($registrar->teamsKey)
            ->wherePivotIn($registrar->teamsKey, self::visiblePermissionTeams());
    }

    /**
     * @return list<int>
     */
    private static function visiblePermissionTeams(): array
    {
        return array_values(array_unique([
            Scope::GLOBAL_PERMISSIONS_TEAM,
            (int) (getPermissionsTeamId() ?? Scope::GLOBAL_PERMISSIONS_TEAM),
        ]));
    }

    /**
     * Super admins hold the role in the global team, so they are super admins
     * in every scope.
     */
    public function isRoot(): bool
    {
        return $this->roles->contains(
            fn (Role $role): bool => $role->name === 'super_admin'
                && (int) $role->pivot->{app(PermissionRegistrar::class)->teamsKey} === Scope::GLOBAL_PERMISSIONS_TEAM,
        );
    }

    /**
     * Whether the user is a super admin, or an admin in the current scope.
     */
    public function isAdmin(): bool
    {
        return $this->isRoot() || $this->hasRole('admin');
    }

    /**
     * Whether the user is an admin or a super admin in any scope.
     */
    public function isAdminInAnyScope(): bool
    {
        return $this->roleAssignments()
            ->whereIn('role_id', Role::query()->whereIn('name', ['super_admin', 'admin'])->select('id'))
            ->exists();
    }

    /**
     * Roles assigned to the user in the given scope only.
     *
     * @return EloquentCollection<int, Role>
     */
    public function rolesInScope(int $scopeId): EloquentCollection
    {
        return Role::query()
            ->whereIn('id', $this->roleAssignments()->where(app(PermissionRegistrar::class)->teamsKey, $scopeId)->select('role_id'))
            ->get();
    }

    /**
     * Replace the roles assigned in the given scope; the roles of the other
     * scopes and the global ones are kept.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public function syncRolesInScope(int $scopeId, array $roleIds): void
    {
        $teamsKey = app(PermissionRegistrar::class)->teamsKey;

        DB::transaction(function () use ($scopeId, $roleIds, $teamsKey): void {
            $this->roleAssignments()->where($teamsKey, $scopeId)->delete();

            $this->roleAssignments()->insert(collect($roleIds)->unique()->map(fn (int|string $roleId): array => [
                'role_id' => (int) $roleId,
                'model_type' => $this->getMorphClass(),
                'model_id' => $this->getKey(),
                $teamsKey => $scopeId,
            ])->values()->all());
        });

        $this->unsetRelation('roles');
    }

    /**
     * Give or take the super_admin role, which is always global.
     */
    public function setRoot(bool $isRoot): void
    {
        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $superAdminRole = Role::findByName('super_admin', 'web');
        $assignment = $this->roleAssignments()
            ->where('role_id', $superAdminRole->getKey())
            ->where($teamsKey, Scope::GLOBAL_PERMISSIONS_TEAM);

        if (! $isRoot) {
            $assignment->delete();
        } elseif ($assignment->doesntExist()) {
            $this->roleAssignments()->insert([
                'role_id' => $superAdminRole->getKey(),
                'model_type' => $this->getMorphClass(),
                'model_id' => $this->getKey(),
                $teamsKey => Scope::GLOBAL_PERMISSIONS_TEAM,
            ]);
        }

        $this->unsetRelation('roles');
    }

    /**
     * Rows of the role assignments of the user, in every scope.
     */
    private function roleAssignments(): QueryBuilder
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', $this->getMorphClass())
            ->where(config('permission.column_names.model_morph_key'), $this->getKey());
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(Scope::class)->withTimestamps();
    }

    public function hasScope(int $scopeId): bool
    {
        return $this->scopes()->whereKey($scopeId)->exists();
    }

    /**
     * Determine whether the user belongs to at least one scope of $other.
     */
    public function sharesScopeWith(User $other): bool
    {
        return $this->scopes()->whereIn('scopes.id', $other->scopes()->select('scopes.id'))->exists();
    }

    /**
     * Determine whether $other belongs to at least one scope and every scope
     * of $other is also a scope of the user.
     */
    public function coversScopesOf(User $other): bool
    {
        return $other->scopes()->exists()
            && $other->scopes()->whereNotIn('scopes.id', $this->scopes()->select('scopes.id'))->doesntExist();
    }

    /**
     * Limit the query to the users visible to $viewer: super admins see every
     * user, everybody else only the users sharing at least one scope.
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isRoot()) {
            return $query;
        }

        return $query->whereHas('scopes', fn (Builder $scopes): Builder => $scopes
            ->whereIn('scopes.id', $viewer->scopes()->select('scopes.id')));
    }

    public function preventDeletionBy()
    {
        return [
            'tasks',
        ];
    }
}
