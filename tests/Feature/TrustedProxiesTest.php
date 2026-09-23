<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;

/**
 * Configure the trusted proxies as TRUSTED_PROXIES would at boot. The config
 * value is set directly: refreshing the application would reload .env, whose
 * values overwrite the ones set by the test.
 */
function setTrustedProxies(?string $proxies): void
{
    config()->set('app.trusted_proxies', $proxies);

    app()->getProvider(AppServiceProvider::class)->configureTrustedProxies();
}

function registerTrustedProxiesProbe(): void
{
    Route::get('/_trusted-proxies-probe', fn () => url('/login'));
}

afterEach(function () {
    TrustProxies::flushState();
});

it('generates https urls behind a trusted proxy', function (string $proxies) {
    setTrustedProxies($proxies);
    registerTrustedProxiesProbe();

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.48.2'])
        ->get('/_trusted-proxies-probe', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'zephyr.cmdln.it'])
        ->assertSee('https://zephyr.cmdln.it/login', escape: false);
})->with([
    'cidr' => '192.168.48.0/20',
    'list' => '10.0.0.1, 192.168.48.2',
    'any' => '*',
]);

it('ignores forwarded headers from untrusted clients', function (?string $proxies) {
    setTrustedProxies($proxies);
    registerTrustedProxiesProbe();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->get('/_trusted-proxies-probe', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example'])
        ->assertDontSee('evil.example', escape: false)
        ->assertDontSee('https://', escape: false);
})->with([
    'not configured' => null,
    'other network' => '192.168.48.0/20',
]);

it('serves the panel branding assets over https behind a trusted proxy', function () {
    setTrustedProxies('192.168.48.0/20');

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.48.2'])
        ->get('/login', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'zephyr.cmdln.it'])
        ->assertOk()
        ->assertSee('https://zephyr.cmdln.it/'.config('app.branding.favicon'), escape: false)
        ->assertSee('https://zephyr.cmdln.it/'.config('app.branding.logo'), escape: false)
        ->assertSee('https://zephyr.cmdln.it/'.config('app.branding.logo_dark'), escape: false)
        ->assertDontSee('http://zephyr.cmdln.it', escape: false);
});
