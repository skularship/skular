<?php

namespace App\Data;

use App\Models\TryoutUnit;
use Spatie\LaravelData\Data;

/**
 * The passage or audio card of the active block as the mentee sees it.
 * Never carries the Listening transcript.
 */
class TryoutUnitViewData extends Data
{
    /**
     * @param  string  $code  Unit code, used to count audio plays.
     * @param  string  $title  Unit title.
     * @param  string|null  $context  Short context line.
     * @param  string|null  $passage  Reading passage (Markdown).
     * @param  string|null  $audio_url  Public audio URL (Listening).
     * @param  int|null  $audio_duration_seconds  Audio length.
     * @param  string|null  $image_url  Map/chart image.
     * @param  array{columns?: array<int, string>, rows?: array<int, array<int, string>>}|null  $attachment  Table under the audio.
     * @param  int  $block_number  1-based number of this block in the subtest.
     * @param  int  $block_total  Expected number of blocks.
     * @param  int  $plays_used  Audio plays used in this attempt.
     * @param  int  $plays_allowed  Audio plays allowed by the mode.
     */
    public function __construct(
        public string $code,
        public string $title,
        public ?string $context,
        public ?string $passage,
        public ?string $audio_url,
        public ?int $audio_duration_seconds,
        public ?string $image_url,
        public ?array $attachment,
        public int $block_number,
        public int $block_total,
        public int $plays_used = 0,
        public int $plays_allowed = 0,
    ) {}

    /**
     * Build the view of a unit without its transcript.
     */
    public static function fromUnit(TryoutUnit $unit, int $block_number, int $block_total, int $plays_used = 0, int $plays_allowed = 0): self
    {
        return new self(
            code: $unit->code,
            title: $unit->title,
            context: $unit->context,
            passage: $unit->passage,
            audio_url: $unit->audioUrl(),
            audio_duration_seconds: $unit->audio_duration_seconds,
            image_url: $unit->imageUrl(),
            attachment: $unit->attachment,
            block_number: $block_number,
            block_total: $block_total,
            plays_used: $plays_used,
            plays_allowed: $plays_allowed,
        );
    }
}
