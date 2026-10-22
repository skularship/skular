<?php

namespace App\Data;

use App\Enums\BrainstormQuestionType;
use Spatie\LaravelData\Data;

/**
 * A single brainstorming question configured by the curriculum team.
 */
class BrainstormQuestionData extends Data
{
    /**
     * @param  array<int, BrainstormOptionData>  $options
     */
    public function __construct(
        public string $id,
        public string $question,
        public BrainstormQuestionType $type,
        public array $options = [],
        public bool $required = false,
        public bool $active = true,
        public int $order = 0,
        public ?string $hint = null,
    ) {}

    /**
     * Determine whether the mentee must answer this question.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}
