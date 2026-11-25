<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Outcome of importing a Tes IQ JSON bank: counts and the rows that failed.
 */
class IqImportReportData extends Data
{
    /**
     * @param  bool  $dry_run  Nothing was written.
     * @param  int  $created  New questions.
     * @param  int  $updated  Existing questions updated.
     * @param  int  $inactive  Imported questions left inactive (activation rules not met).
     * @param  array<int, string>  $errors  One line per failed row.
     */
    public function __construct(
        public bool $dry_run,
        public int $created = 0,
        public int $updated = 0,
        public int $inactive = 0,
        public array $errors = [],
    ) {}

    /**
     * One line summary for notifications and the console.
     */
    public function summary(): string
    {
        return sprintf(
            '%sSoal: %d baru, %d diperbarui, %d belum aktif. Gagal: %d baris.',
            $this->dry_run ? '[Mode coba] ' : '',
            $this->created,
            $this->updated,
            $this->inactive,
            count($this->errors),
        );
    }
}
