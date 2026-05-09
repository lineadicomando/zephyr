<?php

namespace App\Providers\Filament;

use App\Filament\Resources\MovementResource\Widgets\MovementChart;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\TaskResource\Widgets\TaskChart;
use App\Filament\Widgets\CurrentScopeWidget;
use App\Filament\Widgets\StatsOverview;
use App\Models\Scope;
use App\View\Components\CreditsDialog;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('')
            ->login()
            ->passwordReset()
            ->authGuard('web')
            ->defaultThemeMode(ThemeMode::Dark)
            ->sidebarCollapsibleOnDesktop()
            ->profile()
            ->tenant(Scope::class, slugAttribute: 'slug', ownershipRelationship: 'scope')
            ->favicon(asset(env('APP_FAVICON')))
            ->brandLogo(asset(env('APP_LOGO')))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('Inventory')),
                NavigationGroup::make()
                    ->label(__('Users'))
                    ->collapsed(),
                NavigationGroup::make()
                    ->label(__('Maintenance'))
                    ->collapsed(),
                NavigationGroup::make()
                    ->label(__('Products'))
                    ->collapsed(),
                NavigationGroup::make()
                    ->label(__('Movements'))
                    ->collapsed(),
                NavigationGroup::make()
                    ->label(__('Tasks'))
                    ->collapsed(),
            ])
            ->resources([
                RoleResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->renderHook('panels::head.end', fn (): View|string => view('filament.components.brand-assets-head'))
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                CurrentScopeWidget::class,
                StatsOverview::class,
                MovementChart::class,
                TaskChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                Action::make('credits')
                    ->label(__('Credits'))
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading(__('Credits'))
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (): View => view('components.credits-dialog', [
                        'project' => config('app.project'),
                    ])),
                Action::make('diagnostics')
                    ->label(__('credits.diagnostics'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->modalHeading(__('credits.diagnostics'))
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->visible(fn (): bool => (bool) config('app.diagnostics_enabled')
                        && (auth()->user()?->isAdmin() ?? false))
                    ->modalContent(fn (): View => view('components.diagnostics-dialog', [
                        'domain' => request()->getHost(),
                        'dbVersion' => CreditsDialog::resolveDatabaseVersion(),
                        'phpVersion' => phpversion(),
                        'memoryUsage' => round(memory_get_usage() / 1024 / 1024, 2),
                        'serverInfo' => php_uname(),
                        'appEnv' => app()->environment(),
                    ])),
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
                    ->timezone(config('app.calendar_timezone'))
                    ->locale(env('LOCALE', 'en'))
                    ->config([
                        'initialView' => 'timeGridWeek',
                        'slotMinTime' => '06:00:00',
                        'slotMaxTime' => '21:00:00',
                        'expandRows' => true,
                        'slotLabelFormat' => [
                            'hour' => 'numeric',
                            'minute' => '2-digit',
                            'omitZeroMinute' => false,
                            'meridiem' => 'short',
                        ],
                        'headerToolbar' => [
                            'left' => 'prev,next today',
                            'center' => 'title',
                            'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
                        ],
                    ]),
            ]);
    }
}
