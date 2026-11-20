<?php

namespace App\Filament\RichEditor;

use App\Support\MaterialStyles;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

/**
 * Rich editor plugin that lets admins pick an Inti Materi font from the whitelist.
 *
 * Adds the fontFamily TipTap mark (PHP + JS) and one toolbar tool per font in
 * config/content.php. The first font is the brand default (Rubik), so its
 * tool removes the mark instead of storing it. Use toolbarGroup() in
 * toolbarButtons() to show the tools as one dropdown.
 */
class FontFamilyPlugin implements RichContentPlugin
{
    /**
     * Filament asset id of the JS mark (published to public/js/app/).
     */
    public const SCRIPT_ID = 'rich-content-plugins/font-family';

    /**
     * Create the plugin.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the name of the toolbar tool for a font key.
     *
     * @param  string  $key  Font key from config/content.php.
     * @return string Tool name such as "fontLora".
     */
    public static function toolName(string $key): string
    {
        return 'font'.Str::studly($key);
    }

    /**
     * Build the toolbar dropdown that lists every allowed font.
     *
     * @return ToolbarButtonGroup Dropdown labelled "Jenis huruf".
     */
    public static function toolbarGroup(): ToolbarButtonGroup
    {
        return ToolbarButtonGroup::make('Jenis huruf', array_map(
            self::toolName(...),
            array_keys(MaterialStyles::fonts()),
        ))
            ->icon(Heroicon::Language)
            ->textualButtons();
    }

    /**
     * @return array<FontFamilyMark>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [
            app(FontFamilyMark::class),
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
        $is_default = true;

        foreach (MaterialStyles::fonts() as $key => $font) {
            $tool = RichEditorTool::make(self::toolName($key))
                ->label($is_default ? $font['label'].' (bawaan)' : $font['label'])
                ->icon(Heroicon::Language)
                ->extraAttributes(['style' => 'font-family: '.$font['stack']]);

            if ($is_default) {
                $tool
                    ->jsHandler('$getEditor()?.chain().focus().unsetFontFamily().run()')
                    // Never "active": the dropdown icon lights up only when a non-default font is applied.
                    ->activeJsExpression('false');
            } else {
                $tool
                    ->jsHandler('$getEditor()?.chain().focus().setFontFamily('.Js::from($key)->toHtml().').run()')
                    ->activeKey('fontFamily')
                    ->activeOptions(['font' => $key]);
            }

            $tools[] = $tool;
            $is_default = false;
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
}
