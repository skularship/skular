<?php

namespace App\Data;

use App\Models\IqQuestion;
use Spatie\LaravelData\Data;

/**
 * The only shape of a Tes IQ question that reaches the runner. Built field
 * by field so the key and explanation can never leak into Livewire state
 * or HTML.
 */
class IqQuestionViewData extends Data
{
    /**
     * @param  int  $position  1-based place in the attempt (navigation uses it).
     * @param  int  $number  Number of the question in the bank.
     * @param  int  $total  Questions in the attempt.
     * @param  string  $subtest  IqSubtest value.
     * @param  string  $subtest_label  Label shown above the question.
     * @param  string|null  $passage  Reading text (Teks).
     * @param  string  $prompt  Question text.
     * @param  string|null  $image_url  Visuospasial image.
     * @param  string|null  $image_alt  Alt text of the image.
     * @param  array<string, string>  $options  Letter => text.
     * @param  string|null  $choice  The mentee's saved choice.
     */
    public function __construct(
        public int $position,
        public int $number,
        public int $total,
        public string $subtest,
        public string $subtest_label,
        public ?string $passage,
        public string $prompt,
        public ?string $image_url,
        public ?string $image_alt,
        public array $options,
        public ?string $choice,
    ) {}

    /**
     * Build the view of a question without any key data.
     */
    public static function fromQuestion(IqQuestion $question, int $position, int $total, ?string $choice): self
    {
        return new self(
            position: $position,
            number: $question->number,
            total: $total,
            subtest: $question->subtest->value,
            subtest_label: $question->subtest->getLabel(),
            passage: filled($question->passage) ? (string) $question->passage : null,
            prompt: $question->prompt,
            image_url: $question->imageUrl(),
            image_alt: filled($question->image_alt) ? (string) $question->image_alt : null,
            options: $question->optionList(),
            choice: $choice,
        );
    }
}
