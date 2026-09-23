<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Movement extends Model
{
    use BelongsToScope;
    use HasFactory;
    use PreventRelatedDeletion {
        delete as deleteUnlessRelated;
    }

    protected $fillable = [
        'scope_id',
        'date',
        'movement_type_id',
        'from_inventory_location_id',
        'from_inventory_position_id',
        'to_inventory_location_id',
        'to_inventory_position_id',
        'description',
        'note',
    ];

    /**
     * Save in a transaction together with the movement items, whose stock
     * update may reject the change.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    /**
     * Delete in a transaction together with the movement items, whose stock
     * update may reject the change.
     */
    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => $this->deleteUnlessRelated());
    }

    protected static function booted(): void
    {
        static::saving(fn (Movement $movement) => $movement->onSaving());
        static::saved(fn (Movement $movement) => $movement->onSaved());
        static::deleting(fn (Movement $movement) => $movement->onDeleted());
    }

    /**
     * Each side of the movement is either empty or a position: the location
     * is always the one of the position, and a location without a position
     * stands for its default position. So every stock has a position.
     */
    public function onSaving()
    {
        foreach (['from', 'to'] as $side) {
            $positionKey = "{$side}_inventory_position_id";
            $locationKey = "{$side}_inventory_location_id";

            if (blank($this->{$positionKey}) && filled($this->{$locationKey})) {
                $this->{$positionKey} = InventoryLocation::query()->find($this->{$locationKey})?->defaultPosition()->getKey();
            }

            $this->{$locationKey} = filled($this->{$positionKey})
                ? InventoryPosition::query()->whereKey($this->{$positionKey})->value('inventory_location_id')
                : null;
        }
    }

    public function onSaved()
    {
        $this->movement_items->each(function (MovementItem $movementItem) {
            $movementItem->save();
        });
    }

    public function onDeleted()
    {
        $this->movement_items->each(function (MovementItem $movementItem) {
            $movementItem->delete();
        });
    }

    /**
     * @return BelongsTo<MovementType, $this>
     */
    public function movement_type(): BelongsTo
    {
        return $this->belongsTo(MovementType::class);
    }

    /**
     * @return HasMany<MovementItem, $this>
     */
    public function movement_items(): HasMany
    {
        return $this->hasMany(MovementItem::class);
    }

    public function preventDeletionBy()
    {
        return [];
    }

    /**
     * Determine whether any inventory of this movement was moved again afterwards.
     */
    public function hasSubsequentMovements(): bool
    {
        return $this->movement_items()
            ->whereExists(fn ($query) => $query
                ->from('movement_items as later_items')
                ->whereColumn('later_items.inventory_id', 'movement_items.inventory_id')
                ->whereColumn('later_items.id', '>', 'movement_items.id')
                ->whereColumn('later_items.movement_id', '!=', 'movement_items.movement_id'))
            ->exists();
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function from_inventory_location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_inventory_location_id');
    }

    /**
     * @return BelongsTo<InventoryPosition, $this>
     */
    public function from_inventory_position(): BelongsTo
    {
        return $this->belongsTo(InventoryPosition::class, 'from_inventory_position_id');
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function to_inventory_location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_inventory_location_id');
    }

    /**
     * @return BelongsTo<InventoryPosition, $this>
     */
    public function to_inventory_position(): BelongsTo
    {
        return $this->belongsTo(InventoryPosition::class, 'to_inventory_position_id');
        // return $this->belongsTo(InventoryPosition::class, 'to_inventory_position_id')->where('default', false);
    }
}
