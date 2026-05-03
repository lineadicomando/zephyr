<?php

namespace App\Services\Reorders;

use App\Models\ReorderOrder;
use Illuminate\Validation\ValidationException;

class ReorderOrderService
{
    public function request(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        $this->assertTransition($order, [ReorderOrder::STATUS_DRAFT]);

        $order->update([
            'status' => ReorderOrder::STATUS_REQUESTED,
            'requested_at' => now(),
            'updated_by' => $userId,
        ]);

        return $order->refresh();
    }

    public function markOrdered(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        $this->assertTransition($order, [ReorderOrder::STATUS_REQUESTED]);

        $order->update([
            'status' => ReorderOrder::STATUS_ORDERED,
            'ordered_at' => now(),
            'updated_by' => $userId,
        ]);

        return $order->refresh();
    }

    public function markReceived(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        $this->assertTransition($order, [ReorderOrder::STATUS_ORDERED]);

        $order->update([
            'status' => ReorderOrder::STATUS_RECEIVED,
            'received_at' => now(),
            'updated_by' => $userId,
        ]);

        $order->loadMissing('items.reorder');
        foreach ($order->items as $item) {
            if ($item->reorder) {
                $item->reorder->update(['last_reorder_date' => now()]);
            }
        }

        return $order->refresh();
    }

    public function cancel(ReorderOrder $order, ?int $userId = null): ReorderOrder
    {
        $this->assertTransition($order, [
            ReorderOrder::STATUS_DRAFT,
            ReorderOrder::STATUS_REQUESTED,
            ReorderOrder::STATUS_ORDERED,
        ]);

        $order->update([
            'status' => ReorderOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'updated_by' => $userId,
        ]);

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
