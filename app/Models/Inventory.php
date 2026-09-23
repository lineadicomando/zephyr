<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\HasDbCheck;
use App\Traits\PreventRelatedDeletion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use BelongsToScope;
    use HasDbCheck;
    use HasFactory;
    use PreventRelatedDeletion;

    protected static function booted(): void
    {
        static::saved(fn (Inventory $inventory) => $inventory->onSaved());
    }

    public function onSaved()
    {
        $this->autoInventoryNumber(true);
        $this->syncSummary(true);
        $this->stocks()->each(function (Stock $stock) {
            $stock->update();
        });
        $this->movement_items()->each(function (MovementItem $movementItem) {
            $movementItem->update();
        });
    }

    public function autoInventoryNumber($save = false): string
    {
        if (empty($this->inventory_number) && ! empty($this->id)) {
            $this->inventory_number = $this->nextFreeInventoryNumber();
        }
        if ($save) {
            $this->saveQuietly();
        }

        return $this->inventory_number ? $this->inventory_number : '';
    }

    /**
     * First inventory number, starting from the record id, not yet used in the scope.
     */
    protected function nextFreeInventoryNumber(): string
    {
        $sequence = (int) $this->id;

        do {
            $number = str_pad((string) $sequence++, config('app.inventory_number_zero_fill'), '0', STR_PAD_LEFT);
        } while (static::query()
            ->where('scope_id', $this->scope_id)
            ->where('inventory_number', $number)
            ->whereKeyNot($this->getKey())
            ->exists());

        return $number;
    }

    public function syncSummary($save = false): string
    {
        // $summary = [$this->inventory_number, $this->product->product_type?->name];
        $summary = [$this->inventory_number];
        if (! empty($this->product->code)) {
            $summary[] = $this->product->code;
        }
        if (! empty($this->serial_number)) {
            $summary[] = $this->serial_number;
        }
        $summary[] = $this->product->name;
        if (! empty($this->description)) {
            $summary[] = $this->description;
        }
        $this->summary = implode(' | ', $summary);
        if ($save) {
            $this->saveQuietly();
        }

        return $this->summary ? $this->summary : '';
    }

    protected $fillable = [
        'scope_id',
        'inventory_number',
        'product_id',
        'description',
        'summary',
        'serial_number',
        'mac_address',
        'url',
        'note',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movement_items()
    {
        return $this->hasMany(MovementItem::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function non_zero_stocks()
    {
        return $this->hasMany(Stock::class)->where('stock', '<>', '0');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_inventory')->withTimestamps();
    }

    public function preventDeletionBy()
    {
        return [
            'tasks',
            'stocks',
            'movement_items',
        ];
    }

    /**
     * Refresh the denormalized summaries and stock references. The movement
     * items are saved quietly, so a stored quantity that does not match the
     * movement history cannot abort the check: every stock is recalculated at
     * the end, negative values included.
     */
    public static function dbCheck(bool $output = false): void
    {
        if ($output) {
            self::info('Syncing inventory summary');
        }

        Inventory::query()->chunkById(200, function (Collection $inventories) {
            $inventories->each(fn (Inventory $inventory) => $inventory->syncSummary(true));
        });
        if ($output) {
            self::info('Inventory summary updated successfully');
        }
        MovementItem::query()->with(['movement', 'inventory'])->chunkById(200, function (Collection $movementItems) {
            $movementItems->each(function (MovementItem $movementItem) {
                $movementItem->syncStocks();
                $movementItem->syncSummary();
                $movementItem->saveQuietly();
            });
        });
        if ($output) {
            self::info('Movements summary updated successfully');
        }
        Stock::query()->chunkById(200, function (Collection $stocks) {
            $stocks->each(fn (Stock $stock) => $stock->updateStockByMovementItems(allowNegative: true));
        });
        if ($output) {
            self::info('Stock summary updated successfully');
        }
    }
}
