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
    /**
     * @param  int  $id  Scholarship id.
     * @param  string  $scholarship_name  Descriptive scholarship name.
     * @param  string|null  $institution  Organiser.
     * @param  EducationLevel  $level  Target education level.
     * @param  string|null  $location  Free text location.
     * @param  LocationScope|null  $scope  Domestic or international study.
     * @param  bool  $sktm_require  Whether an SKTM/KHI is required.
     * @param  float|null  $minimum_value  Minimum GPA.
     * @param  int|null  $maximum_age  Maximum applicant age.
     * @param  string|null  $closing_date  Registration deadline (Y-m-d).
     * @param  int|null  $estimated_quota  Estimated awardees.
     * @param  string|null  $link  Link to the official source.
     * @param  string|null  $image_url  Public URL of the optional card image; null shows the branded placeholder.
     * @param  ScholarshipStatus  $status  Admin status.
     * @param  bool  $is_open  Whether registration is open today.
     * @param  int|null  $days_remaining  Days until the deadline.
     */
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
        public ?string $image_url,
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
            image_url: $scholarship->image_url,
            status: $scholarship->status,
            is_open: $scholarship->isOpen(),
            days_remaining: $scholarship->daysRemaining(),
        );
    }
}
