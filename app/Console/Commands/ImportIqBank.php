<?php

namespace App\Console\Commands;

use App\Contracts\IqBankImportServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Imports a Tes IQ question bank from a JSON file (same rules as the
 * "Impor JSON" admin action). Use --dry-run first.
 */
#[Signature('iq:import {file : Path to the JSON bank} {--dry-run : Validate and count only}')]
#[Description('Impor bank soal Tes IQ dari file JSON')]
class ImportIqBank extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IqBankImportServiceInterface $importer): int
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
