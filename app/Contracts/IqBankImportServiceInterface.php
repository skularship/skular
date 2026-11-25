<?php

namespace App\Contracts;

use App\Data\IqImportReportData;
use App\Models\IqTest;

/**
 * Imports and exports the Tes IQ question bank as JSON, so the bank can be
 * updated from a script without a deploy. Questions are matched by test
 * code + number; a question that fails the activation rules is stored
 * inactive instead of going live.
 */
interface IqBankImportServiceInterface
{
    /**
     * Import a bank: {"tes": {...}, "soal": [...]}.
     *
     * @param  array<mixed>  $payload  Decoded JSON.
     * @param  bool  $dry_run  Validate and count only.
     */
    public function import(array $payload, bool $dry_run = false): IqImportReportData;

    /**
     * Export a test and its questions in the import format.
     *
     * @return array{tes: array<string, mixed>, soal: array<int, array<string, mixed>>}
     */
    public function export(IqTest $test): array;
}
