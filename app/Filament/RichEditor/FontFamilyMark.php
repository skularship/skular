<?php

namespace App\Filament\RichEditor;

use App\Support\MaterialStyles;
use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

/**
 * Server-side TipTap mark for the Inti Materi font family.
 *
 * Mirrors resources/js/filament/rich-content-plugins/font-family.js so the
 * editor state survives the HTML <-> TipTap conversions Filament does on the
 * server. Only fonts from config/content.php are parsed; any other value is
 * dropped while loading or saving.
 */
class FontFamilyMark extends Mark
{
    /**
     * @var string
     */
    public static $name = 'fontFamily';

    /**
     * Match spans that carry a whitelisted data-font key.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'span',
                'getAttrs' => fn ($DOMNode): bool => MaterialStyles::fontKey($DOMNode->getAttribute('data-font')) !== null,
            ],
        ];
    }

    /**
     * Store the font as its whitelist key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'font' => [
                'parseHTML' => fn ($DOMNode): ?string => MaterialStyles::fontKey($DOMNode->getAttribute('data-font')),
                'renderHTML' => function ($attributes): ?array {
                    $key = MaterialStyles::fontKey($attributes->font ?? null);

                    if ($key === null) {
                        return null;
                    }

                    return [
                        'data-font' => $key,
                        'style' => 'font-family: '.MaterialStyles::fonts()[$key]['stack'],
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
