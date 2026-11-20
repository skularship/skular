<?php

namespace App\Support;

/**
 * Typed access to the allowed Inti Materi text colors and fonts (config/content.php).
 *
 * Used by the admin rich editor, the font family plugin and the HTML
 * sanitizer so the three always agree on the same whitelist.
 */
final class MaterialStyles
{
    /**
     * Get the allowed text colors keyed by their stored key.
     *
     * @return array<string, array{label: string, color: string}>
     */
    public static function textColors(): array
    {
        /** @var array<string, array{label: string, color: string}> $colors */
        $colors = config('content.material.text_colors', []);

        return $colors;
    }

    /**
     * Get the allowed font families keyed by their stored key.
     *
     * @return array<string, array{label: string, family: string, stack: string}>
     */
    public static function fonts(): array
    {
        /** @var array<string, array{label: string, family: string, stack: string}> $fonts */
        $fonts = config('content.material.fonts', []);

        return $fonts;
    }

    /**
     * Find the color key of a stored data-color key or a pasted CSS color.
     *
     * @param  string|null  $value  A key such as "teal" or a hex color such as "#277C93".
     * @return string|null The whitelisted key, or null when the color is not allowed.
     */
    public static function colorKey(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        foreach (self::textColors() as $key => $color) {
            if ($value === $key || $value === strtolower($color['color'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Find the font key of a stored data-font key or a pasted CSS font-family.
     *
     * Only the first family of a stack is compared, without quotes.
     *
     * @param  string|null  $value  A key such as "lora" or a value such as "'Lora', serif".
     * @return string|null The whitelisted key, or null when the font is not allowed.
     */
    public static function fontKey(?string $value): ?string
    {
        $first = strtolower(trim(explode(',', (string) $value)[0], " \t\n\r\0\x0B'\""));

        if ($first === '') {
            return null;
        }

        foreach (self::fonts() as $key => $font) {
            if ($first === $key || $first === strtolower($font['family'])) {
                return $key;
            }
        }

        return null;
    }
}
