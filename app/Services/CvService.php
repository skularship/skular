<?php

namespace App\Services;

use App\Contracts\CvServiceInterface;
use App\Data\CvSectionData;
use App\Data\PracticeFieldData;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Support\Collection;

class CvService implements CvServiceInterface
{
    /**
     * Build the mentee CV sections from practice answers flagged by the admin.
     *
     * @return Collection<int, CvSectionData>
     */
    public function sections(User $user): Collection
    {
        $forms = PracticeForm::query()
            ->cvSources()
            ->with('module:id,title')
            ->get();

        if ($forms->isEmpty()) {
            return collect();
        }

        $responses = UserPracticeResponse::query()
            ->where('user_id', $user->id)
            ->whereIn('practice_form_id', $forms->pluck('id'))
            ->get()
            ->keyBy('practice_form_id');

        $sections = [];

        foreach ($forms as $form) {
            $response = $responses->get($form->id);

            if (! $response instanceof UserPracticeResponse) {
                continue;
            }

            $entries = $this->entriesFor($form, $response);

            if ($entries === []) {
                continue;
            }

            $sections[] = new CvSectionData(
                practice_form_id: $form->id,
                module_id: $form->module_id,
                title: $form->title,
                module_title: $form->module?->title,
                entries: $entries,
            );
        }

        return collect($sections);
    }

    /**
     * Build the labelled entries for a single CV section.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function entriesFor(PracticeForm $form, UserPracticeResponse $response): array
    {
        $labels = [];

        foreach (PracticeFieldData::collectFromSchema($form->fields ?? []) as $field) {
            $labels[$field->name] = $field->label;
        }

        $entries = [];

        foreach ($response->answers as $name => $value) {
            $text = $this->stringifyAnswer($value);

            if ($text === '') {
                continue;
            }

            $entries[] = [
                'label' => $labels[$name] ?? (string) $name,
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
