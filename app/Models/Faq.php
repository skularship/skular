<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A frequently asked question shown on the public FAQ page.
 *
 * Publication is deliberately modelled in one place (the `published` scope)
 * so the landing page, the FAQ page, and the category counts can never
 * disagree about whether an answer is allowed to be read.
 *
 * @property int $id
 * @property int $faq_category_id
 * @property string $question
 * @property string $answer
 * @property bool $is_published
 * @property bool $is_featured
 * @property int $sort_order
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FaqCategory $category
 */
#[Fillable([
    'faq_category_id',
    'question',
    'answer',
    'is_published',
    'is_featured',
    'sort_order',
    'published_at',
])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    /**
     * Stamp the publication time the first time an FAQ goes live.
     */
    protected static function booted(): void
    {
        static::saving(function (Faq $faq): void {
            if ($faq->is_published && $faq->published_at === null) {
                $faq->published_at = now();
            }
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
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Scope the query to FAQs the public may read.
     *
     * The editorial flag, the publication timestamp, and an active category
     * are all required, so a draft or a retired group can never leak through
     * a category filter or a search.
     *
     * @param  Builder<Faq>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        self::applyPublished($query);
    }

    /**
     * Apply the public visibility rules to any FAQ query builder.
     *
     * The scope and relation-count closures share this method, so aggregate
     * counts cannot drift from the rows the public page actually shows.
     *
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public static function applyPublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereHas('category', function (Builder $category): void {
                $category->where('is_active', true);
            });
    }

    /**
     * Scope the query to FAQs the admin prioritised for the landing page.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Scope the query to a deterministic display order.
     *
     * The id tie-breaker keeps the order stable when admins assign the same
     * `sort_order` to more than one FAQ.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope the query to one category slug.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, string $slug): void
    {
        $query->whereHas('category', function (Builder $category) use ($slug): void {
            $category->where('slug', $slug)->where('is_active', true);
        });
    }

    /**
     * Scope the query to questions or answers containing the search term.
     *
     * Wildcard characters are removed from the term so they can only act as
     * literal text, never as an accidental match-all pattern.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim(str_replace(['%', '_'], ' ', $term));

        if ($term === '') {
            return;
        }

        $pattern = '%'.$term.'%';

        $query->where(function (Builder $inner) use ($pattern): void {
            $inner->where('question', 'like', $pattern)
                ->orWhere('answer', 'like', $pattern);
        });
    }

    /**
     * Get the category this FAQ belongs to.
     *
     * @return BelongsTo<FaqCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
