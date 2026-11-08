<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single selectable answer option of a brainstorming question.
 *
 * The stable identifier is what the mentee answer stores. The label can be
 * revised by the curriculum team without changing the meaning of historical
 * answers.
 */
class BrainstormOptionData extends Data
{
    /**
     * @param  string  $id  Stable identifier stored in mentee answers.
     * @param  string  $label  Text shown to the mentee.
     * @param  string  $value  Value submitted when the option is picked.
     * @param  int  $order  Display order inside the question.
     * @param  bool  $active  Whether mentees can still pick this option.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $value,
        public int $order = 0,
        public bool $active = true,
    ) {}
}
