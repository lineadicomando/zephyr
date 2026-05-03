<?php

namespace App\Filament\Resources\TaskResource\Widgets;

use App\Models\Task;
use App\Models\TaskType;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Contracts\Support\Htmlable;

class TaskChart extends ChartWidget
{
    protected ?string $heading = 'Tasks';

    protected static ?int $sort = 3;

    protected string $color = 'info';

    public function getHeading(): string | Htmlable | null
    {
        return __($this->heading ?? '');
    }

    protected function getData(): array
    {
        $movementTypeTable = app(TaskType::class)->getTable();
        $taskTypes = TaskType::where('chart', true)->get();
        $datasets = [];
        $labels = [];

        foreach ($taskTypes as $taskType) {

            $query = Task::where('task_type_id', $taskType->id);
            $authUser = auth()->user();
            if (!($authUser?->isAdmin())) {
                $query->where('user_id', $authUser?->id);
            }

            $data = Trend::model(Task::class)
                ->query($query)
                ->dateColumn('created_at')
                ->dateAlias('d')
                ->between(
                    start: now()->subMonths(12),
                    end: now(),
                )
                ->perMonth()
                ->count();
            if ($labels === []) {
                $labels = $data->map(fn (TrendValue $value) => $value->date);
            }
            $datasets[] =                [
                'label' => $taskType->name,
                'backgroundColor' => $taskType->chart_color,
                'borderColor' => $taskType->chart_color,
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
