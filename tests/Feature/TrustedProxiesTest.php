<?php

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;

/**
 * Set TRUSTED_PROXIES as the environment would; the application must be
 * refreshed afterwards so that the service providers read it at boot.
 */
function setTrustedProxiesEnv(?string $proxies): void
{
    $proxies === null ? putenv('TRUSTED_PROXIES') : putenv("TRUSTED_PROXIES={$proxies}");
    $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = $proxies;

    TrustProxies::flushState();
}

function registerTrustedProxiesProbe(): void
{
    Route::get('/_trusted-proxies-probe', fn () => url('/login'));
}

afterEach(function () {
    putenv('TRUSTED_PROXIES');
    unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
    TrustProxies::flushState();
});

it('generates https urls behind a trusted proxy', function (string $proxies) {
    setTrustedProxiesEnv($proxies);
    $this->refreshApplication();
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
    setTrustedProxiesEnv($proxies);
    $this->refreshApplication();
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
    setTrustedProxiesEnv('192.168.48.0/20');
    $this->refreshApplication();

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.48.2'])
        ->get('/login', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'zephyr.cmdln.it'])
        ->assertOk()
        ->assertSee('https://zephyr.cmdln.it/'.config('app.branding.favicon'), escape: false)
        ->assertSee('https://zephyr.cmdln.it/'.config('app.branding.logo'), escape: false)
        ->assertDontSee('http://zephyr.cmdln.it', escape: false);
});
