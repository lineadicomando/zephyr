<?php

namespace App\Filament\Resources;

use App\Contracts\ScopeContext;
use App\Filament\Resources\MovementTypeResource\Pages;
use App\Filament\Resources\MovementTypeResource\RelationManagers;
use App\Models\MovementType;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class MovementTypeResource extends Resource
{
    protected static ?string $model = MovementType::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-tag";
    protected static ?string $recordTitleAttribute = "name";
    protected static string|\UnitEnum|null $navigationGroup = "Movements";

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
        return __("Movement type");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Movement types");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make("name")
                    ->required()
                    ->translateLabel()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn(Unique $rule) => $rule->where(
                            "scope_id",
                            app(ScopeContext::class)->activeScopeId(),
                        ),
                    ),
                Fieldset::make(__("Chart"))
                    ->schema([
                        Checkbox::make("chart")
                            ->translateLabel()
                            ->default(false),
                        ColorPicker::make("chart_color")
                            ->default("#ffffff")
                            ->translateLabel(),
                    ])
                    ->columns(1),
            ])
            ->columns(1);
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
                ColorColumn::make("chart_color")
                    ->default("#ffffff")
                    ->translateLabel(),
                CheckboxColumn::make("chart")->translateLabel(),
                // IconColumn::make('chart')
                //     ->translateLabel()
                //     ->icon(fn (string $state): string => match ($state) {
                //         '1' => 'heroicon-o-check',
                //         '0' => 'heroicon-o-x-mark',
                //     })
                //     ->color(fn (string $state): string => match ($state) {
                //         '1' => 'success',
                //         '0' => 'gray',
                //     }),
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
                if (auth()->user()->can("update", MovementType::class)) {
                    return Pages\EditMovementType::getUrl([$record->id]);
                }
                return Pages\ViewMovementType::getUrl([$record->id]);
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
            "index" => Pages\ListMovementTypes::route("/"),
            "create" => Pages\CreateMovementType::route("/create"),
            "edit" => Pages\EditMovementType::route("/{record}/edit"),
            "view" => Pages\ViewMovementType::route("/{record}/view"),
        ];
    }
}
