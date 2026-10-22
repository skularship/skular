<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Demo staff accounts. Passwords are intentionally simple for local use only.
     *
     * @var array<int, array{name: string, email: string, role: AdminRole}>
     */
    public const ADMINS = [
        ['name' => 'Super Admin Skular', 'email' => 'admin@skular.test', 'role' => AdminRole::SuperAdmin],
        ['name' => 'Co Admin Skular', 'email' => 'coadmin@skular.test', 'role' => AdminRole::CoAdmin],
        ['name' => 'Staff Divisi Skular', 'email' => 'staff@skular.test', 'role' => AdminRole::StaffDivisi],
    ];

    /**
     * Seed the application's admin users.
     */
    public function run(): void
    {
        foreach (self::ADMINS as $admin) {
            $user = User::query()->updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'profile_completed_at' => now(),
                ],
            );

            $user->syncRoles([$admin['role']->value]);
        }
    }
}
