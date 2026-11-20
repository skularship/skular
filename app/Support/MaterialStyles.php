<?php

namespace App\Support;

/**
 * Typed access to the allowed Inti Materi text colors, fonts and font sizes (config/content.php).
 *
 * Used by the admin rich editor, the font family and font size plugins and
 * the HTML sanitizer so they always agree on the same whitelist.
 */
final class MaterialStyles
{
    /**
     * Font size key that equals the body text; the editor never stores it.
     */
    public const DEFAULT_FONT_SIZE = 'normal';

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
     * Get the allowed font sizes keyed by their stored key.
     *
     * @return array<string, array{label: string, size: string}>
     */
    public static function fontSizes(): array
    {
        /** @var array<string, array{label: string, size: string}> $sizes */
        $sizes = config('content.material.font_sizes', []);

        return $sizes;
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

    /**
     * Find the font size key of a stored data-size key or a pasted CSS font-size.
     *
     * Only exact whitelisted values match ("1.25em"); pixel sizes are dropped.
     *
     * @param  string|null  $value  A key such as "besar" or a value such as "1.25em".
     * @return string|null The whitelisted key, or null when the size is not allowed.
     */
    public static function fontSizeKey(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        foreach (self::fontSizes() as $key => $size) {
            if ($value === $key || $value === strtolower($size['size'])) {
                return $key;
            }
        }

        return null;
    }
}
