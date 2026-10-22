<?php

namespace App\Services;

use App\Data\BrainstormOptionData;
use App\Data\BrainstormQuestionData;
use App\Enums\BrainstormQuestionType;
use App\Models\Module;
use Illuminate\Support\Str;

/**
 * Single owner of the admin-managed brainstorming configuration.
 *
 * The configuration lives in one JSON column on the module. This service is
 * the only place that normalises, validates and reads it, so the Filament
 * editor, the mentee UI and the AI service always agree about the shape.
 */
class BrainstormingConfigService
{
    /**
     * Maximum number of questions accepted for one module.
     */
    public const MAX_QUESTIONS = 6;

    /**
     * Maximum number of options accepted per question.
     */
    public const MAX_OPTIONS = 8;

    /**
     * Maximum number of characters accepted for a question.
     */
    public const MAX_QUESTION_LENGTH = 300;

    /**
     * Maximum number of characters accepted for an option label.
     */
    public const MAX_OPTION_LENGTH = 160;

    /**
     * Maximum number of characters accepted for a question hint.
     */
    public const MAX_HINT_LENGTH = 300;

    /**
     * Maximum number of characters accepted for a free-text answer.
     */
    public const MAX_TEXT_ANSWER_LENGTH = 500;

    /**
     * Get the active, ordered questions of a module.
     *
     * This method reads the stored configuration only. It never calls an AI
     * provider, so the questions stay available even when the provider is
     * down or the API quota is exhausted.
     *
     * @return array<int, BrainstormQuestionData>
     */
    public function questions(Module $module): array
    {
        $raw = $module->brainstorming_questions;

        if (! is_array($raw)) {
            return [];
        }

        $questions = [];

        foreach ($this->hydrateForForm($raw) as $item) {
            if (! (bool) ($item['active'] ?? true)) {
                continue;
            }

            $type = BrainstormQuestionType::tryFrom((string) ($item['type'] ?? ''));
            $question = $this->cleanText($item['question'] ?? null, self::MAX_QUESTION_LENGTH);

            if ($type === null || $question === null) {
                continue;
            }

            $options = $this->activeOptions($item['options'] ?? [], (string) ($item['id'] ?? ''));

            if ($type->hasOptions() && $options === []) {
                continue;
            }

            $questions[] = new BrainstormQuestionData(
                id: (string) ($item['id'] ?? ''),
                question: $question,
                type: $type,
                options: $options,
                required: (bool) ($item['required'] ?? false),
                active: true,
                order: (int) ($item['order'] ?? 0),
                hint: $this->cleanText($item['hint'] ?? null, self::MAX_HINT_LENGTH),
            );

            if (count($questions) >= self::MAX_QUESTIONS) {
                break;
            }
        }

        usort($questions, fn (BrainstormQuestionData $left, BrainstormQuestionData $right): int => $left->order <=> $right->order);

        return $questions;
    }

    /**
     * Prepare stored configuration for the Filament editor without losing data.
     *
     * Missing identifiers are generated and nested values receive the shape the
     * form expects. Invalid entries are intentionally kept so an admin can fix
     * them instead of having them silently disappear from the editor.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hydrateForForm(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $questions = [];

        foreach (array_values($raw) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = is_scalar($item['question'] ?? null) ? (string) $item['question'] : '';
            $id = $this->cleanText($item['id'] ?? null, 64)
                ?? $this->fallbackId('q', $index.'|'.$question);

            $questions[] = [
                'id' => $id,
                'question' => $question,
                'type' => is_scalar($item['type'] ?? null) ? (string) $item['type'] : BrainstormQuestionType::SingleChoice->value,
                'required' => (bool) ($item['required'] ?? false),
                'active' => (bool) ($item['active'] ?? true),
                'hint' => is_scalar($item['hint'] ?? null) ? (string) $item['hint'] : '',
                'order' => $index,
                'options' => $this->hydrateOptions($item['options'] ?? null, $id),
            ];
        }

        return $questions;
    }

    /**
     * Normalise the configuration before it is written to the module.
     *
     * Identifiers are generated once and reused, order follows the editor's
     * row order, and free-text answers are not affected by label edits.
     *
     * @return array<int, array<string, mixed>>
     */
    public function normaliseForStorage(mixed $raw): array
    {
        $questions = [];
        $question_ids = [];

        foreach ($this->hydrateForForm($raw) as $index => $item) {
            $type = BrainstormQuestionType::tryFrom((string) ($item['type'] ?? ''));

            if ($type === null) {
                $type = BrainstormQuestionType::SingleChoice;
            }

            $id = (string) $item['id'];

            if (in_array($id, $question_ids, true)) {
                $id = $this->newId('q');
            }

            $question_ids[] = $id;

            $options = [];
            $option_ids = [];

            foreach ($item['options'] as $option_index => $option) {
                if ($type->hasOptions() === false || ! is_array($option)) {
                    continue;
                }

                if (count($options) >= self::MAX_OPTIONS) {
                    break;
                }

                $label = $this->cleanText($option['label'] ?? null, self::MAX_OPTION_LENGTH);

                if ($label === null) {
                    continue;
                }

                $option_id = (string) $option['id'];

                if ($option_id === '' || in_array($option_id, $option_ids, true)) {
                    $option_id = $this->newId('o');
                }

                $option_ids[] = $option_id;

                $options[] = [
                    'id' => $option_id,
                    'label' => $label,
                    'value' => $this->cleanText($option['value'] ?? null, self::MAX_OPTION_LENGTH) ?? $option_id,
                    'order' => $option_index,
                    'active' => (bool) ($option['active'] ?? true),
                ];
            }

            $questions[] = [
                'id' => $id,
                'question' => $this->cleanText($item['question'] ?? null, self::MAX_QUESTION_LENGTH) ?? '',
                'type' => $type->value,
                'required' => (bool) $item['required'],
                'active' => (bool) $item['active'],
                'hint' => $this->cleanText($item['hint'] ?? null, self::MAX_HINT_LENGTH),
                'order' => $index,
                'options' => $options,
            ];
        }

        return $questions;
    }

    /**
     * Validate the admin configuration.
     *
     * Inactive questions are treated as drafts, so a temporarily disabled
     * question never blocks the module from being published.
     *
     * @return array<int, string>
     */
    public function validationMessages(mixed $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        if (! is_array($raw)) {
            return ['Konfigurasi pertanyaan pemancing tidak valid.'];
        }

        $messages = [];
        $question_ids = [];
        $active_count = 0;

        foreach ($this->hydrateForForm($raw) as $item) {
            if (! (bool) ($item['active'] ?? true)) {
                continue;
            }

            $active_count++;

            $question = $this->cleanText($item['question'] ?? null, self::MAX_QUESTION_LENGTH);

            if ($question === null) {
                $messages[] = 'Teks pertanyaan pemancing tidak boleh kosong.';

                continue;
            }

            $type = BrainstormQuestionType::tryFrom((string) ($item['type'] ?? ''));

            if ($type === null) {
                $messages[] = 'Tipe input pertanyaan pemancing tidak valid.';

                continue;
            }

            $id = (string) ($item['id'] ?? '');

            if ($id === '' || in_array($id, $question_ids, true)) {
                $messages[] = 'Identifier pertanyaan pemancing harus unik.';
            }

            $question_ids[] = $id;

            if ($type->hasOptions() === false) {
                continue;
            }

            $options = array_values(array_filter(
                $this->hydrateOptions($item['options'] ?? [], (string) ($item['id'] ?? '')),
                fn (array $option): bool => (bool) ($option['active'] ?? true),
            ));

            if ($options === []) {
                $messages[] = 'Pertanyaan pilihan wajib memiliki minimal satu pilihan jawaban yang valid.';

                continue;
            }

            if (count($options) > self::MAX_OPTIONS) {
                $messages[] = 'Maksimal '.self::MAX_OPTIONS.' pilihan jawaban per pertanyaan.';
            }

            $option_ids = [];
            $has_invalid_option = false;

            foreach ($options as $option) {
                $option_id = (string) ($option['id'] ?? '');

                if ($option_id === '' || $this->cleanText($option['label'] ?? null, self::MAX_OPTION_LENGTH) === null) {
                    $has_invalid_option = true;

                    continue;
                }

                $option_ids[] = $option_id;
            }

            if ($has_invalid_option) {
                $messages[] = 'Setiap pilihan jawaban wajib memiliki identifier dan teks pilihan.';
            }

            if ($option_ids === []) {
                $messages[] = 'Pertanyaan pilihan wajib memiliki minimal satu pilihan jawaban yang valid.';

                continue;
            }

            if (count($option_ids) !== count(array_unique($option_ids))) {
                $messages[] = 'Identifier pilihan jawaban tidak boleh duplikat dalam satu pertanyaan.';
            }
        }

        if ($active_count > self::MAX_QUESTIONS) {
            $messages[] = 'Maksimal '.self::MAX_QUESTIONS.' pertanyaan pemancing per modul.';
        }

        return $messages;
    }

    /**
     * Validate the mentee answers against the module's official configuration.
     *
     * Answers are keyed by the stable question identifier. Every value is
     * checked against the stored options; a tampered payload can never become
     * the source of truth.
     *
     * @param  array<string, mixed>  $answers
     * @return array{
     *     questions: array<int, BrainstormQuestionData>,
     *     answers: array<int, array{question: string, answer: string}>,
     *     errors: array<string, string>
     * }
     */
    public function resolveAnswers(Module $module, array $answers): array
    {
        $questions = $this->questions($module);
        $resolved = [];
        $errors = [];

        foreach ($questions as $question) {
            $value = $answers[$question->id] ?? null;

            if ($this->isEmptyAnswer($value)) {
                if ($question->required) {
                    $errors[$question->id] = 'Pertanyaan ini wajib dijawab.';
                }

                continue;
            }

            $result = match ($question->type) {
                BrainstormQuestionType::SingleChoice => $this->resolveSingleChoice($question, $value),
                BrainstormQuestionType::MultipleChoice => $this->resolveMultipleChoice($question, $value),
                BrainstormQuestionType::ShortText => $this->resolveShortText($value),
            };

            if (! $result['valid']) {
                $errors[$question->id] = $result['message'];

                continue;
            }

            $resolved[] = [
                'question' => $question->question,
                'answer' => $result['message'],
            ];
        }

        if ($resolved === [] && $errors === []) {
            $errors['answers'] = 'Jawab minimal satu pertanyaan dulu sebelum meminta kerangka draf.';
        }

        return [
            'questions' => $questions,
            'answers' => $resolved,
            'errors' => $errors,
        ];
    }

    /**
     * Keep only answers that still match the module's active configuration.
     *
     * This is used when restoring a stored session after an admin edits the
     * questions, so stale or forged option identifiers never reach the UI.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function sanitizeAnswers(Module $module, array $answers): array
    {
        $clean = [];

        foreach ($this->questions($module) as $question) {
            $value = $answers[$question->id] ?? null;

            if ($this->isEmptyAnswer($value)) {
                continue;
            }

            if ($question->type === BrainstormQuestionType::SingleChoice) {
                if (is_string($value) && $this->findOption($question, $value) instanceof BrainstormOptionData) {
                    $clean[$question->id] = $value;
                }

                continue;
            }

            if ($question->type === BrainstormQuestionType::MultipleChoice) {
                if (! is_array($value)) {
                    continue;
                }

                $ids = [];

                foreach ($value as $option_id) {
                    if (is_string($option_id) && $this->findOption($question, $option_id) instanceof BrainstormOptionData) {
                        $ids[] = $option_id;
                    }
                }

                $ids = array_values(array_unique($ids));

                if ($ids !== []) {
                    $clean[$question->id] = $ids;
                }

                continue;
            }

            if (is_scalar($value)) {
                $text = mb_substr(trim((string) $value), 0, self::MAX_TEXT_ANSWER_LENGTH);

                if ($text !== '') {
                    $clean[$question->id] = $text;
                }
            }
        }

        return $clean;
    }

    /**
     * Resolve one single-choice answer into its option label.
     *
     * @return array{valid: bool, message: string}
     */
    private function resolveSingleChoice(BrainstormQuestionData $question, mixed $value): array
    {
        if (! is_string($value)) {
            return ['valid' => false, 'message' => 'Pilih satu jawaban yang tersedia.'];
        }

        $option = $this->findOption($question, $value);

        if ($option === null) {
            return ['valid' => false, 'message' => 'Pilihan jawaban tidak dikenal. Muat ulang pertanyaannya ya.'];
        }

        return ['valid' => true, 'message' => $option->label];
    }

    /**
     * Resolve one multiple-choice answer into the selected option labels.
     *
     * @return array{valid: bool, message: string}
     */
    private function resolveMultipleChoice(BrainstormQuestionData $question, mixed $value): array
    {
        if (! is_array($value)) {
            return ['valid' => false, 'message' => 'Pilih jawaban dari daftar yang tersedia.'];
        }

        $ids = array_values(array_unique(array_filter(
            array_map(fn (mixed $item): ?string => is_string($item) ? $item : null, $value),
            fn (?string $item): bool => $item !== null && $item !== '',
        )));

        if ($ids === []) {
            return ['valid' => false, 'message' => 'Pilih minimal satu jawaban.'];
        }

        $labels = [];

        foreach ($ids as $id) {
            $option = $this->findOption($question, $id);

            if ($option === null) {
                return ['valid' => false, 'message' => 'Pilihan jawaban tidak dikenal. Muat ulang pertanyaannya ya.'];
            }

            $labels[] = $option->label;
        }

        return ['valid' => true, 'message' => implode(', ', $labels)];
    }

    /**
     * Resolve a free-text answer, bounded to a safe length.
     *
     * @return array{valid: bool, message: string}
     */
    private function resolveShortText(mixed $value): array
    {
        if (! is_scalar($value)) {
            return ['valid' => false, 'message' => 'Tulis jawaban dalam bentuk teks.'];
        }

        $text = trim((string) $value);

        if ($text === '') {
            return ['valid' => false, 'message' => 'Tulis jawabanmu dulu ya.'];
        }

        if (mb_strlen($text) > self::MAX_TEXT_ANSWER_LENGTH) {
            return ['valid' => false, 'message' => 'Jawaban terlalu panjang. Ringkas menjadi '.self::MAX_TEXT_ANSWER_LENGTH.' karakter atau kurang.'];
        }

        return ['valid' => true, 'message' => $text];
    }

    /**
     * Find an active option by its stable identifier.
     */
    private function findOption(BrainstormQuestionData $question, string $id): ?BrainstormOptionData
    {
        foreach ($question->options as $option) {
            if ($option->active && $option->id === $id) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Determine whether an answer value carries no answer.
     */
    private function isEmptyAnswer(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === [];
    }

    /**
     * Build the active option DTOs from a stored question.
     *
     * @return array<int, BrainstormOptionData>
     */
    private function activeOptions(mixed $raw, string $question_id = ''): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $options = [];
        $ids = [];

        foreach ($this->hydrateOptions($raw, $question_id) as $item) {
            if (! (bool) ($item['active'] ?? true)) {
                continue;
            }

            $label = $this->cleanText($item['label'] ?? null, self::MAX_OPTION_LENGTH);
            $id = (string) ($item['id'] ?? '');

            if ($label === null || $id === '' || in_array($id, $ids, true)) {
                continue;
            }

            $ids[] = $id;

            $options[] = new BrainstormOptionData(
                id: $id,
                label: $label,
                value: $this->cleanText($item['value'] ?? null, self::MAX_OPTION_LENGTH) ?? $id,
                order: (int) ($item['order'] ?? 0),
                active: true,
            );

            if (count($options) >= self::MAX_OPTIONS) {
                break;
            }
        }

        return $options;
    }

    /**
     * Give nested option entries the shape the Filament repeater expects.
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateOptions(mixed $raw, string $question_id = ''): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $options = [];

        foreach (array_values($raw) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = is_scalar($item['label'] ?? null) ? (string) $item['label'] : '';

            $options[] = [
                'id' => $this->cleanText($item['id'] ?? null, 64)
                    ?? $this->fallbackId('o', $question_id.'|'.$index.'|'.$label),
                'label' => $label,
                'value' => is_scalar($item['value'] ?? null) ? (string) $item['value'] : '',
                'order' => $index,
                'active' => (bool) ($item['active'] ?? true),
            ];
        }

        return $options;
    }

    /**
     * Generate a stable identifier for a new question or option.
     */
    private function newId(string $prefix): string
    {
        return $prefix.'_'.strtolower((string) Str::ulid());
    }

    /**
     * Build a deterministic identifier for legacy data that has no identifier.
     *
     * Reading the same stored row twice must produce the same identifier, even
     * before the configuration is saved again through the editor.
     */
    private function fallbackId(string $prefix, string $seed): string
    {
        return $prefix.'_'.substr(hash('sha256', $seed), 0, 20);
    }

    /**
     * Trim a value into a bounded string, or null when it is unusable.
     */
    private function cleanText(mixed $value, int $limit): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }
}
