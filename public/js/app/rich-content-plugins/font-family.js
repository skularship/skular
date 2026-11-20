/**
 * Font family mark for the Filament rich editor (Inti Materi columns).
 *
 * Loaded by App\Filament\RichEditor\FontFamilyPlugin through
 * RichContentPlugin::getTipTapJsExtensions(). It builds the mark with the
 * TipTap instance Filament exposes on window.FilamentRichEditor.tiptap, so no
 * second copy of TipTap is bundled. The default export is a factory: Filament
 * calls it after its editor bundle has loaded.
 *
 * The mark stores only a whitelisted key (data-font="lora"); the CSS stack
 * comes from window.filamentData.skularMaterialFonts (config/content.php).
 * The server re-validates the key when saving and when rendering.
 */
export default function fontFamilyExtension() {
    const { Mark, mergeAttributes } = window.FilamentRichEditor.tiptap.core
    const fonts = window.filamentData?.skularMaterialFonts ?? {}

    return Mark.create({
        name: 'fontFamily',

        addAttributes() {
            return {
                font: {
                    default: null,
                    parseHTML: (element) => {
                        const key = element.getAttribute('data-font')

                        return key && fonts[key] ? key : null
                    },
                    renderHTML: (attributes) => {
                        if (!attributes.font || !fonts[attributes.font]) {
                            return {}
                        }

                        return {
                            'data-font': attributes.font,
                            style: `font-family: ${fonts[attributes.font]}`,
                        }
                    },
                },
            }
        },

        parseHTML() {
            return [
                {
                    tag: 'span[data-font]',
                    getAttrs: (element) =>
                        fonts[element.getAttribute('data-font')] ? {} : false,
                },
            ]
        },

        renderHTML({ HTMLAttributes }) {
            return ['span', mergeAttributes(HTMLAttributes), 0]
        },

        addCommands() {
            return {
                setFontFamily:
                    (font) =>
                    ({ commands }) =>
                        fonts[font]
                            ? commands.setMark(this.name, { font })
                            : false,
                unsetFontFamily:
                    () =>
                    ({ commands }) =>
                        commands.unsetMark(this.name),
            }
        },
    })
}
