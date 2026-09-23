<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\Pages\ViewTask;
use App\Models\ChecklistResult;
use App\Models\TaskInventory;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inventories whose latest checklist found anomalies, to decide what to do
 * next. Non admin users see only the checklists of their tasks.
 */
class ChecklistAnomaliesWidget extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Anomalies found by the latest checklist'))
            ->query(fn (): Builder => TaskInventory::query()
                ->withOpenAnomalies()
                ->whereHas('task', function (Builder $tasks): Builder {
                    $tasks->where('scope_id', filament()->getTenant()?->getKey());

                    return auth()->user()->isAdmin() ? $tasks : $tasks->where('user_id', auth()->id());
                })
                ->with(['inventory', 'task', 'completed_by_user', 'results' => fn ($results) => $results->where('is_anomaly', true)]))
            ->defaultSort('completed_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('No open anomalies'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('completed_at')
                    ->translateLabel()
                    ->dateTime(config('app.datetime_format'))
                    ->sortable(),
                TextColumn::make('inventory.summary')
                    ->label('Inventory')
                    ->translateLabel()
                    ->description(fn (TaskInventory $record): ?string => $record->position_path)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('anomalies')
                    ->label('Anomalies')
                    ->translateLabel()
                    ->state(fn (TaskInventory $record): array => $record->results
                        ->map(fn (ChecklistResult $result): string => collect([$result->label, $result->formattedValue(), $result->note])->filter()->implode(' · '))
                        ->all())
                    ->color('danger')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->wrap(),
                TextColumn::make('task.description')
                    ->label('Task')
                    ->translateLabel()
                    ->description(fn (TaskInventory $record): ?string => $record->completed_by_user?->name)
                    ->wrap(),
            ])
            ->recordUrl(fn (TaskInventory $record): string => auth()->user()->can('update', $record->task)
                ? EditTask::getUrl(['record' => $record->task])
                : ViewTask::getUrl(['record' => $record->task]));
    }
}
