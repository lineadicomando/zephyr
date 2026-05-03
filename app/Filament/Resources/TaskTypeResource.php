<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskTypeResource\Pages;
use App\Filament\Resources\TaskTypeResource\RelationManagers;
use App\Models\TaskType;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskTypeResource extends Resource
{
    protected static ?string $model = TaskType::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-tag";
    protected static ?string $recordTitleAttribute = "name";
    protected static string|\UnitEnum|null $navigationGroup = "Tasks";

    // protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getNavigationLabel(): string
    {
        return __("Types");
    }

    public static function getModelLabel(): string
    {
        return __("Task type");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Task types");
    }

    public static function getFormDefinition()
    {
        return [
            TextInput::make("name")
                ->required()
                ->translateLabel()
                ->unique(ignoreRecord: true),
            Fieldset::make(__("Chart"))
                ->schema([
                    Checkbox::make("chart")->translateLabel()->default(false),
                    ColorPicker::make("chart_color")
                        ->default("#ffffff")
                        ->translateLabel(),
                ])
                ->columns(1),
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
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                ColorColumn::make("chart_color")->translateLabel(),
                CheckboxColumn::make("chart")->translateLabel(),
                TextColumn::make("created_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistSearchInSession()
            ->filters([
                //
            ])
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can("update", TaskType::class)) {
                    return Pages\EditTaskType::getUrl([$record->id]);
                }
                return Pages\ViewTaskType::getUrl([$record->id]);
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
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListTaskTypes::route("/"),
            "create" => Pages\CreateTaskType::route("/create"),
            "edit" => Pages\EditTaskType::route("/{record}/edit"),
            "view" => Pages\ViewTaskType::route("/{record}/view"),
        ];
    }
}
