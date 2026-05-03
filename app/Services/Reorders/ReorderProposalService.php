<?php

namespace App\Services\Reorders;

use App\Models\Reorder;
use App\Models\ReorderOrder;
use Illuminate\Support\Collection;

class ReorderProposalService
{
    public function __construct(private readonly ReorderEvaluatorService $evaluator)
    {
    }

    public function createDraftFromCritical(?int $userId = null): ReorderOrder
    {
        $order = ReorderOrder::query()->create([
            'status' => ReorderOrder::STATUS_DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        /** @var Collection<int, Reorder> $criticalRules */
        $criticalRules = $this->evaluator->critical()->with('stock')->get();

        foreach ($criticalRules as $rule) {
            $currentStock = (int) ($rule->stock?->stock ?? 0);
            $fallbackQty = max(1, (int) $rule->reorder_point - $currentStock);

            $order->items()->create([
                'stock_id' => $rule->stock_id,
                'reorder_id' => $rule->id,
                'current_stock' => $currentStock,
                'reorder_point' => (int) $rule->reorder_point,
                'suggested_qty' => (int) ($rule->reorder_quantity ?: $fallbackQty),
                'ordered_qty' => null,
                'received_qty' => null,
                'last_reorder_date' => $rule->last_reorder_date,
            ]);
        }

        return $order->load('items.stock', 'items.reorder');
    }
}
