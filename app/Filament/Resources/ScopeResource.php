<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScopeResource\Pages;
use App\Models\Scope;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScopeResource extends Resource
{
    protected static ?string $model = Scope::class;

    protected static string|\BackedEnum|null $navigationIcon = "heroicon-o-circle-stack";
    protected static string|\UnitEnum|null $navigationGroup = "Users";
    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return __("Scope");
    }

    public static function getPluralModelLabel(): string
    {
        return __("Scopes");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make("name")
                ->translateLabel()
                ->required()
                ->maxLength(255),
            TextInput::make("slug")
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->disabled(
                    fn(?Scope $record): bool => (bool) $record?->protected,
                )
                ->unique(ignoreRecord: true),
            Select::make("type")
                ->translateLabel()
                ->required()
                ->disabled(
                    fn(?Scope $record): bool => (bool) $record?->protected,
                )
                ->options([
                    "company" => "company",
                    "school" => "school",
                    "branch" => "branch",
                    "team" => "team",
                    "department" => "department",
                    "other" => "other",
                ])
                ->default("company"),
            Toggle::make("is_active")
                ->translateLabel()
                ->disabled(
                    fn(?Scope $record): bool => (bool) $record?->protected,
                )
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")
                    ->label("#")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("name")
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make("slug")->searchable()->sortable(),
                TextColumn::make("type")->badge()->sortable(),
                IconColumn::make("is_active")
                    ->label(__("Active"))
                    ->boolean()
                    ->sortable(),
                IconColumn::make("protected")
                    ->label(__("Protected"))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("pending_delete")
                    ->label(__("Pending delete"))
                    ->dateTime()
                    ->sortable()
                    ->placeholder("-"),
                TextColumn::make("users_count")
                    ->counts("users")
                    ->label(__("Users")),
                TextColumn::make("created_at")
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make("type")->options([
                    "company" => "company",
                    "school" => "school",
                    "branch" => "branch",
                    "team" => "team",
                    "department" => "department",
                    "other" => "other",
                ]),
                SelectFilter::make("is_active")->options([
                    "1" => __("Active"),
                    "0" => __("Inactive"),
                ]),
            ])
            ->recordUrl(function ($record) {
                if (auth()->user()->can("update", $record)) {
                    return Pages\EditScope::getUrl([$record->id]);
                }

                return Pages\ViewScope::getUrl([$record->id]);
            })
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                Action::make("requestDeletion")
                    ->label(__("Request deletion"))
                    ->icon("heroicon-o-trash")
                    ->color("danger")
                    ->requiresConfirmation()
                    ->action(function (Scope $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title(__("Deletion requested"))
                                ->body(__("Scope deletion has been scheduled."))
                                ->send();
                        } catch (\Throwable $throwable) {
                            Notification::make()
                                ->danger()
                                ->title(__("Deletion request failed"))
                                ->body(__($throwable->getMessage()))
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListScopes::route("/"),
            "create" => Pages\CreateScope::route("/create"),
            "edit" => Pages\EditScope::route("/{record}/edit"),
            "view" => Pages\ViewScope::route("/{record}/view"),
        ];
    }
}
