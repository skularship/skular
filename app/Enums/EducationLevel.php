<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

enum EducationLevel: string implements HasLabel
{
    use HasSelectOptions;

    case SMA = 'sma';
    case SMK = 'smk';
    case D3 = 'd3';
    case D4 = 'd4';
    case S1 = 's1';
    case S2 = 's2';
    case S3 = 's3';

    public function getLabel(): string
    {
        return strtoupper($this->value);
    }

    /**
     * Get the scholarship levels a mentee at this education level is eligible for.
     *
     * @return array<int, self>
     */
    public function matchingLevels(): array
    {
        return match ($this) {
            self::SMA, self::SMK => [self::SMA, self::SMK],
            self::D3, self::D4 => [self::D3, self::D4],
            self::S1 => [self::S1],
            self::S2, self::S3 => [self::S2],
        };
    }

    /**
     * Get the raw scholarship level values this education level is eligible for.
     *
     * @return array<int, string>
     */
    public function matchingLevelValues(): array
    {
        return array_map(fn (self $level): string => $level->value, $this->matchingLevels());
    }
}
