# Global Scopes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce flat, type-based global scopes so operational data is isolated per active scope while users and product catalog remain shared.

**Architecture:** Add a new `scopes` bounded context, attach users to scopes, and add `scope_id` to operational tables. Resolve `active_scope_id` at runtime via middleware/session, then enforce isolation with model global scopes plus policy checks. Surface scope selection in Filament UI and verify behavior with feature/policy tests.

**Tech Stack:** Laravel 13, Filament 5, Spatie Permission, Pest.

---

## File Structure Map

- Create: `app/Models/Scope.php` (core scope entity)
- Create: `app/Contracts/ScopeContext.php` (runtime active scope contract)
- Create: `app/Support/Scope/SessionScopeContext.php` (session-backed scope resolver)
- Create: `app/Http/Middleware/EnsureActiveScope.php` (active scope validation per request)
- Create: `app/Models/Concerns/BelongsToScope.php` (common relationship + fill helper)
- Create: `app/Models/Scopes/ActiveScopeGlobalScope.php` (global query isolation)
- Create: `app/Filament/Widgets/ScopeSwitcher.php` or `app/Filament/Pages/Concerns/HasScopeSwitcher.php` (panel switching UX, pick pattern used in project)
- Create: `database/migrations/2026_05_03_110000_create_scopes_table.php`
- Create: `database/migrations/2026_05_03_110100_create_scope_user_table.php`
- Create: `database/migrations/2026_05_03_110200_add_scope_id_to_operational_tables.php`
- Create: `database/seeders/ScopeSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (register scope seed)
- Modify: `app/Models/User.php` (scopes relation + helpers)
- Modify: `app/Providers/AppServiceProvider.php` (bind `ScopeContext`)
- Modify: `bootstrap/app.php` or `app/Http/Kernel.php` (register middleware based on project setup)
- Modify: operational models (`Inventory`, `Stock`, `Movement`, `MovementItem`, `Task`, `Reorder`, `ReorderOrder`, `ReorderOrderItem`, etc.) to use `BelongsToScope`
- Modify: operational Filament Resources to force-create with active scope and hide manual scope assignment
- Modify: operational policies to add explicit scope guard
- Create/Modify tests in `tests/Feature/Scopes/*` and `tests/Feature/Policies/*`

### Task 1: Add Failing Scope Isolation Tests First

**Files:**
- Create: `tests/Feature/Scopes/ScopeIsolationTest.php`
- Create: `tests/Feature/Scopes/ScopeSwitchingTest.php`
- Create: `tests/Feature/Policies/ScopePolicyGuardTest.php`

- [ ] **Step 1: Write failing feature test for list isolation**

```php
<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\Scope;
use App\Models\User;

it('shows only records for active scope', function (): void {
    $scopeA = Scope::factory()->create(['type' => 'company']);
    $scopeB = Scope::factory()->create(['type' => 'school']);

    $user = User::factory()->create();
    $user->scopes()->attach([$scopeA->id, $scopeB->id]);

    Inventory::factory()->create(['scope_id' => $scopeA->id]);
    Inventory::factory()->create(['scope_id' => $scopeB->id]);

    $this->actingAs($user)
        ->withSession(['active_scope_id' => $scopeA->id])
        ->get(route('filament.app.resources.inventories.index'))
        ->assertOk()
        ->assertSee((string) $scopeA->id)
        ->assertDontSee((string) $scopeB->id);
});
```

- [ ] **Step 2: Write failing feature test for switching active scope**

```php
it('switches active scope and updates visible dataset', function (): void {
    // prepare user with two scopes
    // set active scope A
    // perform switch action to scope B
    // assert session active_scope_id is B and view no longer contains scope A records
});
```

- [ ] **Step 3: Write failing policy test for cross-scope access denial**

```php
it('denies update when record belongs to another scope', function (): void {
    // user assigned only to scope A
    // record belongs to scope B
    // policy update returns false
});
```

- [ ] **Step 4: Run scope tests to verify FAIL**

Run: `php artisan test tests/Feature/Scopes tests/Feature/Policies/ScopePolicyGuardTest.php`
Expected: FAIL with missing `scopes` table / missing `scope_id` behavior.

- [ ] **Step 5: Commit test scaffold**

```bash
git add tests/Feature/Scopes tests/Feature/Policies/ScopePolicyGuardTest.php
git commit -m "test: add failing tests for global scope isolation"
```

### Task 2: Introduce Scope Schema and Seed Data

**Files:**
- Create: `database/migrations/2026_05_03_110000_create_scopes_table.php`
- Create: `database/migrations/2026_05_03_110100_create_scope_user_table.php`
- Create: `database/seeders/ScopeSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Write migration for `scopes` table**

```php
Schema::create('scopes', function (Blueprint $table): void {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('type');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

- [ ] **Step 2: Write migration for `scope_user` pivot**

```php
Schema::create('scope_user', function (Blueprint $table): void {
    $table->foreignId('scope_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['scope_id', 'user_id']);
});
```

- [ ] **Step 3: Seed default scope**

```php
Scope::query()->firstOrCreate(
    ['slug' => 'default'],
    ['name' => 'Default', 'type' => 'company', 'is_active' => true],
);
```

- [ ] **Step 4: Run migrations + seeders**

Run: `php artisan migrate --seed`
Expected: PASS, `scopes` and `scope_user` created, `default` scope present.

- [ ] **Step 5: Commit schema baseline**

```bash
git add database/migrations database/seeders
git commit -m "feat: add scopes schema and default seed"
```

### Task 3: Add `scope_id` to Operational Tables with Backfill

**Files:**
- Create: `database/migrations/2026_05_03_110200_add_scope_id_to_operational_tables.php`

- [ ] **Step 1: Add nullable `scope_id` to operational tables**

```php
foreach (['inventories','stocks','movements','movement_items','tasks','reorders','reorder_orders','reorder_order_items'] as $tableName) {
    Schema::table($tableName, function (Blueprint $table): void {
        $table->foreignId('scope_id')->nullable()->after('id')->constrained('scopes');
        $table->index(['scope_id']);
    });
}
```

- [ ] **Step 2: Backfill all existing records to default scope**

```php
$defaultScopeId = DB::table('scopes')->where('slug', 'default')->value('id');
DB::table('inventories')->whereNull('scope_id')->update(['scope_id' => $defaultScopeId]);
// repeat for each operational table
```

- [ ] **Step 3: Make `scope_id` non-nullable**

```php
Schema::table('inventories', function (Blueprint $table): void {
    $table->foreignId('scope_id')->nullable(false)->change();
});
```

- [ ] **Step 4: Run migration tests**

Run: `php artisan test tests/Feature/Console`
Expected: PASS, migrations apply in test environment.

- [ ] **Step 5: Commit table partition changes**

```bash
git add database/migrations
git commit -m "feat: partition operational data with scope_id"
```

### Task 4: Implement Runtime Scope Context and Middleware

**Files:**
- Create: `app/Contracts/ScopeContext.php`
- Create: `app/Support/Scope/SessionScopeContext.php`
- Create: `app/Http/Middleware/EnsureActiveScope.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `bootstrap/app.php` (or `app/Http/Kernel.php`)
- Modify: `app/Models/User.php`
- Create: `app/Models/Scope.php`

- [ ] **Step 1: Add `Scope` and `User` relationships**

```php
// app/Models/Scope.php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)->withTimestamps();
}

// app/Models/User.php
public function scopes(): BelongsToMany
{
    return $this->belongsToMany(Scope::class)->withTimestamps();
}
```

- [ ] **Step 2: Implement scope context contract + session adapter**

```php
interface ScopeContext
{
    public function activeScopeId(): ?int;
    public function setActiveScopeId(int $scopeId): void;
}
```

```php
final class SessionScopeContext implements ScopeContext
{
    public function activeScopeId(): ?int
    {
        return session('active_scope_id');
    }

    public function setActiveScopeId(int $scopeId): void
    {
        session(['active_scope_id' => $scopeId]);
    }
}
```

- [ ] **Step 3: Add middleware that validates/repairs active scope**

```php
if (! $request->user()) {
    return $next($request);
}

$active = $context->activeScopeId();
$allowed = $request->user()->scopes()->where('is_active', true)->pluck('scopes.id');

if (! $active || ! $allowed->contains($active)) {
    $fallback = $allowed->first();
    abort_if(! $fallback, 403, 'No accessible scope assigned.');
    $context->setActiveScopeId($fallback);
}
```

- [ ] **Step 4: Bind contract and register middleware**

Run: `php artisan test tests/Feature/Scopes/ScopeSwitchingTest.php`
Expected: still FAIL on model filtering/policies, but middleware resolution works.

- [ ] **Step 5: Commit runtime context layer**

```bash
git add app/Contracts app/Support/Scope app/Http/Middleware app/Models/User.php app/Models/Scope.php app/Providers/AppServiceProvider.php bootstrap/app.php
git commit -m "feat: add active scope runtime context and middleware"
```

### Task 5: Enforce Model-Level Isolation via Global Scope

**Files:**
- Create: `app/Models/Concerns/BelongsToScope.php`
- Create: `app/Models/Scopes/ActiveScopeGlobalScope.php`
- Modify: `app/Models/Inventory.php`
- Modify: `app/Models/Stock.php`
- Modify: `app/Models/Movement.php`
- Modify: `app/Models/MovementItem.php`
- Modify: `app/Models/Task.php`
- Modify: `app/Models/Reorder.php`
- Modify: `app/Models/ReorderOrder.php`
- Modify: `app/Models/ReorderOrderItem.php`

- [ ] **Step 1: Create reusable global scope and trait**

```php
final class ActiveScopeGlobalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $scopeId = app(ScopeContext::class)->activeScopeId();

        if ($scopeId !== null) {
            $builder->where($model->qualifyColumn('scope_id'), $scopeId);
        }
    }
}
```

```php
trait BelongsToScope
{
    protected static function bootBelongsToScope(): void
    {
        static::addGlobalScope(new ActiveScopeGlobalScope());

        static::creating(function (Model $model): void {
            if (! $model->scope_id) {
                $model->scope_id = app(ScopeContext::class)->activeScopeId();
            }
        });
    }
}
```

- [ ] **Step 2: Apply trait to scoped operational models**

```php
class Inventory extends Model
{
    use BelongsToScope;
}
```

- [ ] **Step 3: Run isolation tests**

Run: `php artisan test tests/Feature/Scopes/ScopeIsolationTest.php`
Expected: PASS for read isolation and create auto-assignment behavior (if already covered).

- [ ] **Step 4: Commit model isolation**

```bash
git add app/Models
git commit -m "feat: enforce active scope isolation at model layer"
```

### Task 6: Add Policy-Level Guard Rails

**Files:**
- Modify: `app/Policies/InventoryPolicy.php`
- Modify: `app/Policies/MovementPolicy.php`
- Modify: `app/Policies/TaskPolicy.php`
- Modify: `app/Policies/ReorderPolicy.php`
- Modify: `app/Policies/ReorderOrderPolicy.php`
- Create: `app/Policies/Concerns/ChecksScopeAccess.php` (optional shared helper)

- [ ] **Step 1: Add shared scope check helper**

```php
protected function canAccessScope(User $user, int $scopeId): bool
{
    return $user->isRoot() || $user->scopes()->whereKey($scopeId)->exists();
}
```

- [ ] **Step 2: Integrate helper in record policies**

```php
public function update(User $user, Inventory $inventory): bool
{
    return $this->canAccessScope($user, $inventory->scope_id)
        && $user->can('update_inventory');
}
```

- [ ] **Step 3: Run policy tests**

Run: `php artisan test tests/Feature/Policies/ScopePolicyGuardTest.php`
Expected: PASS for cross-scope denial.

- [ ] **Step 4: Commit policy hardening**

```bash
git add app/Policies tests/Feature/Policies/ScopePolicyGuardTest.php
git commit -m "feat: add explicit cross-scope policy guards"
```

### Task 7: Add Filament Scope Switcher and Resource Integration

**Files:**
- Create: `app/Filament/Widgets/ScopeSwitcher.php` (or equivalent filament topbar component)
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Modify: scoped resources create/edit forms to keep `scope_id` hidden/immutable for non-root users

- [ ] **Step 1: Add topbar selector bound to user scopes**

```php
Select::make('active_scope_id')
    ->options(auth()->user()->scopes()->pluck('name', 'scopes.id'))
    ->afterStateUpdated(fn ($state) => app(ScopeContext::class)->setActiveScopeId((int) $state));
```

- [ ] **Step 2: Register selector in app panel**

```php
$panel->widgets([
    ScopeSwitcher::class,
]);
```

- [ ] **Step 3: Ensure scoped resources never allow manual reassignment**

```php
Hidden::make('scope_id')
    ->default(fn () => app(ScopeContext::class)->activeScopeId())
    ->dehydrated(true);
```

- [ ] **Step 4: Run filament-focused tests**

Run: `php artisan test tests/Feature/Filament tests/Feature/Scopes/ScopeSwitchingTest.php`
Expected: PASS, scope change affects resource data.

- [ ] **Step 5: Commit UI integration**

```bash
git add app/Filament app/Providers/Filament tests/Feature/Scopes
git commit -m "feat: add scope switcher and filament scope integration"
```

### Task 8: Final Verification and Documentation

**Files:**
- Modify: `README.md` (new section: Global Scopes)
- Create: `docs/architecture/global-scopes.md` (optional deeper architecture note)

- [ ] **Step 1: Add user-facing documentation**

```md
## Global Scopes

Zephyr supports multiple flat operational scopes (e.g. `company`, `school`).
Users and product catalog are global; operational records are isolated by active scope.
```

- [ ] **Step 2: Run full regression suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 3: Run formatting/lint if configured**

Run: `vendor/bin/pint --dirty --format=agent`
Expected: PASS.

- [ ] **Step 4: Commit docs + final adjustments**

```bash
git add README.md docs app tests database
git commit -m "feat: implement global scopes v1 with isolation and switching"
```

- [ ] **Step 5: Manual acceptance checklist**

Run through UI:
- Login with user assigned to two scopes.
- Verify default active scope selection.
- Create movement/task/inventory and confirm `scope_id`.
- Switch scope and verify previous records disappear.
- Verify shared catalog entities remain available.

Expected: all acceptance criteria from spec are satisfied.

## Spec Coverage Check

- Domain `scopes` + `type`: Tasks 2, 4.
- Multi-scope user access: Tasks 2, 4.
- Active scope runtime and switching: Tasks 4, 7.
- Operational isolation with `scope_id`: Tasks 3, 5, 6.
- Security (policy + bypass constraints): Tasks 6, 7.
- Rollout and testing: Tasks 1, 8.

## Notes

- Keep `multi_scope_enabled` feature flag wrapping middleware/resource behavior until production migration is complete.
- If existing Spatie setup already supports teams, map `scope_id` to team key instead of introducing parallel role pivots.
