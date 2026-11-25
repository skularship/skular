<?php

namespace App\Services;

use App\Contracts\TryoutBankImportServiceInterface;
use App\Data\TryoutImportReportData;
use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Imports the JSON question bank (PRD section 11). Field names follow the
 * bank specification (id, subtes, tipe_ui, pertanyaan, pilihan, kunci, ...),
 * so the converted .docx bank imports without mapping. Units and questions
 * are upserted by code; a question with mentee answers is never changed
 * (its row is reported as failed). Imported rows start as draft unless the
 * row says "status": "published".
 */
class TryoutBankImportService implements TryoutBankImportServiceInterface
{
    /**
     * Known unit codes => id during one import (0 = validated in a dry run, not saved).
     *
     * @var array<string, int>
     */
    private array $unit_codes = [];

    /**
     * Validate every row and upsert units and questions by code.
     *
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, bool $dry_run = false): TryoutImportReportData
    {
        $report = new TryoutImportReportData(dry_run: $dry_run);
        $units = is_array($payload['units'] ?? null) ? $payload['units'] : [];
        $questions = is_array($payload['questions'] ?? $payload['soal'] ?? null) ? ($payload['questions'] ?? $payload['soal']) : [];

        if ($units === [] && $questions === []) {
            $report->errors[] = 'File tidak berisi "units" atau "questions".';

            return $report;
        }

        DB::transaction(function () use ($units, $questions, $report, $dry_run): void {
            $this->unit_codes = [];

            foreach (TryoutUnit::query()->get(['id', 'code']) as $unit) {
                $this->unit_codes[$unit->code] = $unit->id;
            }

            foreach ($units as $index => $row) {
                $this->importUnit($index + 1, $row, $report, $dry_run);
            }

            foreach ($questions as $index => $row) {
                $this->importQuestion($index + 1, $row, $report, $dry_run);
            }
        });

        return $report;
    }

    /**
     * Validate and upsert one unit row.
     */
    private function importUnit(int $line, mixed $row, TryoutImportReportData $report, bool $dry_run): void
    {
        if (! is_array($row)) {
            $report->errors[] = "Unit baris {$line}: format tidak valid.";

            return;
        }

        $data = [
            'code' => $row['code'] ?? $row['id'] ?? null,
            'exam_type' => $row['exam_type'] ?? TryoutExamType::Ielts->value,
            'skill' => $this->skill($row['skill'] ?? $row['subtes'] ?? null),
            'title' => $row['title'] ?? $row['judul'] ?? null,
            'context' => $row['context'] ?? $row['konteks'] ?? null,
            'passage' => $row['passage'] ?? $row['teks'] ?? null,
            'transcript' => $row['transcript'] ?? $row['naskah'] ?? null,
            'image_path' => $row['image_path'] ?? $row['gambar'] ?? null,
            'attachment' => is_array($row['attachment'] ?? null) ? $row['attachment'] : null,
            'status' => $row['status'] ?? ContentStatus::Draft->value,
        ];

        $validator = Validator::make($data, [
            'code' => ['required', 'string', 'max:32'],
            'exam_type' => ['required', Rule::enum(TryoutExamType::class)],
            'skill' => ['required', Rule::enum(TryoutSkill::class)],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ]);

        if ($validator->fails()) {
            $report->errors[] = "Unit baris {$line} ({$this->label($data['code'])}): ".implode(' ', $validator->errors()->all());

            return;
        }

        $code = (string) $data['code'];
        $exists = isset($this->unit_codes[$code]);
        $exists ? $report->units_updated++ : $report->units_created++;

        if ($dry_run) {
            // Dry run: remember the code so its questions validate; 0 = not saved.
            $this->unit_codes[$code] ??= 0;

            return;
        }

        $unit = TryoutUnit::query()->updateOrCreate(['code' => $data['code']], $data);
        $this->unit_codes[$unit->code] = $unit->id;
    }

    /**
     * Validate and upsert one question row.
     */
    private function importQuestion(int $line, mixed $row, TryoutImportReportData $report, bool $dry_run): void
    {
        if (! is_array($row)) {
            $report->errors[] = "Soal baris {$line}: format tidak valid.";

            return;
        }

        $code = $row['id'] ?? $row['code'] ?? null;
        $unit_code = $row['unit'] ?? $row['teks_id'] ?? $row['audio_id'] ?? null;
        $type = $row['tipe_ui'] ?? $row['question_type'] ?? null;

        $data = [
            'code' => $code,
            'exam_type' => $row['exam_type'] ?? TryoutExamType::Ielts->value,
            'skill' => $this->skill($row['subtes'] ?? $row['skill'] ?? null),
            'number' => $row['nomor'] ?? $row['number'] ?? null,
            'part' => $row['part'] ?? $row['task'] ?? $row['bagian'] ?? null,
            'level' => $row['level'] ?? null,
            'question_type' => $type,
            'source_type' => $row['tipe'] ?? $row['source_type'] ?? null,
            'prompt' => $row['pertanyaan'] ?? $row['prompt'] ?? null,
            'prompt_items' => is_array($row['daftar_pertanyaan'] ?? $row['prompt_items'] ?? null) ? ($row['daftar_pertanyaan'] ?? $row['prompt_items']) : null,
            'prompt_table' => is_array($row['tabel'] ?? $row['prompt_table'] ?? null) ? ($row['tabel'] ?? $row['prompt_table']) : null,
            'options' => $this->options($row['pilihan'] ?? $row['options'] ?? null),
            'answer_key' => isset($row['kunci']) ? (string) $row['kunci'] : ($row['answer_key'] ?? null),
            'accepted_answers' => is_array($row['jawaban_alternatif'] ?? null) ? array_values(array_map('strval', $row['jawaban_alternatif'])) : null,
            'word_limit' => $row['batas_kata'] ?? $row['word_limit'] ?? null,
            'min_words' => $row['minimal_kata'] ?? $row['min_words'] ?? null,
            'prep_seconds' => $row['waktu_persiapan'] ?? $row['prep_seconds'] ?? null,
            'speak_seconds' => $row['waktu_bicara'] ?? $row['speak_seconds'] ?? null,
            'max_score' => $row['skor'] ?? $row['max_score'] ?? 1,
            'rubric_key' => $row['rubrik'] ?? $row['rubric_key'] ?? null,
            'answer_outline' => is_array($row['kerangka'] ?? null) ? $row['kerangka'] : null,
            'explanation' => $row['pembahasan'] ?? $row['explanation'] ?? null,
            'theory_tags' => is_array($row['tag_teori'] ?? null) ? $row['tag_teori'] : null,
            'status' => $row['status'] ?? ContentStatus::Draft->value,
        ];

        $validator = Validator::make($data, [
            'code' => ['required', 'string', 'max:32'],
            'exam_type' => ['required', Rule::enum(TryoutExamType::class)],
            'skill' => ['required', Rule::enum(TryoutSkill::class)],
            'level' => ['required', 'integer', 'between:1,5'],
            'question_type' => ['required', Rule::enum(TryoutQuestionType::class)],
            'prompt' => ['required', 'string'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'part' => ['nullable', 'integer', 'between:1,3'],
        ]);

        $errors = $validator->fails() ? $validator->errors()->all() : [];
        $question_type = TryoutQuestionType::tryFrom((string) $type);
        $skill = TryoutSkill::tryFrom((string) $data['skill']);

        if ($skill !== null && $skill->isObjective()) {
            if (blank($unit_code) || ! array_key_exists((string) $unit_code, $this->unit_codes)) {
                $errors[] = 'Unit "'.$this->label($unit_code).'" tidak ditemukan (wajib untuk Listening/Reading).';
            }

            if (blank($data['answer_key'])) {
                $errors[] = 'Kunci jawaban wajib diisi.';
            }
        }

        if ($question_type !== null && $question_type->hasOptions()) {
            $options = $data['options'] ?? [];

            if (count($options) < $question_type->minOptions()) {
                $errors[] = 'Pilihan minimal '.$question_type->minOptions().'.';
            } elseif (filled($data['answer_key']) && ! array_key_exists(strtoupper((string) $data['answer_key']), $options)) {
                $errors[] = 'Kunci "'.$data['answer_key'].'" tidak ada di pilihan.';
            }
        }

        if ($errors !== []) {
            $report->errors[] = "Soal baris {$line} ({$this->label($code)}): ".implode(' ', $errors);

            return;
        }

        $existing = TryoutQuestion::query()->where('code', $data['code'])->first();

        if ($existing !== null && $existing->hasAnswers()) {
            $report->errors[] = "Soal baris {$line} ({$data['code']}): sudah dijawab mentee, tidak diubah. Buat soal baru dengan code lain.";

            return;
        }

        $existing === null ? $report->questions_created++ : $report->questions_updated++;

        if ($dry_run) {
            return;
        }

        $data['tryout_unit_id'] = filled($unit_code) ? ($this->unit_codes[(string) $unit_code] ?? null) : null;

        if (filled($data['answer_key']) && $question_type?->hasOptions()) {
            $data['answer_key'] = strtoupper((string) $data['answer_key']);
        }

        TryoutQuestion::query()->updateOrCreate(['code' => $data['code']], $data);
    }

    /**
     * Map a subtest name ("Listening", "reading") to a TryoutSkill value.
     */
    private function skill(mixed $value): ?string
    {
        return is_string($value) ? strtolower(trim($value)) : null;
    }

    /**
     * Normalise options to letter => text. A plain list gets letters A, B, C...
     *
     * @return array<string, string>|null
     */
    private function options(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        $options = [];

        if (array_is_list($value)) {
            foreach ($value as $index => $text) {
                $options[TryoutQuestion::letter($index)] = is_scalar($text) ? (string) $text : '';
            }

            return $options;
        }

        foreach ($value as $letter => $text) {
            $options[strtoupper((string) $letter)] = (string) $text;
        }

        return $options;
    }

    /**
     * Printable code for error messages.
     */
    private function label(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '?';
    }
}
