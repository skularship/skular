<?php

namespace App\Console\Commands;

use App\Contracts\TryoutBankImportServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Imports a Try Out question bank from a JSON file (same rules as the
 * "Impor JSON" admin action). Use --dry-run first.
 */
#[Signature('tryout:import {file : Path to the JSON bank} {--dry-run : Validate and count only}')]
#[Description('Impor bank soal Try Out dari file JSON')]
class ImportTryoutBank extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TryoutBankImportServiceInterface $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error('File tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            $this->error('File bukan JSON yang valid.');

            return self::FAILURE;
        }

        $report = $importer->import($payload, (bool) $this->option('dry-run'));

        $this->info($report->summary());

        foreach ($report->errors as $error) {
            $this->warn($error);
        }

        return $report->errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
