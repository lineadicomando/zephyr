<?php

namespace App\Filament\Resources\MovementResource\Widgets;

use App\Models\Movement;
use App\Models\MovementType;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;

class MovementChart extends ChartWidget
{
    protected ?string $heading = 'Movements';

    protected static ?int $sort = 3;

    public function getHeading(): string | Htmlable | null
    {
        return __($this->heading ?? '');
    }


    protected function getData(): array
    {
        $movementTypeTable = app(MovementType::class)->getTable();
        $movementTypes = MovementType::where('chart', true)->get();
        $datasets = [];
        $labels = [];
        foreach ($movementTypes as $movementType) {
            $data = Trend::model(Movement::class)
                ->query(Movement::where('movement_type_id', $movementType->id))
                ->dateColumn('date')
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
                'label' => $movementType->name,
                'backgroundColor' => $movementType->chart_color,
                'borderColor' => $movementType->chart_color,
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
        return 'bar';
    }
}
