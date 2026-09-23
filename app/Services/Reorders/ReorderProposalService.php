<?php

namespace App\Services\Reorders;

use App\Models\Reorder;
use App\Models\ReorderOrder;
use App\Models\ReorderOrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReorderProposalService
{
    public function __construct(private readonly ReorderEvaluatorService $evaluator) {}

    /**
     * Create a draft order in the scope (by default the current tenant) with
     * the critical reorder rules whose stock is not already in an open order,
     * or return null when there is none.
     *
     * @throws InvalidArgumentException when no scope is given and the critical rules belong to several scopes
     */
    public function createDraftFromCritical(?int $userId = null, ?int $scopeId = null): ?ReorderOrder
    {
        $scopeId ??= filament()->getTenant()?->getKey();

        /** @var Collection<int, Reorder> $criticalRules */
        $criticalRules = $this->evaluator->critical()
            ->with('stock')
            ->when($scopeId !== null, fn (Builder $query) => $query->where('reorders.scope_id', $scopeId))
            ->whereNotIn('reorders.stock_id', ReorderOrderItem::query()
                ->whereHas('reorderOrder', fn (Builder $query) => $query->whereIn('status', ReorderOrder::OPEN_STATUSES))
                ->select('stock_id'))
            ->get();

        if ($criticalRules->isEmpty()) {
            return null;
        }

        if ($criticalRules->pluck('scope_id')->unique()->count() > 1) {
            throw new InvalidArgumentException('The critical reorder rules belong to several scopes: pass the scope of the order.');
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
