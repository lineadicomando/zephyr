<?php

namespace App\Filament\Resources;

use App\Enums\ChecklistResponseType;
use App\Filament\Resources\ChecklistTemplateResource\Pages;
use App\Models\ChecklistTemplate;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Tasks';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getNavigationLabel(): string
    {
        return __('Checklists');
    }

    public static function getModelLabel(): string
    {
        return __('Checklist template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Checklist templates');
    }

    public static function getFormDefinition(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->translateLabel()
                ->unique(ignoreRecord: true),
            Toggle::make('is_active')
                ->label('Active')
                ->translateLabel()
                ->default(true)
                ->inline(false),
            Textarea::make('description')
                ->translateLabel()
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Items')
                ->translateLabel()
                ->relationship()
                ->orderColumn('sort')
                ->collapsible()
                ->cloneable()
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->addActionLabel(__('Add item'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('label')
                                ->required()
                                ->translateLabel()
                                ->columnSpan(2),
                            Select::make('response_type')
                                ->label('Response type')
                                ->translateLabel()
                                ->options(ChecklistResponseType::class)
                                ->default(ChecklistResponseType::Outcome)
                                ->required()
                                ->live(),
                            TextInput::make('help')
                                ->translateLabel()
                                ->columnSpan(2),
                            Toggle::make('is_required')
                                ->label('Required')
                                ->translateLabel()
                                ->default(true)
                                ->inline(false),
                            TextInput::make('unit')
                                ->translateLabel()
                                ->visible(fn (Get $get): bool => self::isResponseType($get('response_type'), ChecklistResponseType::Number)),
                            TextInput::make('min')
                                ->label('Minimum')
                                ->translateLabel()
                                ->numeric()
                                ->visible(fn (Get $get): bool => self::isResponseType($get('response_type'), ChecklistResponseType::Number)),
                            TextInput::make('max')
                                ->label('Maximum')
                                ->translateLabel()
                                ->numeric()
                                ->minValue(fn (Get $get): ?float => is_numeric($get('min')) ? (float) $get('min') : null)
                                ->visible(fn (Get $get): bool => self::isResponseType($get('response_type'), ChecklistResponseType::Number)),
                            TagsInput::make('options')
                                ->translateLabel()
                                ->placeholder(__('New option'))
                                ->required()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => self::isResponseType($get('response_type'), ChecklistResponseType::Choice)),
                            Select::make('tags')
                                ->label('Only for the tags')
                                ->translateLabel()
                                ->helperText(__('The item applies only to the inventories having at least one of these tags, also through their product. Leave empty to apply it to every inventory.'))
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * The select state is the enum while loading the record and its value
     * after a change.
     */
    public static function isResponseType(mixed $state, ChecklistResponseType $responseType): bool
    {
        return ($state instanceof ChecklistResponseType ? $state : ChecklistResponseType::tryFrom((string) $state)) === $responseType;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(self::getFormDefinition());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable(isGlobal: true)
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make('description')
                    ->translateLabel()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->translateLabel(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->translateLabel()
                    ->boolean(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->persistSearchInSession()
            ->recordUrl(function ($record) {
                if (auth()->user()->can('update', ChecklistTemplate::class)) {
                    return Pages\EditChecklistTemplate::getUrl(['record' => $record]);
                }

                return Pages\ViewChecklistTemplate::getUrl(['record' => $record]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklistTemplates::route('/'),
            'create' => Pages\CreateChecklistTemplate::route('/create'),
            'edit' => Pages\EditChecklistTemplate::route('/{record}/edit'),
            'view' => Pages\ViewChecklistTemplate::route('/{record}/view'),
        ];
    }
}
