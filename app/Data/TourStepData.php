<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One step of a product tour: the element to highlight and the coach-mark copy.
 *
 * A null or missing target (for example a section that is not on screen, or
 * the desktop sidebar on a phone) shows the step as a centred card instead.
 */
class TourStepData extends Data
{
    /**
     * @param  string|null  $target  CSS selector of the element to highlight; null for a centred card.
     * @param  string  $title  Short heading, Indonesian.
     * @param  string  $body  One or two plain sentences using "kamu".
     */
    public function __construct(
        public ?string $target,
        public string $title,
        public string $body,
    ) {}
}
