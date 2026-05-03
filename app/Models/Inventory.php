<?php

namespace App\Models;

use App\Models\Concerns\BelongsToScope;
use App\Traits\HasDbCheck;
use App\Traits\PreventRelatedDeletion;
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
            $this->inventory_number = str_pad($this->id, env('INVENTORY_NUMBER_ZERO_FILL', 6), '0', STR_PAD_LEFT);
        }
        if ($save) {
            $this->saveQuietly();
        }

        return $this->inventory_number ? $this->inventory_number : '';
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

    public static function dbCheck(bool $output = false): void
    {
        if ($output) {
            self::info('Syncing inventory summary');
        }

        $inventories = Inventory::orderBy('id')->get();
        $inventories->each(function (Inventory $inventory) {
            $inventory->syncSummary(true);
        });
        self::info('Inventory summary updated successfully');
        $movementsItems = MovementItem::orderBy('id')->get();
        $movementsItems->each(function (MovementItem $movementItem) {
            $movementItem->save();
        });
        self::info('Movements summary updated successfully');
        $stocks = Stock::orderBy('id')->get();
        $stocks->each(function (Stock $stock) {
            $stock->save();
        });
        self::info('Stock summary updated successfully');
    }
}
