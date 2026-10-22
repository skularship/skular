<?php

namespace App\Data;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use Spatie\LaravelData\Data;

/**
 * Serializable representation of a scholarship. Models are never passed between layers.
 */
class ScholarshipData extends Data
{
    public function __construct(
        public int $id,
        public string $scholarship_name,
        public ?string $institution,
        public EducationLevel $level,
        public ?string $location,
        public ?LocationScope $scope,
        public bool $sktm_require,
        public ?float $minimum_value,
        public ?int $maximum_age,
        public ?string $closing_date,
        public ?int $estimated_quota,
        public ?string $link,
        public ScholarshipStatus $status,
        public bool $is_open,
        public ?int $days_remaining,
    ) {}

    /**
     * Build the DTO from a persisted scholarship.
     */
    public static function fromModel(Scholarship $scholarship): self
    {
        return new self(
            id: $scholarship->id,
            scholarship_name: $scholarship->scholarship_name,
            institution: $scholarship->institution,
            level: $scholarship->level,
            location: $scholarship->location,
            scope: $scholarship->scope,
            sktm_require: $scholarship->sktm_require,
            minimum_value: $scholarship->minimumGpa(),
            maximum_age: $scholarship->maximum_age,
            closing_date: $scholarship->closing_date?->toDateString(),
            estimated_quota: $scholarship->estimated_quota,
            link: $scholarship->link,
            status: $scholarship->status,
            is_open: $scholarship->isOpen(),
            days_remaining: $scholarship->daysRemaining(),
        );
    }
}
