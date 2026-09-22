<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\PreventRelatedDeletion;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
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

    public function isRoot(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
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
