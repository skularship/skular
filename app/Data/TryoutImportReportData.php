<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Outcome of importing a JSON question bank: counts and the rows that failed.
 */
class TryoutImportReportData extends Data
{
    /**
     * @param  bool  $dry_run  Nothing was written.
     * @param  int  $units_created  New units.
     * @param  int  $units_updated  Existing units updated.
     * @param  int  $questions_created  New questions.
     * @param  int  $questions_updated  Existing questions updated.
     * @param  array<int, string>  $errors  One line per failed row.
     */
    public function __construct(
        public bool $dry_run,
        public int $units_created = 0,
        public int $units_updated = 0,
        public int $questions_created = 0,
        public int $questions_updated = 0,
        public array $errors = [],
    ) {}

    /**
     * One line summary for notifications and the console.
     */
    public function summary(): string
    {
        return sprintf(
            '%sUnit: %d baru, %d diperbarui. Soal: %d baru, %d diperbarui. Gagal: %d baris.',
            $this->dry_run ? '[Mode coba] ' : '',
            $this->units_created,
            $this->units_updated,
            $this->questions_created,
            $this->questions_updated,
            count($this->errors),
        );
    }
}
