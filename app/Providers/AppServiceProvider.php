<?php

namespace App\Providers;

use App\Support\Scope\ScopeAccessResolver;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScopeAccessResolver::class, ScopeAccessResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // After all service providers have booted (including Filament which registers
        // its routes during boot), retroactively constrain the {tenant} route parameter
        // on all Filament routes to prevent them from matching reserved path segments
        // such as "api". Without this, GET /api/products would match Filament's
        // {tenant:slug}/products route (treating "api" as a tenant slug) before the
        // api middleware group can handle the request.
        $this->app->booted(function (): void {
            // Symfony route requirements strip anchors, so use a negative lookbehind
            // to match any valid slug that does not end with exactly "api".
            // This prevents the Filament {tenant:slug}/... web routes from intercepting
            // requests to the /api/... endpoints.
            $pattern = '[a-z0-9\-_]+(?<!api)';

            foreach (Route::getRoutes()->getRoutes() as $route) {
                $uri = $route->uri();

                if (str_starts_with($uri, '{tenant') || str_contains($uri, '/{tenant')) {
                    $route->where('tenant', $pattern);
                    // Reset compiled route so the new constraint is applied on next match.
                    $route->compiled = null;
                }
            }
        });

        FilamentAsset::register([
            AlpineComponent::make('barcode-scanner', __DIR__.'/../../resources/js/dist/components/barcode-scanner.js')
                ->loadedOnRequest(),
        ], package: 'app');

        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch->slideOver();
            $panelSwitch
                ->simple()
                ->labels([
                    'app' => __('Home'),
                    'admin' => __('Settings'),
                ])
                ->icons(
                    [
                        // 'app' => 'heroicon-o-star',
                        'app' => 'heroicon-o-home',
                        'admin' => 'heroicon-o-cog-6-tooth',
                    ],
                    $asImage = false,
                )
                ->renderHook('panels::global-search.after');
        });
    }
}
