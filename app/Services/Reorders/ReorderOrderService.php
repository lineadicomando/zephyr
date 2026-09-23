<?php

namespace App\Services\Reorders;

use App\Models\ReorderOrder;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderOrderService
{
    public function request(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        return $this->transition($order, [ReorderOrder::STATUS_DRAFT], [
            'status' => ReorderOrder::STATUS_REQUESTED,
            'requested_at' => now(),
            'updated_by' => $userId,
        ]);
    }

    public function markOrdered(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        return $this->transition($order, [ReorderOrder::STATUS_REQUESTED], [
            'status' => ReorderOrder::STATUS_ORDERED,
            'ordered_at' => now(),
            'updated_by' => $userId,
        ]);
    }

    public function markReceived(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        return $this->transition($order, [ReorderOrder::STATUS_ORDERED], [
            'status' => ReorderOrder::STATUS_RECEIVED,
            'received_at' => now(),
            'updated_by' => $userId,
        ], function (ReorderOrder $lockedOrder): void {
            $lockedOrder->loadMissing('items.reorder');
            foreach ($lockedOrder->items as $item) {
                if ($item->reorder) {
                    $item->reorder->update(['last_reorder_date' => now()]);
                }
            }
        });
    }

    public function cancel(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        return $this->transition($order, [
            ReorderOrder::STATUS_DRAFT,
            ReorderOrder::STATUS_REQUESTED,
            ReorderOrder::STATUS_ORDERED,
        ], [
            'status' => ReorderOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'updated_by' => $userId,
        ]);
    }

    /**
     * Apply the transition in a transaction, checking the status of the
     * locked row: a stale $order or a concurrent transition cannot move the
     * order from a status it has already left, and a failure in $after rolls
     * the whole transition back.
     *
     * @param  array<int, string>  $allowedFrom
     * @param  array<string, mixed>  $attributes
     * @param  (Closure(ReorderOrder): void)|null  $after
     */
    private function transition(ReorderOrder $order, array $allowedFrom, array $attributes, ?Closure $after = null): ReorderOrder
    {
        DB::transaction(function () use ($order, $allowedFrom, $attributes, $after): void {
            $lockedOrder = ReorderOrder::query()->lockForUpdate()->findOrFail($order->getKey());

            $this->assertTransition($lockedOrder, $allowedFrom);

            $lockedOrder->update($attributes);

            if ($after) {
                $after($lockedOrder);
            }
        });

        return $order->refresh();
    }

    private function assertTransition(ReorderOrder $order, array $allowedFrom): void
    {
        if (! in_array($order->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => __('Invalid reorder order transition from :status', ['status' => $order->status]),
            ]);
        }
    }
}
