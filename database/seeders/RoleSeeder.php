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
     * Read and update existing records without creating or deleting them (report review).
     *
     * @var array<int, string>
     */
    private const REVIEW = ['ViewAny', 'View', 'Update'];

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
            'ScholarshipInfoReport' => self::MANAGE,
            'SupportTicket' => self::MANAGE,
            'Faq' => self::MANAGE,
            'FaqCategory' => self::MANAGE,
            'UserPracticeResponse' => self::READ,
            'Checkpoint' => self::READ,
            'ModuleFeedback' => self::READ,
            'User' => self::READ,
            // Money: co-admin reads orders and notifications and can retry one, edits prices, and may give Pro by hand.
            'Product' => self::REVIEW,
            'Order' => self::READ,
            'Subscription' => self::WRITE,
            'PaymentEvent' => self::REVIEW,
            // Events: full management incl. "Duplikat event"; participants are created by mentees, admins only mark attendance.
            'Event' => [...self::MANAGE, 'Replicate'],
            'EventRegistration' => self::REVIEW,
            // Try Out: full content management; attempts are read, "Nilai ulang" and closing a stuck attempt need Update.
            'Tryout' => [...self::MANAGE, 'Replicate'],
            'TryoutUnit' => self::MANAGE,
            'TryoutQuestion' => [...self::MANAGE, 'Replicate'],
            'TryoutAttempt' => self::REVIEW,
            // Tes IQ: full management of tests and the question bank (keys, explanations, images).
            'IqTest' => self::MANAGE,
            'IqQuestion' => self::MANAGE,
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
            // Staff may review reports (change status) but never delete them.
            'ScholarshipInfoReport' => self::REVIEW,
            // Staff may handle tickets (change status) but never delete them.
            'SupportTicket' => self::REVIEW,
            'UserPracticeResponse' => self::READ,
            'Checkpoint' => self::READ,
            'ModuleFeedback' => self::READ,
            'Event' => [...self::WRITE, 'Replicate'],
            'EventRegistration' => self::REVIEW,
            'Tryout' => [...self::WRITE, 'Replicate'],
            'TryoutUnit' => self::WRITE,
            'TryoutQuestion' => [...self::WRITE, 'Replicate'],
            'TryoutAttempt' => self::READ,
            'IqTest' => self::WRITE,
            'IqQuestion' => self::WRITE,
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
