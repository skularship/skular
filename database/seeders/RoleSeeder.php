<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Read-only access to a resource.
     *
     * @var array<int, string>
     */
    private const READ = ['ViewAny', 'View'];

    /**
     * Create and edit access without destructive operations.
     *
     * @var array<int, string>
     */
    private const WRITE = ['ViewAny', 'View', 'Create', 'Update'];

    /**
     * Full management of a resource, including deletion and reordering.
     *
     * @var array<int, string>
     */
    private const MANAGE = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Reorder'];

    /**
     * Default permission matrix for each non super-admin role.
     *
     * Super Admin receives every permission through the Filament Shield gate
     * (`filament-shield.super_admin.define_via_gate`), so it is not listed here.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const ROLE_PERMISSIONS = [
        AdminRole::CoAdmin->value => [
            'Fase' => self::MANAGE,
            'Module' => self::MANAGE,
            'PracticeForm' => self::MANAGE,
            'QuickQuestion' => self::MANAGE,
            'TwibbonTemplate' => self::MANAGE,
            'Cluster' => self::MANAGE,
            'Scholarship' => self::MANAGE,
            'ScholarshipApplication' => self::MANAGE,
            'Faq' => self::MANAGE,
            'FaqCategory' => self::MANAGE,
            'UserPracticeResponse' => self::READ,
            'Checkpoint' => self::READ,
            'ModuleFeedback' => self::READ,
            'User' => self::READ,
        ],
        AdminRole::StaffDivisi->value => [
            'Fase' => self::WRITE,
            'Module' => self::WRITE,
            'PracticeForm' => self::WRITE,
            'QuickQuestion' => self::WRITE,
            'TwibbonTemplate' => self::WRITE,
            'Faq' => self::WRITE,
            'FaqCategory' => self::READ,
            'Scholarship' => self::READ,
            'ScholarshipApplication' => self::READ,
            'UserPracticeResponse' => self::READ,
            'Checkpoint' => self::READ,
            'ModuleFeedback' => self::READ,
        ],
    ];

    /**
     * Seed the application's roles and their default permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminRole::values() as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $role_name => $resources) {
            $permissions = [];

            foreach ($resources as $entity => $actions) {
                foreach ($actions as $action) {
                    $permissions[] = Permission::findOrCreate("{$action}:{$entity}", 'web');
                }
            }

            Role::findByName($role_name, 'web')->syncPermissions($permissions);
        }

        Role::findByName(AdminRole::SuperAdmin->value, 'web')
            ->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
