<?php

namespace App\Services\Reorders;

use App\Models\Reorder;
use Illuminate\Database\Eloquent\Builder;

class ReorderEvaluatorService
{
    public function baseQuery(): Builder
    {
        return Reorder::query()->with('stock');
    }

    public function critical(): Builder
    {
        return $this->baseQuery()
            ->join('stocks', 'stocks.id', '=', 'reorders.stock_id')
            ->whereColumn('stocks.stock', '<', 'reorders.reorder_point')
            ->select('reorders.*');
    }

    public function warning(): Builder
    {
        return $this->baseQuery()
            ->join('stocks', 'stocks.id', '=', 'reorders.stock_id')
            ->whereColumn('stocks.stock', '=', 'reorders.reorder_point')
            ->select('reorders.*');
    }

    public function ok(): Builder
    {
        return $this->baseQuery()
            ->join('stocks', 'stocks.id', '=', 'reorders.stock_id')
            ->whereColumn('stocks.stock', '>', 'reorders.reorder_point')
            ->select('reorders.*');
    }
}
