<?php

namespace App\Services;

use App\Contracts\IqBankImportServiceInterface;
use App\Data\IqImportReportData;
use App\Enums\ContentStatus;
use App\Enums\IqSubtest;
use App\Models\IqQuestion;
use App\Models\IqTest;
use Illuminate\Support\Facades\DB;

/**
 * Imports and exports the Tes IQ bank as JSON:
 * {"tes": {"kode", "judul", "durasi_menit", "pengantar"}, "soal": [{"nomor",
 * "subtes", "bacaan", "soal", "gambar", "alt_gambar", "pilihan": {"A".."D"},
 * "kunci", "pembahasan", "aktif"}]}. "teks_soal", "gambar_url" and
 * "opsi_a".."opsi_d" are accepted as aliases. Questions are matched by test
 * code + number. An uploaded image is never wiped by an import without one,
 * and a question that fails the activation rules is stored inactive.
 */
class IqBankImportService implements IqBankImportServiceInterface
{
    /**
     * Import a bank.
     *
     * @param  array<mixed>  $payload
     */
    public function import(array $payload, bool $dry_run = false): IqImportReportData
    {
        $report = new IqImportReportData(dry_run: $dry_run);
        $meta = is_array($payload['tes'] ?? null) ? $payload['tes'] : [];
        $code = trim((string) ($meta['kode'] ?? ''));

        if ($code === '') {
            $report->errors[] = 'Kolom "tes.kode" wajib diisi.';

            return $report;
        }

        $rows = is_array($payload['soal'] ?? null) ? array_values($payload['soal']) : [];

        if ($rows === []) {
            $report->errors[] = 'Daftar "soal" kosong.';

            return $report;
        }

        DB::transaction(function () use ($meta, $code, $rows, $dry_run, $report): void {
            $test = IqTest::query()->where('code', $code)->first() ?? new IqTest([
                'code' => $code,
                'title' => 'Tes IQ Latihan',
                'duration_minutes' => (int) config('iq.duration_minutes', 20),
                'status' => ContentStatus::Draft,
            ]);

            if (filled($meta['judul'] ?? null)) {
                $test->title = mb_substr((string) $meta['judul'], 0, 255);
            }

            if (is_numeric($meta['durasi_menit'] ?? null) && (int) $meta['durasi_menit'] > 0) {
                $test->duration_minutes = (int) $meta['durasi_menit'];
            }

            if (array_key_exists('pengantar', $meta)) {
                $test->intro = filled($meta['pengantar']) ? (string) $meta['pengantar'] : null;
            }

            if (! $dry_run) {
                $test->save();
            }

            $seen = [];

            foreach ($rows as $index => $row) {
                $line = 'Baris '.($index + 1);

                if (! is_array($row)) {
                    $report->errors[] = $line.': bukan objek soal.';

                    continue;
                }

                $error = $this->rowError($row);
                $number = (int) ($row['nomor'] ?? 0);

                if ($error === null && isset($seen[$number])) {
                    $error = 'nomor '.$number.' dipakai lebih dari sekali.';
                }

                if ($error !== null) {
                    $report->errors[] = $line.': '.$error;

                    continue;
                }

                $seen[$number] = true;

                $question = $test->exists
                    ? IqQuestion::query()->where('iq_test_id', $test->id)->where('number', $number)->first()
                    : null;

                $is_new = $question === null;
                $question ??= new IqQuestion(['number' => $number]);
                $question->fill($this->attributes($row));

                $wants_active = (bool) ($row['aktif'] ?? true);
                $question->is_active = $wants_active && $question->activationErrors() === [];

                if ($wants_active && ! $question->is_active) {
                    $report->inactive++;
                }

                if ($is_new) {
                    $report->created++;
                } else {
                    $report->updated++;
                }

                if (! $dry_run) {
                    $question->iq_test_id = $test->id;
                    $question->save();
                }
            }
        });

        return $report;
    }

    /**
     * Export a test and its questions in the import format.
     *
     * @return array{tes: array<string, mixed>, soal: array<int, array<string, mixed>>}
     */
    public function export(IqTest $test): array
    {
        return [
            'tes' => [
                'kode' => $test->code,
                'judul' => $test->title,
                'durasi_menit' => $test->duration_minutes,
                'pengantar' => $test->intro,
            ],
            'soal' => $test->questions()->get()->map(fn (IqQuestion $question): array => [
                'nomor' => $question->number,
                'subtes' => $question->subtest->value,
                'bacaan' => $question->passage,
                'soal' => $question->prompt,
                'gambar' => $question->image_path,
                'alt_gambar' => $question->image_alt,
                'pilihan' => $question->optionList(),
                'kunci' => $question->answer_key,
                'pembahasan' => $question->explanation,
                'aktif' => $question->is_active,
            ])->all(),
        ];
    }

    /**
     * Why a row cannot be imported, or null.
     *
     * @param  array<mixed>  $row
     */
    private function rowError(array $row): ?string
    {
        $number = $row['nomor'] ?? null;

        if (! is_numeric($number) || (int) $number < 1) {
            return 'kolom "nomor" harus angka 1 atau lebih.';
        }

        if (IqSubtest::tryFrom(strtolower(trim((string) ($row['subtes'] ?? '')))) === null) {
            return 'subtes harus salah satu dari '.implode(', ', array_column(IqSubtest::cases(), 'value')).'.';
        }

        if (trim((string) ($row['soal'] ?? $row['teks_soal'] ?? '')) === '') {
            return 'teks soal kosong.';
        }

        foreach ($this->options($row) as $letter => $text) {
            if ($text === '') {
                return "pilihan {$letter} kosong.";
            }
        }

        if (! in_array(strtoupper(trim((string) ($row['kunci'] ?? ''))), IqQuestion::LETTERS, true)) {
            return 'kunci harus tepat satu huruf A, B, C, atau D.';
        }

        return null;
    }

    /**
     * Model attributes of a valid row. Image fields are only set when given.
     *
     * @param  array<mixed>  $row
     * @return array<string, mixed>
     */
    private function attributes(array $row): array
    {
        $options = $this->options($row);

        $attributes = [
            'subtest' => IqSubtest::from(strtolower(trim((string) $row['subtes']))),
            'passage' => filled($row['bacaan'] ?? null) ? (string) $row['bacaan'] : null,
            'prompt' => trim((string) ($row['soal'] ?? $row['teks_soal'] ?? '')),
            'option_a' => $options['A'],
            'option_b' => $options['B'],
            'option_c' => $options['C'],
            'option_d' => $options['D'],
            'answer_key' => strtoupper(trim((string) $row['kunci'])),
            'explanation' => filled($row['pembahasan'] ?? null) ? (string) $row['pembahasan'] : null,
        ];

        $image = $row['gambar'] ?? $row['gambar_url'] ?? null;

        if (is_string($image) && trim($image) !== '') {
            $attributes['image_path'] = trim($image);
        }

        if (is_string($row['alt_gambar'] ?? null) && trim($row['alt_gambar']) !== '') {
            $attributes['image_alt'] = mb_substr(trim($row['alt_gambar']), 0, 500);
        }

        return $attributes;
    }

    /**
     * The four options of a row as letter => trimmed text.
     *
     * @param  array<mixed>  $row
     * @return array<string, string>
     */
    private function options(array $row): array
    {
        $given = is_array($row['pilihan'] ?? null) ? array_change_key_case($row['pilihan'], CASE_UPPER) : [];
        $options = [];

        foreach (IqQuestion::LETTERS as $letter) {
            $value = $given[$letter] ?? $row['opsi_'.strtolower($letter)] ?? '';
            $options[$letter] = mb_substr(trim(is_scalar($value) ? (string) $value : ''), 0, 500);
        }

        return $options;
    }
}
