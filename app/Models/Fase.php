<?php

namespace App\Models;

use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\FaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property int $fase_order
 * @property string|null $short_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $modules_count
 */
#[Fillable(['title', 'subtitle', 'fase_order', 'short_description'])]
class Fase extends Model
{
    /** @use HasFactory<FaseFactory> */
    use HasFactory;

    /**
     * Flush cached curriculum when the fase order or content changes.
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            ContentCache::bump();
        });

        static::deleted(function (): void {
            ContentCache::bump();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fase_order' => 'integer',
        ];
    }

    /**
     * Scope the query to fases in curriculum order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('fase_order')->orderBy('id');
    }

    /**
     * Get the modules that belong to this fase.
     *
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('modul_order')->orderBy('id');
    }
}
