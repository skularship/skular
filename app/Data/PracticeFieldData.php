<?php

namespace App\Data;

use App\Enums\PracticeFieldType;
use Spatie\LaravelData\Data;

/**
 * A single admin-configured field inside a dynamic practice form.
 */
class PracticeFieldData extends Data
{
    /**
     * @param  array<int, string>  $options
     */
    public function __construct(
        public string $name,
        public string $label,
        public PracticeFieldType $type,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $help_text = null,
        public array $options = [],
    ) {}

    /**
     * Build the DTO from the raw JSON schema stored on the practice form.
     *
     * @param  array<string, mixed>  $field
     */
    public static function fromSchema(array $field): ?self
    {
        $name = $field['name'] ?? null;
        $type = PracticeFieldType::tryFrom((string) ($field['type'] ?? ''));

        if (! is_string($name) || $name === '' || $type === null) {
            return null;
        }

        /** @var array<int, string> $options */
        $options = array_values(array_filter(
            array_map('strval', is_array($field['options'] ?? null) ? $field['options'] : []),
            fn (string $option): bool => $option !== '',
        ));

        return new self(
            name: $name,
            label: is_string($field['label'] ?? null) ? $field['label'] : $name,
            type: $type,
            required: (bool) ($field['required'] ?? false),
            placeholder: is_string($field['placeholder'] ?? null) ? $field['placeholder'] : null,
            help_text: is_string($field['help_text'] ?? null) ? $field['help_text'] : null,
            options: $options,
        );
    }

    /**
     * Build every valid field DTO defined on a practice form schema.
     *
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<int, self>
     */
    public static function collectFromSchema(array $schema): array
    {
        $fields = [];

        foreach ($schema as $field) {
            $dto = self::fromSchema($field);

            if ($dto !== null) {
                $fields[] = $dto;
            }
        }

        return $fields;
    }

    /**
     * Get the validation rules for this field.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        return $this->type->validationRules();
    }
}
