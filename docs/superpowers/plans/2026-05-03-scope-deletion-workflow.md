# Scope Deletion Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendere gli scope default protetti e introdurre un workflow di cancellazione differita con purge schedulato.

**Architecture:** Lo stato di lifecycle dello scope viene esteso con `protected` e `pending_delete`. La richiesta di delete diventa una schedulazione (`pending_delete`) e la rimozione definitiva avviene via comando Artisan schedulato, con cancellazione diretta dei dati scoped in transazione. Le guardie vengono applicate sia in UI che nel comando di purge.

**Tech Stack:** Laravel 12, Filament 4, Pest, scheduler Laravel (`routes/console.php`), query builder DB.

---

### Task 1: Estendere schema/config/model Scope

**Files:**
- Modify: `database/migrations/2014_10_12_050000_create_scopes_table.php`
- Modify: `.env.example`
- Create: `config/scopes.php`
- Modify: `app/Models/Scope.php`
- Test: `tests/Feature/Scopes/ScopeLifecycleRulesTest.php`

- [ ] **Step 1: Write failing test for new attributes/casts**

```php
it('supports protected and pending_delete attributes on scopes', function (): void {
    $scope = \App\Models\Scope::query()->create([
        'name' => 'Scoped',
        'slug' => 'scoped-attr',
        'type' => 'company',
        'is_active' => false,
        'protected' => true,
        'pending_delete' => now()->addHour(),
    ]);

    expect($scope->protected)->toBeTrue();
    expect($scope->pending_delete)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Scopes/ScopeLifecycleRulesTest.php --filter=supports`  
Expected: FAIL su colonne mancanti o cast mancanti.

- [ ] **Step 3: Implement schema/config/model changes**

```php
// migration scopes
$table->boolean('protected')->default(false);
$table->timestamp('pending_delete')->nullable()->index();

DB::table('scopes')->insert([
    'name' => 'Default',
    'slug' => 'default',
    'type' => 'company',
    'is_active' => true,
    'protected' => true,
    'pending_delete' => null,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

```php
// app/Models/Scope.php
protected $fillable = ['name', 'slug', 'type', 'is_active', 'protected', 'pending_delete'];
protected $casts = ['is_active' => 'boolean', 'protected' => 'boolean', 'pending_delete' => 'datetime'];
```

```php
// config/scopes.php
return [
    'delete_grace_hours' => (int) env('SCOPE_DELETE_GRACE_HOURS', 24),
];
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Scopes/ScopeLifecycleRulesTest.php --filter=supports`  
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2014_10_12_050000_create_scopes_table.php .env.example config/scopes.php app/Models/Scope.php tests/Feature/Scopes/ScopeLifecycleRulesTest.php
git commit -m "feat(scopes): add protected and pending delete fields"
```

### Task 2: Guardrail applicativo su disattivazione e delete request

**Files:**
- Modify: `app/Filament/Resources/ScopeResource.php`
- Test: `tests/Feature/Filament/ScopeManagementUxTest.php`
- Test: `tests/Feature/Scopes/ScopeLifecycleRulesTest.php`

- [ ] **Step 1: Write failing tests for lifecycle rules**

```php
it('prevents protected scopes from being deactivated', function (): void {
    $scope = \App\Models\Scope::query()->create([
        'name' => 'Protected', 'slug' => 'protected-x', 'type' => 'company', 'is_active' => true, 'protected' => true,
    ]);

    $scope->update(['is_active' => false]);

    expect($scope->fresh()->is_active)->toBeTrue();
});
```

```php
it('rejects delete request for active scopes', function (): void {
    $scope = \App\Models\Scope::query()->create([
        'name' => 'Active', 'slug' => 'active-delete', 'type' => 'company', 'is_active' => true,
    ]);

    // invoke service/action used by ScopeResource delete request
    // expect domain exception / validation error
});
```

- [ ] **Step 2: Run targeted tests and confirm fail**

Run: `php artisan test --compact tests/Feature/Scopes/ScopeLifecycleRulesTest.php`  
Expected: FAIL su regole non implementate.

- [ ] **Step 3: Implement ScopeResource rules and delete scheduling action**

```php
Toggle::make('is_active')
    ->disabled(fn (?Scope $record): bool => (bool) $record?->protected)
    ->afterStateUpdated(function (?Scope $record, bool $state): void {
        if ($record?->protected && $state === false) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'is_active' => __('Protected scope cannot be deactivated.'),
            ]);
        }

        if ($state === true) {
            $record?->update(['pending_delete' => null]);
        }
    });
```

```php
// table action: request deletion instead of immediate delete
if ($record->protected) { /* reject */ }
if ($record->is_active) { /* reject */ }
$record->update([
    'pending_delete' => now()->addHours(config('scopes.delete_grace_hours', 24)),
]);
```

- [ ] **Step 4: Re-run tests and ensure pass**

Run: `php artisan test --compact tests/Feature/Scopes/ScopeLifecycleRulesTest.php tests/Feature/Filament/ScopeManagementUxTest.php`  
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/ScopeResource.php tests/Feature/Scopes/ScopeLifecycleRulesTest.php tests/Feature/Filament/ScopeManagementUxTest.php
git commit -m "feat(scopes): enforce protected and scheduled deletion rules"
```

### Task 3: Implementare purge diretto con comando Artisan

**Files:**
- Create: `app/Console/Commands/PurgePendingScopesCommand.php`
- Create: `app/Support/Scope/ScopePurgeRegistry.php`
- Test: `tests/Feature/Scopes/ScopePurgeCommandTest.php`

- [ ] **Step 1: Write failing purge command tests**

```php
it('purges expired pending scopes and linked records', function (): void {
    $scope = \App\Models\Scope::query()->create([
        'name' => 'To purge',
        'slug' => 'to-purge',
        'type' => 'company',
        'is_active' => false,
        'pending_delete' => now()->subMinute(),
    ]);

    \Illuminate\Support\Facades\DB::table('scope_user')->insert([
        'scope_id' => $scope->id,
        'user_id' => \App\Models\User::factory()->create()->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('scopes:purge-pending')->assertExitCode(0);

    expect(\App\Models\Scope::query()->whereKey($scope->id)->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run test to confirm fail**

Run: `php artisan test --compact tests/Feature/Scopes/ScopePurgeCommandTest.php`  
Expected: FAIL, comando non esiste.

- [ ] **Step 3: Implement registry + command with DB direct deletes**

```php
// ScopePurgeRegistry
public function tables(): array
{
    return [
        'inventory_positions',
        'movement_items',
        'movements',
        'stocks',
        'tasks',
        'reorders',
        'reorder_orders',
        'inventory_locations',
        'inventories',
    ];
}
```

```php
// PurgePendingScopesCommand handle()
Scope::query()
    ->whereNotNull('pending_delete')
    ->where('pending_delete', '<=', now())
    ->orderBy('id')
    ->chunkById(100, function ($scopes): void {
        foreach ($scopes as $scope) {
            DB::transaction(function () use ($scope): void {
                $locked = Scope::query()->whereKey($scope->id)->lockForUpdate()->first();
                if (! $locked || $locked->protected || $locked->is_active || $locked->pending_delete === null || $locked->pending_delete->isFuture()) {
                    return;
                }

                foreach (app(\App\Support\Scope\ScopePurgeRegistry::class)->tables() as $table) {
                    DB::table($table)->where('scope_id', $locked->id)->delete();
                }

                DB::table('scope_user')->where('scope_id', $locked->id)->delete();
                DB::table('scopes')->where('id', $locked->id)->delete();
            });
        }
    });
```

- [ ] **Step 4: Re-run purge tests**

Run: `php artisan test --compact tests/Feature/Scopes/ScopePurgeCommandTest.php`  
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/PurgePendingScopesCommand.php app/Support/Scope/ScopePurgeRegistry.php tests/Feature/Scopes/ScopePurgeCommandTest.php
git commit -m "feat(scopes): add scheduled purge command for pending deletions"
```

### Task 4: Schedulare purge nel cron Laravel

**Files:**
- Modify: `routes/console.php`
- Test: `tests/Feature/Scopes/ScopePurgeScheduleTest.php`

- [ ] **Step 1: Write failing scheduler test**

```php
it('registers pending scope purge in scheduler', function (): void {
    $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();

    expect(collect($events)->contains(
        fn ($event) => str_contains($event->command, 'scopes:purge-pending')
    ))->toBeTrue();
});
```

- [ ] **Step 2: Run test to confirm fail**

Run: `php artisan test --compact tests/Feature/Scopes/ScopePurgeScheduleTest.php`  
Expected: FAIL evento non presente.

- [ ] **Step 3: Register scheduled command**

```php
Schedule::command('scopes:purge-pending')
    ->hourly()
    ->withoutOverlapping();
```

- [ ] **Step 4: Re-run scheduler test**

Run: `php artisan test --compact tests/Feature/Scopes/ScopePurgeScheduleTest.php`  
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/console.php tests/Feature/Scopes/ScopePurgeScheduleTest.php
git commit -m "feat(scopes): schedule pending delete purge command"
```

### Task 5: Verifica end-to-end e documentazione operativa

**Files:**
- Modify: `README.md`
- Test: `tests/Feature/Scopes/ScopeLifecycleRulesTest.php`
- Test: `tests/Feature/Scopes/ScopePurgeCommandTest.php`
- Test: `tests/Feature/Scopes/ScopePurgeScheduleTest.php`

- [ ] **Step 1: Add README operational notes**

```md
### Scope deletion lifecycle
- Protected scopes cannot be deactivated or deleted.
- Scope deletion is deferred by `SCOPE_DELETE_GRACE_HOURS` (default 24).
- Final purge runs via `php artisan schedule:run` and command `scopes:purge-pending`.
```

- [ ] **Step 2: Run focused scope test suite**

Run:
`php artisan test --compact tests/Feature/Scopes/ScopeLifecycleRulesTest.php tests/Feature/Scopes/ScopePurgeCommandTest.php tests/Feature/Scopes/ScopePurgeScheduleTest.php tests/Feature/Filament/ScopeManagementUxTest.php`

Expected: PASS.

- [ ] **Step 3: Run broader regression for scope behavior**

Run:
`php artisan test --compact tests/Feature/Scopes tests/Feature/Filament/CurrentScopeWidgetTest.php tests/Feature/Filament/ScopeManagementUxTest.php`

Expected: PASS.

- [ ] **Step 4: Commit final docs/tests alignment**

```bash
git add README.md tests/Feature/Scopes tests/Feature/Filament/ScopeManagementUxTest.php
git commit -m "docs(scopes): document protected and deferred deletion workflow"
```
