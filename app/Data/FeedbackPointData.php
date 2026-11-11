<?php

namespace App\Data;

use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

/**
 * One feedback item of the AI practice evaluator: a short main point plus a
 * plain explanation.
 *
 * Reads both the current shape ({point, explanation}) and the legacy shape
 * (a plain string, stored before November 2026), so old rows in
 * user_practice_responses.ai_feedback render without any data migration.
 */
class FeedbackPointData extends Data
{
    /**
     * Longest main point kept, in characters (the prompt asks for 3-8 words).
     */
    public const MAX_POINT_LENGTH = 80;

    /**
     * Longest explanation kept, in characters (the prompt asks for 1-2 sentences).
     */
    public const MAX_EXPLANATION_LENGTH = 400;

    /**
     * Most items kept per category (the prompt asks for 2-4).
     */
    public const MAX_ITEMS = 4;

    /**
     * @param  string|null  $point  Short main point, rendered bold; null for legacy items.
     * @param  string  $explanation  One or two plain sentences; may be empty when only a point exists.
     */
    public function __construct(
        public ?string $point,
        public string $explanation,
    ) {}

    /**
     * Normalise one stored or AI-returned item, or null when it holds no text.
     *
     * @param  mixed  $item  A FeedbackPointData, a legacy string or an array with point/explanation keys.
     */
    public static function fromMixed(mixed $item): ?self
    {
        if ($item instanceof self) {
            $item = ['point' => $item->point, 'explanation' => $item->explanation];
        }

        if (is_scalar($item)) {
            $explanation = self::clean((string) $item, self::MAX_EXPLANATION_LENGTH);

            return $explanation === '' ? null : new self(point: null, explanation: $explanation);
        }

        if (! is_array($item)) {
            return null;
        }

        $point = is_scalar($item['point'] ?? null) ? self::clean((string) $item['point'], self::MAX_POINT_LENGTH) : '';
        $explanation = is_scalar($item['explanation'] ?? null) ? self::clean((string) $item['explanation'], self::MAX_EXPLANATION_LENGTH) : '';

        // The point is a label, so a trailing colon from the model is dropped.
        $point = (string) preg_replace('/[\s:.\-\x{2013}\x{2014}]+$/u', '', $point);

        if ($point === '' && $explanation === '') {
            return null;
        }

        return new self(point: $point === '' ? null : $point, explanation: $explanation);
    }

    /**
     * Normalise a whole category list, keeping at most MAX_ITEMS items.
     *
     * @param  mixed  $value  A list of legacy strings and/or point arrays; anything else yields [].
     * @return array<int, self>
     */
    public static function listFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $point = self::fromMixed($item);

            if ($point instanceof self) {
                $items[] = $point;
            }

            if (count($items) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $items;
    }

    /**
     * Strip markdown markers and extra whitespace, then cap the length.
     */
    private static function clean(string $text, int $limit): string
    {
        // Mentees see plain text; the model sometimes wraps points in **bold** or # headings.
        $text = (string) preg_replace('/(\*\*|__|`|^#+\s*)/mu', '', $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($text, $limit, '…');
    }
}
