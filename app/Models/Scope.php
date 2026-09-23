<?php

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

class Scope extends Model implements HasCurrentTenantLabel
{
    use HasFactory;

    /**
     * Slugs usable in the tenant URL segment: lowercase, and never exactly
     * "api", which is reserved to the API routes. The negative lookahead
     * works without anchors, as Symfony route requirements strip them.
     */
    public const SLUG_PATTERN = '(?!api(?:/|$))[a-z0-9\-_]+';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'protected',
        'pending_delete',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'protected' => 'boolean',
        'pending_delete' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Scope $scope): void {
            $scope->enforceLifecycleGuardrailsOnSaving();
        });

        static::deleting(function (Scope $scope): bool {
            return $scope->scheduleDeletionRequestOnDelete();
        });
    }

    public function getCurrentTenantLabel(): string
    {
        return __('Active scope');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Limit the query to the scopes visible to $viewer: super admins see every
     * scope, everybody else only the scopes they belong to.
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isRoot()) {
            return $query;
        }

        return $query->whereIn('scopes.id', $viewer->scopes()->select('scopes.id'));
    }

    public function enforceLifecycleGuardrailsOnSaving(): void
    {
        if ($this->exists && $this->protected) {
            if ($this->isDirty('is_active') && ! $this->is_active) {
                throw new LogicException('Protected scopes cannot be deactivated.');
            }

            $dirtyProtectedFields = collect(array_keys($this->getDirty()))
                ->reject(fn (string $field): bool => in_array($field, ['name', 'updated_at'], true))
                ->values()
                ->all();

            if ($dirtyProtectedFields !== []) {
                throw new LogicException('For protected scopes only the name can be changed.');
            }
        }

        if ($this->is_active) {
            $this->pending_delete = null;
        }
    }

    public function scheduleDeletionRequestOnDelete(): bool
    {
        if ($this->protected) {
            throw new LogicException('Protected scopes cannot be deleted.');
        }

        if ($this->is_active) {
            throw new LogicException('Active scopes cannot be deleted. Deactivate the scope first.');
        }

        if (blank($this->pending_delete)) {
            $graceHours = (int) config('scopes.delete_grace_hours', 24);

            $this->forceFill([
                'pending_delete' => now()->addHours($graceHours),
            ])->saveQuietly();
        }

        // Block physical delete: this model event transforms delete into a delete-request.
        return false;
    }
}
