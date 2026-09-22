<?php

use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the favicons configured in the application config', function () {
    config()->set('app.branding.favicon_svg', 'images/custom-favicon.svg');
    config()->set('app.branding.apple_touch_icon', 'images/custom-touch.png');

    $html = view('filament.components.brand-assets-head')->render();

    expect($html)->toContain(asset('images/custom-favicon.svg'))
        ->toContain(asset('images/custom-touch.png'));
});

it('pads automatic inventory numbers with the configured number of digits', function () {
    config()->set('app.inventory_number_zero_fill', 4);

    $inventory = Inventory::factory()->create();

    expect($inventory->fresh()->inventory_number)->toBe(str_pad((string) $inventory->id, 4, '0', STR_PAD_LEFT));
});

/**
 * Evaluates a config file with the given environment variables (null unsets them).
 *
 * @param  array<string, string|null>  $variables
 * @return array<string, mixed>
 */
function configWithEnvironment(string $file, array $variables): array
{
    $original = [];

    foreach ($variables as $name => $value) {
        $original[$name] = [
            'env' => array_key_exists($name, $_ENV) ? $_ENV[$name] : false,
            'server' => array_key_exists($name, $_SERVER) ? $_SERVER[$name] : false,
            'getenv' => getenv($name),
        ];

        $value === null ? putenv($name) : putenv("{$name}={$value}");
        unset($_ENV[$name], $_SERVER[$name]);

        if ($value !== null) {
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
    }

    try {
        return require config_path("{$file}.php");
    } finally {
        foreach ($original as $name => $previous) {
            $previous['getenv'] === false ? putenv($name) : putenv("{$name}={$previous['getenv']}");
            unset($_ENV[$name], $_SERVER[$name]);

            if ($previous['env'] !== false) {
                $_ENV[$name] = $previous['env'];
            }

            if ($previous['server'] !== false) {
                $_SERVER[$name] = $previous['server'];
            }
        }
    }
}

it('expires api tokens after one year unless configured otherwise', function (?string $value, ?int $expected) {
    expect(configWithEnvironment('sanctum', ['SANCTUM_TOKEN_EXPIRATION' => $value])['expiration'])->toBe($expected);
})->with([
    'unset' => [null, 525600],
    'empty' => ['', 525600],
    'custom' => ['1440', 1440],
    'never' => ['0', null],
]);

it('sends the session cookie over https only when the application url uses https', function (?string $secure, string $url, bool $expected) {
    $config = configWithEnvironment('session', ['SESSION_SECURE_COOKIE' => $secure, 'APP_URL' => $url]);

    expect($config['secure'])->toBe($expected);
})->with([
    'automatic on https' => [null, 'https://zephyr.example.com', true],
    'automatic on http' => ['', 'http://localhost:8000', false],
    'forced on' => ['true', 'http://localhost:8000', true],
    'forced off' => ['false', 'https://zephyr.example.com', false],
]);

arch('environment variables are only read in the config directory')
    ->expect('env')
    ->not->toBeUsedIn(['App', 'Database\Seeders']);
