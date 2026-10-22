<?php

namespace App\Concerns;

use App\Data\PracticeFieldData;
use App\Enums\PracticeFieldType;
use App\Models\PracticeForm;
use Illuminate\Validation\Rule;

trait PracticeFieldRules
{
    /**
     * Build validation rules for every field defined on the practice form.
     *
     * @param  array<int, string>  $answered  field names that already hold a stored answer
     * @return array<string, array<int, mixed>>
     */
    protected function practiceFieldRules(PracticeForm $practice_form, array $answered = []): array
    {
        $rules = [];

        foreach (PracticeFieldData::collectFromSchema($practice_form->fields ?? []) as $field) {
            $key = 'answers.'.$field->name;
            $required = $field->required && ! in_array($field->name, $answered, true);

            if ($field->type === PracticeFieldType::Checkbox) {
                $rules[$key] = [$required ? 'required' : 'nullable', 'array'];

                if ($field->options !== []) {
                    $rules[$key.'.*'] = [Rule::in($field->options)];
                }

                continue;
            }

            $field_rules = $field->validationRules();

            if ($field->type->hasOptions() && $field->options !== []) {
                $field_rules[] = Rule::in($field->options);
            }

            $rules[$key] = array_merge(
                [$required ? 'required' : 'nullable'],
                $field_rules,
            );
        }

        return $rules;
    }

    /**
     * Build friendly attribute names for the dynamic fields.
     *
     * @return array<string, string>
     */
    protected function practiceFieldAttributes(PracticeForm $practice_form): array
    {
        $attributes = [];

        foreach (PracticeFieldData::collectFromSchema($practice_form->fields ?? []) as $field) {
            $attributes['answers.'.$field->name] = $field->label;
        }

        return $attributes;
    }
}
