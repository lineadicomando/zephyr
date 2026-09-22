<?php

use App\Models\Scope;
use Filament\Resources\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Activate Filament's native tenancy for tests without booting the full panel.
 *
 * Booting the full panel registers global scopes on every resource including
 * vendor resources that do not have a 'scope' relationship (e.g. FilamentShield
 * RoleResource), which causes a LogicException. This helper registers the
 * tenancy global scope and creation observer only for the provided resource
 * classes, then sets the current panel and tenant so Filament resolves
 * tenant-scoped queries and associates new records with the tenant.
 *
 * @param  array<class-string<Filament\Resources\Resource>>  $resources
 */
function activateFilamentTenant(Scope $scope, array $resources = []): void
{
    $panel = Filament\Facades\Filament::getPanel('app');

    Filament\Facades\Filament::setCurrentPanel($panel);

    foreach ($resources as $resource) {
        $resource::observeTenancyModelCreation($panel);
        $resource::registerTenancyModelGlobalScope($panel);
    }

    Filament\Facades\Filament::setTenant($scope);
}
