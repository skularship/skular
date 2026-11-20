<?php

namespace App\Filament\RichEditor;

use App\Support\MaterialStyles;
use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

/**
 * Server-side TipTap mark for the Inti Materi font size.
 *
 * Mirrors resources/js/filament/rich-content-plugins/font-size.js so the
 * editor state survives the HTML <-> TipTap conversions Filament does on the
 * server. Only sizes from config/content.php are parsed; any other value is
 * dropped while loading or saving.
 */
class FontSizeMark extends Mark
{
    /**
     * @var string
     */
    public static $name = 'fontSize';

    /**
     * Match spans that carry a whitelisted data-size key.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'span',
                'getAttrs' => fn ($DOMNode): bool => MaterialStyles::fontSizeKey($DOMNode->getAttribute('data-size')) !== null,
            ],
        ];
    }

    /**
     * Store the size as its whitelist key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'size' => [
                'parseHTML' => fn ($DOMNode): ?string => MaterialStyles::fontSizeKey($DOMNode->getAttribute('data-size')),
                'renderHTML' => function ($attributes): ?array {
                    $key = MaterialStyles::fontSizeKey($attributes->size ?? null);

                    if ($key === null) {
                        return null;
                    }

                    return [
                        'data-size' => $key,
                        'style' => 'font-size: '.MaterialStyles::fontSizes()[$key]['size'],
                    ];
                },
            ],
        ];
    }

    /**
     * Render the mark as a span.
     *
     * @param  object  $mark
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<int, mixed>
     */
    public function renderHTML($mark, $HTMLAttributes = []): array
    {
        return ['span', HTML::mergeAttributes($HTMLAttributes), 0];
    }
}
