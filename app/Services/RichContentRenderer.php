<?php

namespace App\Services;

use App\Contracts\RichContentRendererInterface;
use App\Enums\MaterialFormat;
use App\Support\MaterialStyles;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Renderer for curriculum material: CommonMark for Markdown and an allowlist
 * sanitizer for rich editor HTML (Inti Materi columns).
 *
 * Bound as a singleton: the CommonMark environment and the sanitizer are
 * built once per request and every rendered text is memoised, so a Pahami
 * page with many Inti Materi columns converts each text only once. A shared
 * cache is not used on purpose: with the database cache store a cache hit
 * costs a query, which is slower than converting a few kilobytes of text.
 */
class RichContentRenderer implements RichContentRendererInterface
{
    /**
     * Elements kept by the HTML sanitizer, with their allowed attributes.
     *
     * UNWRAPPED_ELEMENTS keep their text; anything else is removed together
     * with its content.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_ELEMENTS = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [],
        'a' => ['href'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'span' => ['data-color', 'data-font', 'data-size', 'style'],
    ];

    /**
     * Harmless wrappers that are unwrapped: the tag goes, its text stays.
     *
     * Every element that is neither allowed nor listed here is removed
     * together with its content (scripts, styles, iframes, forms, media...).
     *
     * @var array<int, string>
     */
    private const UNWRAPPED_ELEMENTS = [
        'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'figure', 'figcaption', 'caption',
        'small', 'big', 'sub', 'sup', 'code', 'pre', 'font', 'center', 'label', 'abbr', 'cite', 'q', 'del', 'ins',
        'dl', 'dt', 'dd', 'details', 'summary', 'tfoot', 'colgroup', 'col',
    ];

    /**
     * Rendered HTML of this request, keyed by format, heading level and source hash.
     *
     * @var array<string, string>
     */
    private array $rendered = [];

    private ?MarkdownConverter $converter = null;

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * Render Markdown to sanitised HTML.
     *
     * @param  string|null  $markdown  Stored material; null or blank yields ''.
     * @param  int  $heading_level  HTML level used for a Markdown `#` (2-6).
     * @return string HTML that is safe to echo unescaped.
     */
    public function toHtml(?string $markdown, int $heading_level = 4): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $markdown));

        if ($text === '') {
            return '';
        }

        $heading_level = max(2, min(6, $heading_level));
        $key = $heading_level.':'.hash('xxh128', $text);

        return $this->rendered[$key] ??= $this->shiftHeadings(
            $this->converter()->convert($text)->getContent(),
            $heading_level,
        );
    }

    /**
     * Render stored material in the given format to sanitised HTML.
     *
     * @param  string|null  $content  Stored material; null or blank yields ''.
     * @param  MaterialFormat|string  $format  Storage format; unknown values fall back to Markdown.
     * @param  int  $heading_level  HTML level used for a top-level heading (2-6).
     * @return string HTML that is safe to echo unescaped.
     */
    public function toHtmlFromFormat(?string $content, MaterialFormat|string $format, int $heading_level = 4): string
    {
        if (! $format instanceof MaterialFormat) {
            $format = MaterialFormat::tryFrom($format) ?? MaterialFormat::Markdown;
        }

        if ($format === MaterialFormat::Markdown) {
            return $this->toHtml($content, $heading_level);
        }

        $source = trim((string) $content);

        if ($source === '') {
            return '';
        }

        $heading_level = max(2, min(6, $heading_level));
        $key = 'html:'.$heading_level.':'.hash('xxh128', $source);

        return $this->rendered[$key] ??= $this->renderHtml($source, $heading_level);
    }

    /**
     * Convert stored Markdown to the HTML the rich editor starts from.
     *
     * @param  string|null  $markdown  Legacy Markdown or plain text.
     * @return string CommonMark HTML without heading shifting (raw HTML stripped).
     */
    public function markdownToEditorHtml(?string $markdown): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $markdown));

        if ($text === '') {
            return '';
        }

        return trim($this->converter()->convert($text)->getContent());
    }

    /**
     * Sanitise rich editor HTML, rebuild styles from the whitelist and shift the headings.
     */
    private function renderHtml(string $source, int $heading_level): string
    {
        $html = $this->rebuildInlineTags($this->sanitizer()->sanitize($source));

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (trim(str_replace("\u{00A0}", ' ', $text)) === '') {
            return '';
        }

        return $this->shiftHeadings($html, $heading_level);
    }

    /**
     * Rebuild every <span> and <a> from scratch so only whitelisted values survive.
     *
     * The sanitizer output is normalised (lowercase names, double-quoted and
     * encoded values), which makes this attribute parsing reliable.
     */
    private function rebuildInlineTags(string $html): string
    {
        return (string) preg_replace_callback(
            '/<(span|a)\b([^>]*)>/',
            function (array $match): string {
                $attributes = $this->parseAttributes($match[2]);

                return $match[1] === 'span'
                    ? $this->spanTag($attributes)
                    : $this->linkTag($attributes);
            },
            $html,
        );
    }

    /**
     * Build a <span> that only carries a whitelisted color, font and font size.
     *
     * Stored inline styles are never echoed: Filament writes CSS variables and
     * pasted text can carry anything, so the style is generated here.
     *
     * @param  array<string, string>  $attributes
     */
    private function spanTag(array $attributes): string
    {
        $declarations = $this->styleDeclarations($attributes['style'] ?? '');

        $color_key = MaterialStyles::colorKey($attributes['data-color'] ?? null)
            ?? MaterialStyles::colorKey($declarations['color'] ?? null);
        $font_key = MaterialStyles::fontKey($attributes['data-font'] ?? null)
            ?? MaterialStyles::fontKey($declarations['font-family'] ?? null);
        $size_key = MaterialStyles::fontSizeKey($attributes['data-size'] ?? null)
            ?? MaterialStyles::fontSizeKey($declarations['font-size'] ?? null);

        $output = [];
        $style = [];

        if ($color_key !== null) {
            $output[] = 'data-color="'.e($color_key).'"';
            $style[] = 'color: '.MaterialStyles::textColors()[$color_key]['color'];
        }

        if ($font_key !== null) {
            $output[] = 'data-font="'.e($font_key).'"';
            $style[] = 'font-family: '.MaterialStyles::fonts()[$font_key]['stack'];
        }

        if ($size_key !== null) {
            $output[] = 'data-size="'.e($size_key).'"';
            $style[] = 'font-size: '.MaterialStyles::fontSizes()[$size_key]['size'];
        }

        if ($style !== []) {
            $output[] = 'style="'.e(implode('; ', $style)).'"';
        }

        return '<span'.($output === [] ? '' : ' '.implode(' ', $output)).'>';
    }

    /**
     * Build an <a> whose external links open in a new tab without an opener.
     *
     * The sanitizer already removed every href that is not http, https or mailto.
     *
     * @param  array<string, string>  $attributes
     */
    private function linkTag(array $attributes): string
    {
        $href = $attributes['href'] ?? '';

        if ($href === '') {
            return '<a>';
        }

        $tag = '<a href="'.e($href).'"';
        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
        $host = parse_url($href, PHP_URL_HOST);
        $app_host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (in_array($scheme, ['http', 'https'], true) && is_string($host) && $host !== $app_host) {
            $tag .= ' rel="noopener noreferrer" target="_blank"';
        }

        return $tag.'>';
    }

    /**
     * Parse the attributes of a sanitised tag into decoded values.
     *
     * @return array<string, string>
     */
    private function parseAttributes(string $source): array
    {
        preg_match_all('/([a-z][a-z0-9-]*)(?:="([^"]*)")?/', $source, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[$match[1]] = html_entity_decode($match[2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $attributes;
    }

    /**
     * Split an inline style into lowercase property => value pairs.
     *
     * @return array<string, string>
     */
    private function styleDeclarations(string $style): array
    {
        $declarations = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));

            if ($property !== '' && trim($value) !== '') {
                $declarations[$property] = trim($value);
            }
        }

        return $declarations;
    }

    /**
     * Map <h1>-<h6> onto the page outline and add the brand classes.
     */
    private function shiftHeadings(string $html, int $heading_level): string
    {
        return (string) preg_replace_callback(
            '/<(\/?)h([1-6])>/',
            function (array $match) use ($heading_level): string {
                $depth = min(3, (int) $match[2]);
                $tag = 'h'.min(6, $heading_level + $depth - 1);

                return $match[1] === '/' ? "</{$tag}>" : "<{$tag} class=\"sk-h{$depth}\">";
            },
            $html,
        );
    }

    /**
     * Build the allowlist HTML sanitizer once.
     */
    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer instanceof HtmlSanitizer) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            // Long Inti Materi columns must not be truncated (the default is 20k characters).
            ->withMaxInputLength(1_000_000);

        foreach (self::ALLOWED_ELEMENTS as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (self::UNWRAPPED_ELEMENTS as $element) {
            $config = $config->blockElement($element);
        }

        return $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Build the CommonMark converter once.
     */
    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        $environment = new Environment([
            // Never trust HTML stored in the database.
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            // Legacy material is plain text typed in a textarea: a single
            // line break must stay a line break, not merge into one line.
            'renderer' => ['soft_break' => "<br>\n"],
            'external_link' => [
                'internal_hosts' => is_string($host) ? [$host] : [],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ExternalLinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
