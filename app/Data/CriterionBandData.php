<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Band of one Writing/Speaking criterion with a short Indonesian reason.
 */
class CriterionBandData extends Data
{
    /**
     * @param  string  $key  Criterion key, e.g. "task_response".
     * @param  string  $label  Display name, e.g. "Task Response".
     * @param  float  $band  0..9 in steps of 0.5.
     * @param  string  $reason  Short reason (Indonesian).
     */
    public function __construct(
        public string $key,
        public string $label,
        public float $band,
        public string $reason = '',
    ) {}

    /**
     * Read a stored list of criteria back into DTOs, skipping malformed rows.
     *
     * @return array<int, self>
     */
    public static function listFrom(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $criteria = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['key'], $row['band']) || ! is_numeric($row['band'])) {
                continue;
            }

            $criteria[] = new self(
                key: (string) $row['key'],
                label: is_string($row['label'] ?? null) ? $row['label'] : (string) $row['key'],
                band: (float) $row['band'],
                reason: is_string($row['reason'] ?? null) ? $row['reason'] : '',
            );
        }

        return $criteria;
    }
}
