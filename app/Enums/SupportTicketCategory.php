<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Kind of problem a mentee reports from the "Bantuan" page.
 */
enum SupportTicketCategory: string implements HasLabel
{
    use HasSelectOptions;

    case Bug = 'bug';
    case Error = 'error';
    case Account = 'account';
    case Learning = 'learning';
    case Ai = 'ai';
    case Scholarship = 'scholarship';
    case Other = 'other';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Bug => 'Bug / tampilan tidak sesuai',
            self::Error => 'Error / halaman gagal dimuat',
            self::Account => 'Akun & login',
            self::Learning => 'Modul & materi belajar',
            self::Ai => 'Fitur AI (penilaian, Mentor, Brainstorming)',
            self::Scholarship => 'Data atau pilihan beasiswa',
            self::Other => 'Lainnya',
        };
    }
}
