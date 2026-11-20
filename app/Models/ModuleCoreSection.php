<?php

namespace App\Models;

use App\Enums\MaterialFormat;
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
 * @property MaterialFormat $body_format
 * @property int $ordering
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Module $module
 */
#[Fillable(['module_id', 'subtitle', 'body', 'body_format', 'ordering'])]
class ModuleCoreSection extends Model
{
    /** @use HasFactory<ModuleCoreSectionFactory> */
    use HasFactory;

    /**
     * Mirror the column default so a new, unsaved column is Markdown too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'body_format' => 'markdown',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body_format' => MaterialFormat::class,
            'ordering' => 'integer',
        ];
    }

    /**
     * Determine whether the body has readable text.
     *
     * Rich editor HTML such as "<p></p>" or "<p>&nbsp;</p>" counts as empty,
     * so an admin who clears a column does not publish a blank section.
     *
     * @return bool True when the body contains visible text.
     */
    public function hasBody(): bool
    {
        $body = (string) $this->body;

        if ($this->body_format === MaterialFormat::Html) {
            $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim(str_replace("\u{00A0}", ' ', $body)) !== '';
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
