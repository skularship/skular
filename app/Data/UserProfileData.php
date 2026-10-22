<?php

namespace App\Data;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\User;
use Spatie\LaravelData\Data;

/**
 * Immutable snapshot of the mentee profile used to match scholarships.
 */
class UserProfileData extends Data
{
    public function __construct(
        public EducationLevel $level,
        public int $age,
        public ?float $gpa,
        public bool $has_sktm,
        public EducationLevel $desired_level,
        public LocationScope $desired_location,
        public ?int $cluster_id = null,
        public ?string $cluster_name = null,
    ) {}

    /**
     * Build the DTO from a persisted mentee.
     */
    public static function fromUser(User $user): self
    {
        return new self(
            level: $user->level ?? EducationLevel::S1,
            age: $user->age ?? 0,
            gpa: $user->gpa === null ? null : (float) $user->gpa,
            has_sktm: (bool) $user->has_sktm,
            desired_level: $user->desired_level ?? EducationLevel::S1,
            desired_location: $user->desired_location ?? LocationScope::DalamNegeri,
            cluster_id: $user->cluster_id,
            cluster_name: $user->cluster?->label,
        );
    }

    /**
     * Determine whether every profiling field required for matching is present.
     */
    public function isComplete(): bool
    {
        return $this->age > 0;
    }
}
