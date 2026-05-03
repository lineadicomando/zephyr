<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

class Scope extends Model
{
    use HasFactory;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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
