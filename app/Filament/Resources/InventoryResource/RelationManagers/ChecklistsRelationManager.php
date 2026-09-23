<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Enums\ChecklistResponseType;
use App\Filament\Forms\ChecklistForm;
use App\Models\ChecklistResult;
use App\Models\TaskInventory;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Checklists filled for the inventory in the tasks, with their readings.
 */
class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'task_inventories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Checklists');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)
                ->schema([
                    TextEntry::make('task.description')
                        ->label('Task')
                        ->translateLabel(),
                    TextEntry::make('task.checklist_template.name')
                        ->label('Checklist template')
                        ->translateLabel(),
                    TextEntry::make('completed_at')
                        ->translateLabel()
                        ->dateTime(config('app.datetime_format'))
                        ->placeholder(__('To do')),
                    TextEntry::make('completed_by_user.name')
                        ->label('Completed by')
                        ->translateLabel(),
                    TextEntry::make('position_path')
                        ->label('Position in the task')
                        ->translateLabel(),
                    TextEntry::make('note')
                        ->translateLabel(),
                ]),
            RepeatableEntry::make('results')
                ->label('Items')
                ->translateLabel()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('label')
                                ->hiddenLabel()
                                ->weight('bold'),
                            TextEntry::make('value')
                                ->hiddenLabel()
                                ->badge()
                                ->color(fn (ChecklistResult $record): string => $record->is_anomaly ? 'danger' : 'gray')
                                ->formatStateUsing(fn (ChecklistResult $record): string => $record->formattedValue())
                                ->placeholder('—'),
                            IconEntry::make('is_anomaly')
                                ->label('Anomaly')
                                ->translateLabel()
                                ->boolean()
                                ->trueColor('danger')
                                ->falseColor('success')
                                ->trueIcon('heroicon-o-exclamation-triangle')
                                ->falseIcon('heroicon-o-check-circle')
                                ->hiddenLabel(),
                            TextEntry::make('note')
                                ->hiddenLabel()
                                ->columnSpanFull()
                                ->visible(fn (ChecklistResult $record): bool => filled($record->note)),
                            ImageEntry::make('photos')
                                ->hiddenLabel()
                                ->disk(ChecklistForm::PHOTOS_DISK)
                                ->visibility('private')
                                ->columnSpanFull()
                                ->visible(fn (ChecklistResult $record): bool => filled($record->photos)),
                        ]),
                ])
                ->columnSpanFull(),
            ImageEntry::make('photos')
                ->label('Photos')
                ->translateLabel()
                ->disk(ChecklistForm::PHOTOS_DISK)
                ->visibility('private')
                ->columnSpanFull()
                ->visible(fn (TaskInventory $record): bool => filled($record->photos)),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position_path')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with(['task.checklist_template', 'completed_by_user', 'results'])
                    ->whereHas('task', fn (Builder $tasks): Builder => $tasks->whereNotNull('checklist_template_id'));

                if (! auth()->user()->isAdmin()) {
                    $query->whereHas('task', fn (Builder $tasks): Builder => $tasks->where('user_id', auth()->user()->id));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('completed_at')
                    ->translateLabel()
                    ->dateTime(config('app.datetime_format'))
                    ->placeholder(__('To do'))
                    ->sortable(),
                TextColumn::make('task.description')
                    ->label('Task')
                    ->translateLabel()
                    ->description(fn (TaskInventory $record): ?string => $record->task?->checklist_template?->name)
                    ->wrap(),
                TextColumn::make('outcome')
                    ->label('Checklist')
                    ->translateLabel()
                    ->badge()
                    ->state(fn (TaskInventory $record): string => match (true) {
                        ! $record->isCompleted() => __('To do'),
                        $record->has_anomalies => __('Anomalies'),
                        default => __('OK'),
                    })
                    ->color(fn (TaskInventory $record): string => match (true) {
                        ! $record->isCompleted() => 'gray',
                        $record->has_anomalies => 'danger',
                        default => 'success',
                    }),
                TextColumn::make('readings')
                    ->label('Readings')
                    ->translateLabel()
                    ->state(fn (TaskInventory $record): array => $record->results
                        ->filter(fn (ChecklistResult $result): bool => $result->response_type === ChecklistResponseType::Number && filled($result->value))
                        ->map(fn (ChecklistResult $result): string => "{$result->label}: {$result->formattedValue()}")
                        ->values()
                        ->all())
                    ->listWithLineBreaks(),
                TextColumn::make('completed_by_user.name')
                    ->label('Completed by')
                    ->translateLabel()
                    ->toggleable(),
                TextColumn::make('position_path')
                    ->label('Position in the task')
                    ->translateLabel()
                    ->toggleable(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->filters([
                TernaryFilter::make('has_anomalies')
                    ->label('Anomalies')
                    ->translateLabel(),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn (TaskInventory $record): string => __('Checklist').': '.$record->task?->description),
            ]);
    }
}
