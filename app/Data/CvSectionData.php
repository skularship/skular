<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single section of the mentee CV, sourced from an admin-configured practice form.
 *
 * Also feeds the read-only "Data dirimu" reference panel of a module, where
 * a source the mentee has not answered yet is returned with `filled = false`.
 */
class CvSectionData extends Data
{
    /**
     * @param  int  $practice_form_id  Source practice form.
     * @param  int|null  $module_id  Module that owns the source form (where the mentee edits the answer).
     * @param  string  $title  Form title.
     * @param  string|null  $module_title  Title of the module that owns the form.
     * @param  array<int, array{label: string, value: string}>  $entries  Labelled answers in form schema order.
     * @param  string|null  $button_label  Admin text for the "Tampilkan ..." button; null = use the title.
     * @param  bool  $filled  Whether the mentee has any displayable answer for this source.
     */
    public function __construct(
        public int $practice_form_id,
        public ?int $module_id,
        public string $title,
        public ?string $module_title,
        public array $entries,
        public ?string $button_label = null,
        public bool $filled = true,
    ) {}

    /**
     * Get the text of the button that opens this source in the reference panel.
     *
     * @return string For example "Tampilkan pengalamanku".
     */
    public function buttonText(): string
    {
        $label = trim((string) $this->button_label);

        return 'Tampilkan '.($label !== '' ? $label : $this->title);
    }
}
