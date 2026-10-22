<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

/**
 * A reorderable editor for data that is stored as a plain list of strings.
 *
 * Admins type one item per row instead of writing JSON or raw arrays. The
 * repeater rows are "{ value: string }" objects internally; the hydration and
 * dehydration hooks flatten them, so the model always receives — and always
 * exposes — a simple list of strings, which is exactly what the mentee views
 * and the detail pages expect.
 */
class ListRepeater
{
    public static function make(
        string $name,
        string $addItemLabel,
        string $placeholder,
        ?string $label = null,
        ?string $helperText = null,
        int $maxLength = 160,
    ): Repeater {
        return Repeater::make($name)
            ->label($label)
            ->hiddenLabel($label === null)
            ->schema([
                TextInput::make('value')
                    ->hiddenLabel()
                    ->required()
                    ->maxLength($maxLength)
                    ->placeholder($placeholder),
            ])
            ->formatStateUsing(fn (mixed $state): array => collect(self::toRows($state))
                ->map(fn (string $item): array => ['value' => $item])
                ->values()
                ->all())
            ->dehydrateStateUsing(fn (mixed $state): array => collect(is_array($state) ? $state : [])
                ->map(fn (mixed $item): string => trim(is_array($item) ? (string) ($item['value'] ?? '') : (string) $item))
                ->filter()
                ->values()
                ->all())
            ->addActionLabel($addItemLabel)
            ->helperText($helperText)
            ->reorderable()
            ->defaultItems(0)
            ->columnSpanFull();
    }

    /**
     * Normalise stored values into repeater rows.
     *
     * Records written before this editor existed stored the list as plain text
     * with one item per line. Those rows are split back into separate items so
     * editing an old record never drops its content.
     *
     * @return array<int, string>
     */
    private static function toRows(mixed $state): array
    {
        if (is_string($state)) {
            $state = preg_split('/\r\n|\r|\n/', $state) ?: [];
        }

        if (! is_array($state)) {
            return [];
        }

        $rows = [];

        foreach ($state as $item) {
            $rows[] = is_array($item) ? (string) ($item['value'] ?? '') : (string) $item;
        }

        return $rows;
    }
}
