<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

enum PracticeFieldType: string implements HasLabel
{
    use HasSelectOptions;

    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Date = 'date';
    case Url = 'url';
    case File = 'file';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Teks Singkat',
            self::Textarea => 'Teks Panjang',
            self::Number => 'Angka',
            self::Select => 'Pilihan Dropdown',
            self::Radio => 'Pilihan Tunggal',
            self::Checkbox => 'Pilihan Ganda',
            self::Date => 'Tanggal',
            self::Url => 'URL / Link',
            self::File => 'Unggah File',
        };
    }

    /**
     * Determine whether this field type needs a list of options.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }

    /**
     * Get the validation rules implied by this field type.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::Text => ['string', 'max:255'],
            self::Textarea => ['string', 'max:5000'],
            self::Number => ['numeric'],
            self::Select, self::Radio => ['string'],
            self::Checkbox => ['array'],
            self::Date => ['date'],
            self::Url => ['url', 'max:2048'],
            self::File => ['file', 'max:5120'],
        };
    }
}
