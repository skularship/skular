<?php

namespace App\Services;

use App\Contracts\CvServiceInterface;
use App\Data\CvSectionData;
use App\Data\PracticeFieldData;
use App\Enums\PracticeFieldType;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Builds the CV page sections, and the read-only CV reference panel of a
 * module, from the latest practice answers of forms flagged "include in CV".
 *
 * Only forms a mentee can see count (active, in a published module); entries
 * follow the current form schema, so removed or renamed fields and uploaded
 * file paths never appear. Every read is scoped to the given mentee.
 */
class CvService implements CvServiceInterface
{
    /**
     * Label used when an admin left a question without a label, so the
     * internal answer code is never shown to the mentee.
     */
    private const FALLBACK_LABEL = 'Jawaban';

    /**
     * Build the mentee CV sections from practice answers flagged by the admin.
     *
     * @param  User  $user  The mentee whose answers are read.
     * @return Collection<int, CvSectionData> Only sections with at least one answer.
     */
    public function sections(User $user): Collection
    {
        $forms = PracticeForm::query()
            ->cvSources()
            ->with('module:id,title')
            ->get();

        return $this->buildSections($user, $forms)
            ->filter(fn (CvSectionData $section): bool => $section->filled)
            ->values();
    }

    /**
     * Build the CV reference sources an admin linked to a module.
     *
     * @param  User  $user  The signed-in mentee; only their answers are read.
     * @param  Module  $module  The module whose Praktik step shows the panel.
     * @return Collection<int, CvSectionData> Sources in CV order, answered or not.
     */
    public function sectionsForModule(User $user, Module $module): Collection
    {
        $forms = PracticeForm::query()
            ->cvSources()
            ->whereHas('cvSourceFor', fn (Builder $query): Builder => $query->whereKey($module->id))
            // A module never references its own exercise; the editor refuses
            // it too, this only guards rows written another way.
            ->where('practice_forms.module_id', '!=', $module->id)
            ->with('module:id,title')
            ->get();

        return $this->buildSections($user, $forms);
    }

    /**
     * Turn source forms into sections with the mentee's latest answers.
     *
     * Answers of all forms are read with one query, always filtered by the
     * given mentee.
     *
     * @param  EloquentCollection<int, PracticeForm>  $forms
     * @return Collection<int, CvSectionData>
     */
    private function buildSections(User $user, EloquentCollection $forms): Collection
    {
        if ($forms->isEmpty()) {
            return collect();
        }

        $responses = UserPracticeResponse::query()
            ->where('user_id', $user->id)
            ->whereIn('practice_form_id', $forms->pluck('id'))
            ->get(['id', 'user_id', 'practice_form_id', 'answers'])
            ->keyBy('practice_form_id');

        return $forms->map(function (PracticeForm $form) use ($responses): CvSectionData {
            $response = $responses->get($form->id);
            $entries = $response instanceof UserPracticeResponse ? $this->entriesFor($form, $response) : [];
            $button_label = trim((string) $form->cv_button_label);

            return new CvSectionData(
                practice_form_id: $form->id,
                module_id: $form->module_id,
                title: $form->title,
                module_title: $form->module?->title,
                entries: $entries,
                button_label: $button_label !== '' ? $button_label : null,
                filled: $entries !== [],
            );
        })->values()->toBase();
    }

    /**
     * Build the labelled entries for a single CV section.
     *
     * Walks the current form schema (not the stored answers), so the entries
     * keep the admin's question order, answers of removed or renamed fields
     * are skipped, and file uploads (a storage path) are never shown.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function entriesFor(PracticeForm $form, UserPracticeResponse $response): array
    {
        $answers = $response->answers;
        $entries = [];

        foreach ($form->fields ?? [] as $raw_field) {
            $field = PracticeFieldData::fromSchema($raw_field);

            if ($field === null || $field->type === PracticeFieldType::File) {
                continue;
            }

            $text = $this->stringifyAnswer($answers[$field->name] ?? null);

            if ($text === '') {
                continue;
            }

            // PracticeFieldData falls back to the answer code for a missing
            // label; read the raw label so that code never reaches a mentee.
            $label = is_string($raw_field['label'] ?? null) ? trim($raw_field['label']) : '';

            $entries[] = [
                'label' => $label !== '' ? $label : self::FALLBACK_LABEL,
                'value' => $text,
            ];
        }

        return $entries;
    }

    /**
     * Convert a stored practice answer into displayable text.
     */
    private function stringifyAnswer(mixed $value): string
    {
        if (is_array($value)) {
            $items = array_filter(
                array_map(
                    fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
                    $value,
                ),
                fn (string $item): bool => $item !== '',
            );

            return implode(', ', $items);
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
