<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TwibbonTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $image_path
 * @property string|null $font_path
 * @property array<int, array<string, mixed>>|null $placements
 * @property bool $is_active
 * @property int $ordering
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name',
    'description',
    'image_path',
    'font_path',
    'placements',
    'is_active',
    'ordering',
])]
class TwibbonTemplate extends Model
{
    /** @use HasFactory<TwibbonTemplateFactory> */
    use HasFactory;

    /**
     * The text placements used when a template does not define its own.
     *
     * @var array<int, array<string, mixed>>
     */
    public const DEFAULT_PLACEMENTS = [
        [
            'key' => 'user_name',
            'label' => 'Nama Mentee',
            'x' => 0.5,
            'y' => 0.66,
            'font_size' => 54,
            'color' => '#FFFFFF',
            'align' => 'center',
        ],
        [
            'key' => 'scholarship_name',
            'label' => 'Nama Beasiswa',
            'x' => 0.5,
            'y' => 0.76,
            'font_size' => 30,
            'color' => '#F2C94C',
            'align' => 'center',
        ],
        [
            'key' => 'institution',
            'label' => 'Institusi',
            'x' => 0.5,
            'y' => 0.83,
            'font_size' => 22,
            'color' => '#FFFFFF',
            'align' => 'center',
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placements' => 'array',
            'is_active' => 'boolean',
            'ordering' => 'integer',
        ];
    }

    /**
     * Scope the query to active templates in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activeOrdered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('ordering')->orderBy('name');
    }

    /**
     * Get the configured placements, falling back to the defaults.
     *
     * @return array<int, array<string, mixed>>
     */
    public function placementsOrDefault(): array
    {
        return $this->placements ?: self::DEFAULT_PLACEMENTS;
    }
}
