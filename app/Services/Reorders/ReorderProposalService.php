<?php

namespace App\Services\Reorders;

use App\Models\Reorder;
use App\Models\ReorderOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderProposalService
{
    public function __construct(private readonly ReorderEvaluatorService $evaluator) {}

    /**
     * Create a draft order with the critical reorder rules, or return null
     * when no rule is critical.
     */
    public function createDraftFromCritical(?int $userId = null): ?ReorderOrder
    {
        /** @var Collection<int, Reorder> $criticalRules */
        $criticalRules = $this->evaluator->critical()->with('stock')->get();

        if ($criticalRules->isEmpty()) {
            return null;
        }

        return DB::transaction(fn (): ReorderOrder => $this->createDraft($criticalRules, $userId));
    }

    /**
     * @param  Collection<int, Reorder>  $criticalRules
     */
    protected function createDraft(Collection $criticalRules, ?int $userId): ReorderOrder
    {
        $scopeId = $criticalRules->first()?->scope_id;

        $order = ReorderOrder::query()->create([
            'scope_id' => $scopeId,
            'status' => ReorderOrder::STATUS_DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        foreach ($criticalRules as $rule) {
            $currentStock = (int) ($rule->stock?->stock ?? 0);
            $fallbackQty = max(1, (int) $rule->reorder_point - $currentStock);

            $order->items()->create([
                'scope_id' => $rule->scope_id,
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
