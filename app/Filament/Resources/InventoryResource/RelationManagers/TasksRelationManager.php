<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Tasks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema(TaskResource::getFormDefinition());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('starts_at')
                    ->date('Y-m-d')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->date('Y-m-d')
                    ->translateLabel()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('task_status.name')
                    ->badge()
                    ->color(fn (string $state, Task $task) => $task->task_status->color)
                    ->icon(fn (string $state, Task $task) => $task->task_status->icon)
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('task_type.name')
                    ->translateLabel()
                    ->sortable(),
                TextColumn::make('description')
                    ->sortable()
                    ->searchable()
                    ->translateLabel(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DetachAction::make(),
                // \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
