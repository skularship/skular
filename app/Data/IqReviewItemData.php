<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One reviewed Tes IQ question with its key and explanation, built only by
 * IqResultService::review() for mentees with paid access.
 */
class IqReviewItemData extends Data
{
    /**
     * @param  int  $position  1-based place in the attempt.
     * @param  string  $subtest_label  Subtest label.
     * @param  string|null  $passage  Reading text.
     * @param  string  $prompt  Question text.
     * @param  string|null  $image_url  Visuospasial image.
     * @param  string|null  $image_alt  Alt text.
     * @param  array<string, string>  $options  Letter => text.
     * @param  string|null  $choice  The mentee's choice.
     * @param  string|null  $answer_key  Correct letter.
     * @param  bool  $is_correct  Whether the choice matched the key.
     * @param  string|null  $explanation  Team explanation.
     */
    public function __construct(
        public int $position,
        public string $subtest_label,
        public ?string $passage,
        public string $prompt,
        public ?string $image_url,
        public ?string $image_alt,
        public array $options,
        public ?string $choice,
        public ?string $answer_key,
        public bool $is_correct,
        public ?string $explanation,
    ) {}
}
