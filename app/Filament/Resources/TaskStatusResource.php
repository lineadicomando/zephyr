<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskStatusResource\Pages;
use App\Filament\Resources\TaskStatusResource\RelationManagers;
use App\Models\TaskStatus;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskStatusResource extends Resource
{
    protected static ?string $model = TaskStatus::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-check-circle";
    protected static ?string $recordTitleAttribute = "name";
    protected static string|\UnitEnum|null $navigationGroup = "Tasks";

    // protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getNavigationLabel(): string
    {
        return __("Statuses");
    }

    public static function getModelLabel(): string
    {
        return __("Task status");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Task statuses");
    }

    public static function getFormDefinition()
    {
        return [
            TextInput::make("name")
                ->translateLabel()
                ->required()
                ->unique(ignoreRecord: true),
            // ColorPicker::make('color')
            //     ->translateLabel(),
            Select::make("color")
                ->required()
                ->translateLabel()
                ->options([
                    "danger" => "danger",
                    "gray" => "gray",
                    "info" => "info",
                    "primary" => "primary",
                    "success" => "success",
                    "warning" => "warning",
                ]),
            TextInput::make("icon")->translateLabel(),
            Fieldset::make("Meta")->schema([
                Group::make()->schema([
                    TextInput::make("order")->numeric()->translateLabel(),
                ]),
                Group::make()
                    ->schema([
                        Checkbox::make("default")->translateLabel(),
                        Checkbox::make("completed")->translateLabel(),
                    ])
                    ->columns(1),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")
                    ->label("#")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("name")
                    ->badge()
                    ->color(
                        fn(
                            string $state,
                            TaskStatus $taskStatus,
                        ) => $taskStatus->color,
                    )
                    ->icon(
                        fn(
                            string $state,
                            TaskStatus $taskStatus,
                        ) => $taskStatus->icon,
                    )
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make("default")->translateLabel(),
                IconColumn::make("default")
                    ->icon(
                        fn(string $state): string => match ($state) {
                            "1" => "heroicon-o-check",
                            "0" => "heroicon-o-x-mark",
                        },
                    )
                    ->color(
                        fn(string $state): string => match ($state) {
                            "1" => "success",
                            "0" => "gray",
                        },
                    ),
                TextColumn::make("created_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort("order", "asc")
            ->persistSearchInSession()
            ->filters([
                //
            ])
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can("update", TaskStatus::class)) {
                    return Pages\EditTaskStatus::getUrl([$record->id]);
                }
                return Pages\ViewTaskStatus::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListTaskStatuses::route("/"),
            "create" => Pages\CreateTaskStatus::route("/create"),
            "edit" => Pages\EditTaskStatus::route("/{record}/edit"),
            "view" => Pages\ViewTaskStatus::route("/{record}/view"),
        ];
    }
}
