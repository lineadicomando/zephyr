<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Team of the assignments valid in every scope (Scope::GLOBAL_PERMISSIONS_TEAM).
     */
    private const GLOBAL_TEAM = 0;

    /**
     * Enable the spatie/permission teams, the team being the scope. Fresh
     * installs already get the team columns from the permission tables
     * migration. On existing installs super_admin stays global, the other
     * role assignments are copied into every scope of their user (they stay
     * global for users without scopes) and direct permissions stay global.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $team = config('permission.column_names.team_foreign_key');

        if (Schema::hasColumn($tableNames['model_has_roles'], $team)) {
            return;
        }

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($team): void {
            $table->unsignedBigInteger($team)->nullable()->after('id');
            $table->index($team, 'roles_team_foreign_key_index');
            $table->dropUnique(['name', 'guard_name']);
            $table->unique([$team, 'name', 'guard_name']);
        });

        $superAdminRoleIds = DB::table($tableNames['roles'])->where('name', 'super_admin')->pluck('id')->all();
        $scopeIdsByUser = DB::table('scope_user')->get(['user_id', 'scope_id'])->groupBy('user_id')
            ->map(fn (Collection $rows): array => $rows->pluck('scope_id')->all());

        $roleAssignments = DB::table($tableNames['model_has_roles'])->get()
            ->flatMap(function (object $row) use ($team, $superAdminRoleIds, $scopeIdsByUser): array {
                $scopeIds = in_array($row->role_id, $superAdminRoleIds)
                    ? []
                    : ($scopeIdsByUser[$row->model_id] ?? []);

                return collect($scopeIds ?: [self::GLOBAL_TEAM])
                    ->map(fn (int|string $scopeId): array => [...(array) $row, $team => (int) $scopeId])
                    ->all();
            });
        $permissionAssignments = DB::table($tableNames['model_has_permissions'])->get()
            ->map(fn (object $row): array => [...(array) $row, $team => self::GLOBAL_TEAM]);

        $this->recreatePivotTable('model_has_roles', 'role_id', 'roles', $team, $roleAssignments);
        $this->recreatePivotTable('model_has_permissions', 'permission_id', 'permissions', $team, $permissionAssignments);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Back to global assignments: the team of every assignment is dropped.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $team = config('permission.column_names.team_foreign_key');

        if (! Schema::hasColumn($tableNames['model_has_roles'], $team)) {
            return;
        }

        $withoutTeam = fn (string $table): Collection => DB::table($tableNames[$table])->get()
            ->map(fn (object $row): array => collect((array) $row)->except($team)->all())
            ->unique(fn (array $row): string => implode('|', $row))
            ->values();

        $this->recreatePivotTable('model_has_roles', 'role_id', 'roles', null, $withoutTeam('model_has_roles'));
        $this->recreatePivotTable('model_has_permissions', 'permission_id', 'permissions', null, $withoutTeam('model_has_permissions'));

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($team): void {
            $table->dropUnique([$team, 'name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');
            $table->unique(['name', 'guard_name']);
        });
        Schema::table($tableNames['roles'], function (Blueprint $table) use ($team): void {
            $table->dropColumn($team);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * The team column is part of the primary key: the pivot table is created
     * again, as in the permission tables migration, and refilled.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function recreatePivotTable(string $pivot, string $key, string $related, ?string $team, Collection $rows): void
    {
        $tableNames = config('permission.table_names');

        Schema::drop($tableNames[$pivot]);

        Schema::create($tableNames[$pivot], function (Blueprint $table) use ($pivot, $key, $related, $team, $tableNames): void {
            $table->unsignedBigInteger($key);
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], "{$pivot}_model_id_model_type_index");
            $table->foreign($key)->references('id')->on($tableNames[$related])->cascadeOnDelete();

            $primaryName = $pivot === 'model_has_roles' ? 'model_has_roles_role_model_type_primary' : 'model_has_permissions_permission_model_type_primary';

            if ($team !== null) {
                $table->unsignedBigInteger($team);
                $table->index($team, "{$pivot}_team_foreign_key_index");
                $table->primary([$team, $key, 'model_id', 'model_type'], $primaryName);
            } else {
                $table->primary([$key, 'model_id', 'model_type'], $primaryName);
            }
        });

        $rows->chunk(500)->each(fn (Collection $chunk) => DB::table($tableNames[$pivot])->insert($chunk->values()->all()));
    }
};
