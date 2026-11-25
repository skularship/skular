<?php

namespace App\Contracts;

use App\Data\TryoutImportReportData;

/**
 * Imports a JSON question bank (units + questions) in the bank specification format.
 */
interface TryoutBankImportServiceInterface
{
    /**
     * Validate every row and upsert units and questions by code.
     *
     * Questions that already have mentee answers are never changed.
     *
     * @param  array<string, mixed>  $payload  Decoded JSON: {"units": [...], "questions": [...]}.
     * @param  bool  $dry_run  Validate and count only; write nothing.
     */
    public function import(array $payload, bool $dry_run = false): TryoutImportReportData;
}
