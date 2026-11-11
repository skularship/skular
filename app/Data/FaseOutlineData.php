<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Name-only view of a fase the mentee has not reached yet.
 *
 * Deliberately carries no module data, so a locked fase can never leak its
 * module titles to the browser.
 */
class FaseOutlineData extends Data
{
    /**
     * @param  int  $id  Fase id.
     * @param  string  $title  Fase title.
     * @param  string|null  $subtitle  Short fase subtitle.
     * @param  int  $fase_order  Position on the roadmap.
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $subtitle,
        public int $fase_order,
    ) {}

    /**
     * Strip a fase down to its name, dropping every module.
     */
    public static function fromProgress(FaseProgressData $fase): self
    {
        return new self(
            id: $fase->id,
            title: $fase->title,
            subtitle: $fase->subtitle,
            fase_order: $fase->fase_order,
        );
    }
}
