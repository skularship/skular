<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Roles of the Filament admin panel (spatie/laravel-permission via Shield).
 *
 * super_admin bypasses every policy; co_admin and staff_divisi only get the
 * permissions assigned by RoleSeeder.
 */
enum AdminRole: string implements HasLabel
{
    use HasSelectOptions;

    case SuperAdmin = 'super_admin';
    case CoAdmin = 'co_admin';
    case StaffDivisi = 'staff_divisi';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::CoAdmin => 'Co-Admin',
            self::StaffDivisi => 'Staff Divisi',
        };
    }

    /**
     * Get every role name as a plain string list.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
