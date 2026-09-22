<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $label = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldTranslateLabel = true;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->translateLabel()
                    ->required(),
                TextInput::make('email')
                    ->autocomplete('off')
                    ->email(),
                TextInput::make('password')
                    ->autocomplete('off')
                    ->password()
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->password(),
                Select::make('roles')
                    ->translateLabel()
                    ->multiple()
                    ->preload()
                    ->relationship(
                        'roles',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->isRoot()
                            ? $query
                            : $query->where('name', '!=', 'super_admin'),
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(isGlobal: true)
                    ->translateLabel(),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->translateLabel(),
                TextColumn::make('created_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->date()
                    ->translateLabel()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistSearchInSession()
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(function ($record) {
                // if ($record->trashed()) {
                //     return null;
                // }
                if (auth()->user()->can('update', $record)) {
                    return Pages\EditUser::getUrl([$record->id]);
                }

                return Pages\ViewUser::getUrl([$record->id]);
            })
            ->actions([
                ViewAction::make(),
                EditAction::make()->hidden(fn (User $user) => $user->trashed()),
                RestoreAction::make(),
                ForceDeleteAction::make()->hidden(fn (User $user) => $user->hasRelated()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ScopesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}/view'),
        ];
    }
}
