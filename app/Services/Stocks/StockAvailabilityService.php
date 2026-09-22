<?php

namespace App\Services\Stocks;

use App\Models\MovementItem;
use App\Models\Stock;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAvailabilityService
{
    /**
     * Quantity of the inventory currently available at the given position,
     * computed from the movement items. When $lock is true the stock row is
     * locked for the rest of the current transaction.
     */
    public function availableQuantity(int $inventoryId, int $positionId, bool $lock = false): int
    {
        $stock = Stock::query()
            ->where('inventory_id', $inventoryId)
            ->where('inventory_position_id', $positionId)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();

        if ($stock === null) {
            return 0;
        }

        $incoming = (int) MovementItem::query()->where('incoming_stock_id', $stock->id)->sum('stock');
        $outcoming = (int) MovementItem::query()->where('outcoming_stock_id', $stock->id)->sum('stock');

        return $incoming - $outcoming;
    }

    /**
     * Determine whether $quantity can be withdrawn from the position.
     * $alreadyWithdrawn is the quantity the edited movement item already
     * takes from the same position, which is released by the edit.
     */
    public function canWithdraw(int $inventoryId, ?int $positionId, int $quantity, int $alreadyWithdrawn = 0, bool $lock = false): bool
    {
        if ($positionId === null) {
            return true;
        }

        return $quantity <= $this->availableQuantity($inventoryId, $positionId, $lock) + $alreadyWithdrawn;
    }

    /**
     * @throws ValidationException
     */
    public function ensureCanWithdraw(int $inventoryId, ?int $positionId, int $quantity, int $alreadyWithdrawn = 0): void
    {
        if (! $this->canWithdraw($inventoryId, $positionId, $quantity, $alreadyWithdrawn, lock: true)) {
            throw ValidationException::withMessages([
                'stock' => __('Insufficient availability, impossible to proceed'),
            ]);
        }
    }

    /**
     * Run $callback in a transaction after locking the source stock and
     * verifying that $quantity can still be withdrawn from it.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     *
     * @throws ValidationException
     */
    public function withdraw(int $inventoryId, ?int $positionId, int $quantity, Closure $callback, int $alreadyWithdrawn = 0): mixed
    {
        return DB::transaction(function () use ($inventoryId, $positionId, $quantity, $callback, $alreadyWithdrawn): mixed {
            $this->ensureCanWithdraw($inventoryId, $positionId, $quantity, $alreadyWithdrawn);

            return $callback();
        });
    }
}
