/**
 * Font size mark for the Filament rich editor (Inti Materi columns).
 *
 * Loaded by App\Filament\RichEditor\FontSizePlugin through
 * RichContentPlugin::getTipTapJsExtensions(). Like font-family.js it builds
 * the mark with the TipTap instance Filament exposes on
 * window.FilamentRichEditor.tiptap, so no second copy of TipTap is bundled.
 *
 * The mark stores only a whitelisted key (data-size="besar"); the CSS size
 * comes from window.filamentData.skularMaterialFontSizes (config/content.php).
 * The server re-validates the key when saving and when rendering.
 */
export default function fontSizeExtension() {
    const { Mark, mergeAttributes } = window.FilamentRichEditor.tiptap.core
    const sizes = window.filamentData?.skularMaterialFontSizes ?? {}

    return Mark.create({
        name: 'fontSize',

        addAttributes() {
            return {
                size: {
                    default: null,
                    parseHTML: (element) => {
                        const key = element.getAttribute('data-size')

                        return key && sizes[key] ? key : null
                    },
                    renderHTML: (attributes) => {
                        if (!attributes.size || !sizes[attributes.size]) {
                            return {}
                        }

                        return {
                            'data-size': attributes.size,
                            style: `font-size: ${sizes[attributes.size]}`,
                        }
                    },
                },
            }
        },

        parseHTML() {
            return [
                {
                    tag: 'span[data-size]',
                    getAttrs: (element) =>
                        sizes[element.getAttribute('data-size')] ? {} : false,
                },
            ]
        },

        renderHTML({ HTMLAttributes }) {
            return ['span', mergeAttributes(HTMLAttributes), 0]
        },

        addCommands() {
            return {
                setFontSize:
                    (size) =>
                    ({ commands }) =>
                        sizes[size] ? commands.setMark(this.name, { size }) : false,
                unsetFontSize:
                    () =>
                    ({ commands }) =>
                        commands.unsetMark(this.name),
            }
        },
    })
}
