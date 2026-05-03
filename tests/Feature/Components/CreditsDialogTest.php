<?php

use App\View\Components\CreditsDialog;

it('renders the credits dialog view with system variables', function () {
    $rendered = view('components.credits-dialog', [
        'project' => [
            'name' => 'TestProject',
            'version' => '1.0.0',
            'author_name' => 'Test Author',
            'author_email' => 'author@example.com',
            'license' => 'AGPL-3.0-or-later',
        ],
    ])->render();

    expect($rendered)
        ->toContain('TestProject')
        ->toContain('1.0.0')
        ->toContain('Test Author')
        ->toContain('author@example.com')
        ->toContain('AGPL-3.0-or-later');
});

it('resolves a non-empty database version string', function () {
    $version = CreditsDialog::resolveDatabaseVersion();

    expect($version)->toBeString()->not->toBeEmpty();
});
