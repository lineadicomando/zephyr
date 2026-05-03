<?php

declare(strict_types=1);

use App\Models\Scope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('casts protected and pending_delete attributes correctly', function (): void {
    $pendingDelete = now()->addDay()->startOfSecond();

    $scope = Scope::query()->create([
        'name' => 'Protected Scope',
        'slug' => 'protected-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => true,
        'pending_delete' => $pendingDelete,
    ]);

    $scope->refresh();

    expect($scope->getAttributes())->toHaveKeys([
        'protected',
        'pending_delete',
    ]);

    expect($scope->protected)->toBeTrue()
        ->and($scope->pending_delete)->toBeInstanceOf(Carbon::class)
        ->and($scope->pending_delete?->equalTo($pendingDelete))->toBeTrue();
});

it('prevents deactivation of protected scopes', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Default',
        'slug' => 'default-protected',
        'type' => 'company',
        'is_active' => true,
        'protected' => true,
    ]);

    expect(fn () => $scope->update(['is_active' => false]))
        ->toThrow(\LogicException::class, 'Protected scopes cannot be deactivated.');
});

it('prevents delete request for protected scopes', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Default',
        'slug' => 'default-2',
        'type' => 'company',
        'is_active' => false,
        'protected' => true,
    ]);

    expect(fn () => $scope->delete())
        ->toThrow(\LogicException::class, 'Protected scopes cannot be deleted.');
});

it('allows changing only name for protected scopes', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Default',
        'slug' => 'default-3',
        'type' => 'company',
        'is_active' => true,
        'protected' => true,
    ]);

    $scope->update(['name' => 'Default Updated']);
    $scope->refresh();

    expect($scope->name)->toBe('Default Updated');
});

it('prevents changing slug for protected scopes', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Default',
        'slug' => 'default-4',
        'type' => 'company',
        'is_active' => true,
        'protected' => true,
    ]);

    expect(fn () => $scope->update(['slug' => 'default-4-updated']))
        ->toThrow(\LogicException::class, 'For protected scopes only the name can be changed.');
});

it('prevents delete request for active scopes', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Active Scope',
        'slug' => 'active-scope',
        'type' => 'company',
        'is_active' => true,
        'protected' => false,
    ]);

    expect(fn () => $scope->delete())
        ->toThrow(\LogicException::class, 'Active scopes cannot be deleted. Deactivate the scope first.');
});

it('schedules deletion instead of physically deleting an inactive scope', function (): void {
    config()->set('scopes.delete_grace_hours', 24);
    Carbon::setTestNow('2026-05-03 12:00:00');

    $scope = Scope::query()->create([
        'name' => 'Deletable Scope',
        'slug' => 'deletable-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
    ]);

    $scope->delete();
    $scope->refresh();

    expect($scope->exists)->toBeTrue()
        ->and($scope->pending_delete?->equalTo(now()->addHours(24)))->toBeTrue();
});

it('keeps delete request idempotent when pending_delete is already set', function (): void {
    config()->set('scopes.delete_grace_hours', 24);
    Carbon::setTestNow('2026-05-03 12:00:00');

    $originalPendingDelete = now()->addHours(24);

    $scope = Scope::query()->create([
        'name' => 'Pending Scope',
        'slug' => 'pending-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
        'pending_delete' => $originalPendingDelete,
    ]);

    Carbon::setTestNow('2026-05-03 15:30:00');
    $scope->delete();
    $scope->refresh();

    expect($scope->pending_delete?->equalTo($originalPendingDelete))->toBeTrue();
});

it('clears pending_delete when scope is re-activated', function (): void {
    $scope = Scope::query()->create([
        'name' => 'Reactivation Scope',
        'slug' => 'reactivation-scope',
        'type' => 'company',
        'is_active' => false,
        'protected' => false,
        'pending_delete' => now()->addHours(24),
    ]);

    $scope->update(['is_active' => true]);
    $scope->refresh();

    expect($scope->pending_delete)->toBeNull();
});
