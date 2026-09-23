<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Affixes of the permission names, the longest first so that
     * view_any_product is read as view_any + product.
     *
     * @var list<string>
     */
    private const AFFIXES = [
        'force_delete_any', 'force_delete', 'delete_any', 'restore_any', 'view_any',
        'create', 'update', 'delete', 'restore', 'replicate', 'reorder', 'view', 'transition',
    ];

    /**
     * Filament Shield 4.3 rejects the snake_case permission names used so far
     * (view_any_product): rename them to its default Affix:Subject format
     * (ViewAny:Product). Role and user assignments follow the permission id.
     */
    public function up(): void
    {
        $this->rename(function (string $name): ?string {
            foreach (self::AFFIXES as $affix) {
                if (str_starts_with($name, "{$affix}_") && strlen($name) > strlen($affix) + 1) {
                    return Str::studly($affix).':'.Str::studly(substr($name, strlen($affix) + 1));
                }
            }

            return null;
        });
    }

    public function down(): void
    {
        $this->rename(fn (string $name): ?string => str_contains($name, ':')
            ? implode('_', array_map(fn (string $part): string => Str::snake($part), explode(':', $name, 2)))
            : null);
    }

    /**
     * Rename every permission $newName() returns a name for. When a permission
     * with the new name already exists, its assignments are merged into it.
     *
     * @param  Closure(string): ?string  $newName
     */
    private function rename(Closure $newName): void
    {
        $tableNames = config('permission.table_names');

        DB::transaction(function () use ($newName, $tableNames): void {
            foreach (DB::table($tableNames['permissions'])->orderBy('id')->get() as $permission) {
                $name = $newName($permission->name);

                if ($name === null) {
                    continue;
                }

                $existingId = DB::table($tableNames['permissions'])
                    ->where('name', $name)
                    ->where('guard_name', $permission->guard_name)
                    ->value('id');

                if ($existingId === null) {
                    DB::table($tableNames['permissions'])->where('id', $permission->id)->update(['name' => $name]);

                    continue;
                }

                foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                    $keys = DB::table($tableNames[$pivot])->where('permission_id', $existingId)->get()
                        ->map(fn (object $row): string => json_encode(collect((array) $row)->except('permission_id')->sortKeys()));

                    DB::table($tableNames[$pivot])->where('permission_id', $permission->id)->get()
                        ->reject(fn (object $row): bool => $keys->contains(json_encode(collect((array) $row)->except('permission_id')->sortKeys())))
                        ->each(fn (object $row) => DB::table($tableNames[$pivot])->insert([...(array) $row, 'permission_id' => $existingId]));
                }

                DB::table($tableNames['permissions'])->where('id', $permission->id)->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
