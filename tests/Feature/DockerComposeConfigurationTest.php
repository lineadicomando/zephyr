<?php

use Symfony\Component\Yaml\Yaml;

function composeFile(string $file): array
{
    return Yaml::parseFile(base_path($file));
}

it('starts the bundled database only with the database profile', function () {
    expect(composeFile('docker-compose.yml')['services']['db']['profiles'])->toBe(['database']);
});

it('does not make the php services depend on the bundled database', function (string $service) {
    // podman-compose fails when a dependency belongs to an inactive profile.
    expect(composeFile('docker-compose.yml')['services'][$service]['depends_on'] ?? [])
        ->not->toHaveKey('db');
})->with(['app', 'queue', 'scheduler']);

it('lets the php services reach a database on the host machine', function (string $service) {
    expect(composeFile('docker-compose.yml')['services'][$service]['extra_hosts'])
        ->toContain('host.docker.internal:host-gateway');
})->with(['app', 'queue', 'scheduler']);

it('connects the php services to an external database network in the override', function (string $service) {
    $override = composeFile('docker-compose.external-network.yml');

    expect($override['services'][$service]['networks'])->toBe(['default', 'database'])
        ->and($override['networks']['database']['external'])->toBeTrue();
})->with(['app', 'queue', 'scheduler']);

it('enables the bundled database by default in the docker environment template', function () {
    expect(file_get_contents(base_path('.env.docker')))->toMatch('/^COMPOSE_PROFILES=database$/m');
});

it('creates the public volume mount point owned by the user running the entrypoint', function () {
    $mountPoint = collect(composeFile('docker-compose.yml')['services']['app']['volumes'])
        ->map(fn (string $volume): string => explode(':', $volume)[1])
        ->first(fn (string $target): bool => str_ends_with($target, '/public-vol'));

    expect(file_get_contents(base_path('docker/php/Dockerfile')))
        ->toContain("mkdir -p {$mountPoint}")
        ->toMatch('/chown -R www-data:www-data[^\n]*(\\\\\n[^\n]*)*'.preg_quote($mountPoint, '/').'/');
});
