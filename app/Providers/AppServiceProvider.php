<?php

namespace App\Providers;

use App\Support\Scope\ScopeAccessResolver;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureTrustedProxies();

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(config('sanctum.rate_limit_per_minute'))
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // After all service providers have booted (including Filament which registers
        // its routes during boot), retroactively constrain the {tenant} route parameter
        // on all Filament routes to prevent them from matching reserved path segments
        // such as "api". Without this, GET /api/products would match Filament's
        // {tenant:slug}/products route (treating "api" as a tenant slug) before the
        // api middleware group can handle the request.
        $this->app->booted(function (): void {
            // Symfony route requirements strip anchors, so use a negative lookahead
            // to reject a slug that is exactly "api" (followed by "/" or the end of
            // the path) while still allowing slugs such as "sapi" or "api-team".
            // This prevents the Filament {tenant:slug}/... web routes from intercepting
            // requests to the /api/... endpoints.
            $pattern = '(?!api(?:/|$))[a-z0-9\-_]+';

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

    /**
     * Trust the X-Forwarded-* headers sent by the configured reverse proxies.
     */
    public function configureTrustedProxies(): void
    {
        TrustProxies::flushState();

        if (filled($trustedProxies = config('app.trusted_proxies'))) {
            TrustProxies::at($trustedProxies === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', (string) $trustedProxies)))));
        }
    }
}
