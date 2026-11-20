<?php

namespace App\Filament\RichEditor;

use App\Support\MaterialStyles;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

/**
 * Rich editor plugin that lets admins pick an Inti Materi font size from the whitelist.
 *
 * Adds the fontSize TipTap mark (PHP + JS) and one toolbar tool per size in
 * config/content.php. The "normal" size equals the body text, so its tool
 * removes the mark instead of storing it. Use toolbarGroup() in
 * toolbarButtons() to show the tools as one dropdown.
 */
class FontSizePlugin implements RichContentPlugin
{
    /**
     * Filament asset id of the JS mark (published to public/js/app/).
     */
    public const SCRIPT_ID = 'rich-content-plugins/font-size';

    /**
     * Create the plugin.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the name of the toolbar tool for a size key.
     *
     * @param  string  $key  Size key from config/content.php.
     * @return string Tool name such as "sizeSangatBesar".
     */
    public static function toolName(string $key): string
    {
        return 'size'.Str::studly($key);
    }

    /**
     * Build the toolbar dropdown that lists every allowed size.
     *
     * @return ToolbarButtonGroup Dropdown labelled "Ukuran huruf".
     */
    public static function toolbarGroup(): ToolbarButtonGroup
    {
        return ToolbarButtonGroup::make('Ukuran huruf', array_map(
            self::toolName(...),
            array_keys(MaterialStyles::fontSizes()),
        ))
            ->icon(self::icon())
            ->textualButtons();
    }

    /**
     * @return array<FontSizeMark>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [
            app(FontSizeMark::class),
        ];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc(self::SCRIPT_ID),
        ];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        $tools = [];

        foreach (MaterialStyles::fontSizes() as $key => $size) {
            $is_default = $key === MaterialStyles::DEFAULT_FONT_SIZE;

            $tool = RichEditorTool::make(self::toolName($key))
                ->label($is_default ? $size['label'].' (bawaan)' : $size['label'])
                ->icon(self::icon())
                ->extraAttributes(['style' => 'font-size: '.$size['size']]);

            if ($is_default) {
                $tool
                    ->jsHandler('$getEditor()?.chain().focus().unsetFontSize().run()')
                    // Never "active": the dropdown icon lights up only when a non-default size is applied.
                    ->activeJsExpression('false');
            } else {
                $tool
                    ->jsHandler('$getEditor()?.chain().focus().setFontSize('.Js::from($key)->toHtml().').run()')
                    ->activeKey('fontSize')
                    ->activeOptions(['size' => $key]);
            }

            $tools[] = $tool;
        }

        return $tools;
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [];
    }

    /**
     * Heroicons has no text size icon, so this draws a large and a small "A".
     */
    private static function icon(): HtmlString
    {
        return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 19 8 5h1l5.5 14M4.6 14h7.8M15 19l3.1-8h.8L22 19M16.2 16.3h4.6"/></svg>');
    }
}
