<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

enum AdminRole: string implements HasLabel
{
    use HasSelectOptions;

    case SuperAdmin = 'super_admin';
    case CoAdmin = 'co_admin';
    case StaffDivisi = 'staff_divisi';

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
