<?php

namespace App\Enums\Concerns;

trait HasSelectOptions
{
    /**
     * Get the human readable label for the enum case.
     */
    public function label(): string
    {
        return (string) $this->getLabel();
    }

    /**
     * Get every enum case as a value => label map, ready for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = (string) $case->getLabel();
        }

        return $options;
    }
}
