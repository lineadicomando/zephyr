<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Affixes of the permissions Shield generates for a resource.
     *
     * @var list<string>
     */
    private const RESOURCE_AFFIXES = [
        'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
        'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder',
    ];

    /**
     * Global catalog subjects added by the checklists: only super admins
     * change them.
     *
     * @var list<string>
     */
    private const CATALOG_SUBJECTS = ['ChecklistTemplate', 'Tag'];

    private const ANOMALIES_WIDGET = 'View:ChecklistAnomaliesWidget';

    /**
     * Create the permissions of the checklist templates, the tags and the
     * anomalies widget, and add them to the preset roles as the roles seeder
     * would: every permission to the super admins, the read permissions and
     * the widget to the admins, the widget to the users. The other
     * permissions of the roles are left as they are.
     */
    public function up(): void
    {
        $readPermissions = [];
        $allPermissions = [self::ANOMALIES_WIDGET];

        foreach (self::CATALOG_SUBJECTS as $subject) {
            foreach (self::RESOURCE_AFFIXES as $affix) {
                $allPermissions[] = "{$affix}:{$subject}";
            }

            $readPermissions[] = "ViewAny:{$subject}";
            $readPermissions[] = "View:{$subject}";
        }

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->grant('super_admin', $allPermissions);
        $this->grant('admin', [...$readPermissions, self::ANOMALIES_WIDGET]);
        $this->grant('user', [self::ANOMALIES_WIDGET]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->where(function ($query): void {
                $query->where('name', self::ANOMALIES_WIDGET);

                foreach (self::CATALOG_SUBJECTS as $subject) {
                    $query->orWhere('name', 'like', "%:{$subject}");
                }
            })
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grant(string $roleName, array $permissions): void
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

        $role?->givePermissionTo($permissions);
    }
};
