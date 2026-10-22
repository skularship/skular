<?php

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\ScholarshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $scholarship_name
 * @property string|null $institution
 * @property int|null $cluster_id
 * @property EducationLevel $level
 * @property string|null $location
 * @property LocationScope|null $scope
 * @property bool $sktm_require
 * @property string|null $minimum_value
 * @property int|null $maximum_age
 * @property CarbonImmutable|null $opening_date
 * @property CarbonImmutable|null $closing_date
 * @property int|null $estimated_quota
 * @property string|null $link
 * @property CarbonImmutable|null $verification_date
 * @property string|null $note
 * @property array<int, string>|null $key_benefits
 * @property array<int, string>|null $values_sought
 * @property ScholarshipStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'scholarship_name',
    'institution',
    'cluster_id',
    'level',
    'location',
    'scope',
    'sktm_require',
    'minimum_value',
    'maximum_age',
    'opening_date',
    'closing_date',
    'estimated_quota',
    'link',
    'verification_date',
    'note',
    'key_benefits',
    'values_sought',
    'status',
])]
class Scholarship extends Model
{
    /** @use HasFactory<ScholarshipFactory> */
    use HasFactory;

    /**
     * Flush cached public catalog data when a scholarship changes.
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
            'level' => EducationLevel::class,
            'scope' => LocationScope::class,
            'status' => ScholarshipStatus::class,
            'sktm_require' => 'boolean',
            'minimum_value' => 'decimal:2',
            'maximum_age' => 'integer',
            'estimated_quota' => 'integer',
            'opening_date' => 'date',
            'closing_date' => 'date',
            'verification_date' => 'date',
            'key_benefits' => 'array',
            'values_sought' => 'array',
        ];
    }

    /**
     * Scope the query to scholarships mentees can apply for.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereIn('status', [ScholarshipStatus::Active, ScholarshipStatus::Closed]);
    }

    /**
     * Scope the query to scholarships currently accepting registrations.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', ScholarshipStatus::Active)
            ->where(fn (Builder $query) => $query
                ->whereNull('closing_date')
                ->orWhereDate('closing_date', '>=', CarbonImmutable::today()));
    }

    /**
     * Determine whether the scholarship is currently accepting registrations.
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen($this->closing_date);
    }

    /**
     * Get the sanitised list of benefits the scholarship provides.
     *
     * @return array<int, string>
     */
    public function keyBenefits(): array
    {
        return $this->sanitisedList($this->key_benefits);
    }

    /**
     * Get the sanitised list of values the organisers look for.
     *
     * @return array<int, string>
     */
    public function valuesSought(): array
    {
        return $this->sanitisedList($this->values_sought);
    }

    /**
     * Determine whether the scholarship has any benefit information to show.
     */
    public function hasKeyBenefits(): bool
    {
        return $this->keyBenefits() !== [];
    }

    /**
     * Determine whether the scholarship has any values information to show.
     */
    public function hasValuesSought(): bool
    {
        return $this->valuesSought() !== [];
    }

    /**
     * Trim and drop empty entries from a stored JSON list.
     *
     * @param  array<int, mixed>|null  $list
     * @return array<int, string>
     */
    private function sanitisedList(?array $list): array
    {
        if ($list === null) {
            return [];
        }

        $items = [];

        foreach ($list as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $value = trim((string) $item);

            if ($value !== '') {
                $items[] = $value;
            }
        }

        return $items;
    }

    /**
     * Get the remaining days until the registration deadline.
     */
    public function daysRemaining(): ?int
    {
        if ($this->closing_date === null) {
            return null;
        }

        return max(0, (int) CarbonImmutable::today()->diffInDays($this->closing_date, false));
    }

    /**
     * Get the minimum GPA required, or null when unrestricted.
     */
    public function minimumGpa(): ?float
    {
        return $this->minimum_value === null ? null : (float) $this->minimum_value;
    }

    /**
     * Get the field of study this scholarship targets, when specified.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * Get the mentee applications for this scholarship.
     *
     * @return HasMany<ScholarshipApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    /**
     * Get the template learning modules tailored to this scholarship.
     *
     * @return HasMany<Module, $this>
     */
    public function templateModules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('modul_order')->orderBy('id');
    }

    /**
     * Get the mentees applying for this scholarship.
     *
     * @return BelongsToMany<User, $this>
     */
    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'scholarship_applications')
            ->withPivot(['id', 'status', 'is_primary', 'selected_at', 'submitted_at', 'result'])
            ->withTimestamps();
    }
}
