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

arch('environment variables are only read in the config directory')
    ->expect('env')
    ->not->toBeUsedIn(['App', 'Database\Seeders']);
