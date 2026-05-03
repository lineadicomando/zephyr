<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Reorder;
use App\Models\Movement;
use App\Models\Inventory;
use App\Models\TaskStatus;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Log;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{

    protected static ?int $sort = 1;
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $stats = [];

        // Last Inventory Update - Movements stats
        if (auth()->user()->can('view', Inventory::class)) {
            $product = Product::orderBy('updated_at', 'desc')->first();
            $inventory = Inventory::orderBy('updated_at', 'desc')->first();
            $latestRecordsUpdateAt = collect([
                $product?->updated_at,
                $inventory?->updated_at,
            ])->filter()->sortDesc()->first();

            $latestUpdateDescription = $latestRecordsUpdateAt
                ? __('Last update of records: :date', ['date' => $latestRecordsUpdateAt->format('d/m/Y')])
                : __('No records available');

            $movementQuery =  Movement::query();

            $movementData = Trend::model(Movement::class)
                ->query($movementQuery)
                ->dateColumn("date")
                ->between(
                    start: now()->subMonth(12),
                    end: now(),
                )
                ->perMonth()
                ->count();
            $movementDataCollection = $movementData->map(fn (TrendValue $value) => $value->aggregate);
            $completedMovementDataArray = $movementDataCollection->toArray();

            $latestMovement = Movement::latest('date')->first();
            $formattedDate = $latestMovement
                ? \Carbon\Carbon::parse($latestMovement->date)->format('d/m/Y')
                : '--';

            $stats[] = Stat::make(__('Latest inventory update (movements)'), $formattedDate)
            ->description($latestUpdateDescription)
            ->chart($completedMovementDataArray)
            ->color('success');
        }

        // TASKS

        if (auth()->user()->can('view', Task::class)) {

            $taskTable = app(Task::class)->getTable();
            $taskStatusesTable = app(TaskStatus::class)->getTable();
            $taskQuery =  Task::join('task_statuses', 'task_statuses.id', '=', 'task_status_id')
                ->where('task_statuses.completed', true);


            if (!auth()->user()->isAdmin()) {
                $taskQuery->where('user_id', auth()->user()->id);
            }

            $subMonts = 6;
            $taskData = Trend::model(Task::class)
                ->query($taskQuery)
                ->dateColumn("{$taskTable}.created_at")
                ->between(
                    start: now()->subMonth($subMonts),
                    end: now(),
                )
                ->perDay()
                ->count();
            $taskDataCollection = $taskData->map(fn (TrendValue $value) => $value->aggregate);
            $completedTaskDataArray = $taskDataCollection->toArray();
            $completedTaskDataSum = array_sum($completedTaskDataArray);


            $taskStatusesTable = app(TaskStatus::class)->getTable();
            $taskQuery =  Task::join('task_statuses', 'task_statuses.id', '=', 'task_status_id')
                ->where('task_statuses.completed', false);
            if (!auth()->user()->isAdmin()) {
                $taskQuery->where('user_id', auth()->user()->id);
            }


            $stats[] = Stat::make(__('Open tasks'), $taskQuery->count())
                ->description(__('Tasks completed in the last :last months: :completed.', [
                    'last' => $subMonts,
                    'completed' => $completedTaskDataSum,
                ]))
                ->chart($completedTaskDataArray)
                ->color('success');

        }

        // REORDERS

        $stockTable = app(Stock::class)->getTable();
        $reorderQueryTotal =  Reorder::query()->count();
        $reorderQueryAlert =  Reorder::join($stockTable, $stockTable . '.id', '=', 'stock_id')
            ->whereColumn("{$stockTable}.stock", '<', 'reorder_point')->count();
        $reorderQueryWarning =  Reorder::join($stockTable, $stockTable . '.id', '=', 'stock_id')
            ->whereColumn("{$stockTable}.stock", '=', 'reorder_point')->count();
        $reorderQueryColor = 'info';
        $reorderDescription = __('Total items watched for reorder :total.', ['total' => $reorderQueryTotal]);
        $reorderQueryIcon = 'heroicon-m-check';
        if ($reorderQueryWarning > 0) {
            $reorderQueryColor = 'warning';
            $reorderQueryIcon = 'heroicon-m-exclamation-circle';
            $reorderDescription = __('Total items watched for reorder :total, of which :warning are running low.', [
                'total' => $reorderQueryTotal,
                'warning' => $reorderQueryWarning,
            ]);
        }
        if ($reorderQueryAlert > 0) {
            $reorderQueryIcon = 'heroicon-m-exclamation-triangle';
            $reorderQueryColor = 'danger';
        }

        if (auth()->user()->can('view', Reorder::class)) {
            $stats[] = Stat::make(__('Orders to place'), $reorderQueryAlert)
                ->description($reorderDescription)
                ->descriptionIcon($reorderQueryIcon)
                ->color($reorderQueryColor);
        }


        return $stats;
    }
}
