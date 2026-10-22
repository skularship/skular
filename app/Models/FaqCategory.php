<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FaqCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A public grouping of frequently asked questions.
 *
 * Categories are editable from the admin panel so the product team can add,
 * rename, reorder, or retire a group without a deployment.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $faqs_count
 */
#[Fillable(['name', 'slug', 'description', 'sort_order', 'is_active'])]
class FaqCategory extends Model
{
    /** @use HasFactory<FaqCategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Keep categories that are still referenced by FAQs.
     *
     * The foreign key also restricts deletion, but returning false here gives
     * the application a graceful failure instead of a database exception.
     */
    protected static function booted(): void
    {
        static::deleting(function (FaqCategory $category): bool {
            return ! $category->isInUse();
        });
    }

    /**
     * Determine whether any FAQ still belongs to this category.
     *
     * This always asks the database so a delete request cannot rely on a
     * stale count that was loaded earlier. The admin table uses its eager-
     * loaded count only to disable the button in the UI.
     */
    public function isInUse(): bool
    {
        return $this->faqs()->exists();
    }

    /**
     * Scope the query to active categories in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope the query to categories in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }

    /**
     * Get the FAQs in this category.
     *
     * @return HasMany<Faq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
