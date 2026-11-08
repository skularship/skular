<?php

namespace App\Models;

use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\ModuleCoreSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One titled column of a module's "Inti Materi".
 *
 * @property int $id
 * @property int $module_id
 * @property string $subtitle
 * @property string $body
 * @property int $ordering
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['module_id', 'subtitle', 'body', 'ordering'])]
class ModuleCoreSection extends Model
{
    /** @use HasFactory<ModuleCoreSectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordering' => 'integer',
        ];
    }

    /**
     * Invalidate cached curriculum reads whenever module content changes.
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
     * Get the module this column belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
