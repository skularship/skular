<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single section of the mentee CV, sourced from an admin-configured practice form.
 */
class CvSectionData extends Data
{
    /**
     * @param  array<int, array{label: string, value: string}>  $entries
     */
    public function __construct(
        public int $practice_form_id,
        public ?int $module_id,
        public string $title,
        public ?string $module_title,
        public array $entries,
    ) {}
}
